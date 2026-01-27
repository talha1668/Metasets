<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserInvestment;
use App\Models\InvestmentPlan;
use App\Services\EmailTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentLogController extends Controller
{
    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function index(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:5|max:100',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,active,completed,cancelled',
            'plan_id' => 'nullable|integer|exists:investment_plans,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_field' => 'nullable|in:investment_id,amount,expected_return,earned_amount,status,started_at,maturity_date,created_at',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $status = $request->get('status');
            $planId = $request->get('plan_id');
            $userId = $request->get('user_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $query = UserInvestment::with(['user:id,name,email,uid', 'plan:id,name,slug,interest_rate,return_type,duration_days,principal_return'])
                ->select('user_investments.*');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('investment_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('uid', 'like', "%{$search}%");
                        })
                        ->orWhereHas('plan', function ($planQuery) use ($search) {
                            $planQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($planId) {
                $query->where('plan_id', $planId);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            $investments = $query->orderBy($sortField, $sortDirection)
                ->paginate($perPage);

            $stats = [
                'total_investments' => UserInvestment::count(),
                'pending_investments' => UserInvestment::where('status', 'pending')->count(),
                'active_investments' => UserInvestment::where('status', 'active')->count(),
                'completed_investments' => UserInvestment::where('status', 'completed')->count(),
                'cancelled_investments' => UserInvestment::where('status', 'cancelled')->count(),
                'total_invested_amount' => UserInvestment::whereIn('status', ['active', 'completed'])->sum('amount'),
                'total_earned_amount' => UserInvestment::whereIn('status', ['active', 'completed'])->sum('earned_amount'),
                'total_expected_return' => UserInvestment::where('status', 'active')->sum('expected_return'),
            ];

            $investmentPlans = InvestmentPlan::select('id', 'name')
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($plan) {
                    return [
                        'value' => $plan->id,
                        'label' => $plan->name
                    ];
                });

            return Inertia::render('Admin/InvestmentLogs/Index', [
                'investments' => $investments->items(),
                'meta' => [
                    'total' => $investments->total(),
                    'current_page' => $investments->currentPage(),
                    'per_page' => $investments->perPage(),
                    'last_page' => $investments->lastPage(),
                ],
                'stats' => $stats,
                'filters' => $request->only(['search', 'status', 'plan_id', 'user_id', 'date_from', 'date_to', 'sort_field', 'sort_direction']),
                'status_options' => [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'completed', 'label' => 'Completed'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                ],
                'investment_plans' => $investmentPlans,
            ]);

        } catch (\Exception $e) {
            Log::error('Investment logs index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Unable to load investment logs. Please try again.']);
        }
    }

    /**
     * @param UserInvestment $investment
     * @return Response|RedirectResponse
     */
    public function show(UserInvestment $investment): Response | RedirectResponse
    {
        try {
            $investment->load([
                'user:id,name,email,uid,avatar',
                'plan:id,name,slug,description,interest_rate,interest_type,return_type,duration_days,principal_return'
            ]);

            return Inertia::render('Admin/InvestmentLogs/Show', [
                'investment' => $investment,
            ]);

        } catch (\Exception $e) {
            Log::error('Investment show error', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Unable to load investment details. Please try again.']);
        }
    }

    /**
     * Approve pending investment
     *
     * @param UserInvestment $investment
     * @return RedirectResponse
     */
    public function approve(UserInvestment $investment): RedirectResponse
    {
        if ($investment->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending investments can be approved.']);
        }

        try {
            DB::beginTransaction();
            $investment->update([
                'status' => 'active',
                'approved_at' => now(),
                'started_at' => now(),
            ]);

            $investment->plan->increment('current_investors');
            $investment->plan->increment('current_invested', $investment->amount);

            DB::commit();

            Log::info('Investment approved', [
                'investment_id' => $investment->investment_id,
                'user_id' => $investment->user_id,
                'amount' => $investment->amount
            ]);

            return back()->with('success', 'Investment approved successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Investment approval failed', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Failed to approve investment. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @param UserInvestment $investment
     * @return RedirectResponse
     */
    public function cancel(Request $request, UserInvestment $investment): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        if (!in_array($investment->status, ['pending', 'active'])) {
            return back()->withErrors(['error' => 'Only pending or active investments can be cancelled.']);
        }

        try {
            DB::beginTransaction();

            $refundAmount = 0;
            $wallet = $investment->user->wallet;
            if ($investment->status === 'pending') {
                $refundAmount = $investment->amount;

                $wallet->increment('balance', $refundAmount);
                $wallet->decrement('total_invested', $investment->amount);

                $investment->user->transactions()->create([
                    'transaction_id' => 'TXN' . strtoupper(uniqid()),
                    'type' => 'credit',
                    'amount' => $refundAmount,
                    'fee' => 0,
                    'post_balance' => $wallet->balance,
                    'status' => 'completed',
                    'details' => 'Investment cancelled (pending) - Full refund for investment #' . $investment->investment_id . '. Reason: ' . $request->reason,
                ]);
            }

            if ($investment->status === 'active') {
                $refundAmount = $investment->remaining_principal > 0
                    ? $investment->remaining_principal
                    : $investment->amount - $investment->withdrawn_amount;

                $wallet->increment('balance', $refundAmount);

                $investment->user->transactions()->create([
                    'transaction_id' => 'TXN' . strtoupper(uniqid()),
                    'type' => 'credit',
                    'amount' => $refundAmount,
                    'fee' => 0,
                    'post_balance' => $wallet->balance,
                    'status' => 'completed',
                    'details' => 'Investment cancelled - Principal refund for investment #' . $investment->investment_id . '. Reason: ' . $request->reason,
                ]);

                $investment->plan->decrement('current_investors');
                $investment->plan->decrement('current_invested', $investment->amount);
            }

            $investment->update([
                'status' => 'cancelled',
                'notes' => $request->reason,
            ]);

            DB::commit();

            Log::info('Investment cancelled', [
                'investment_id' => $investment->investment_id,
                'user_id' => $investment->user_id,
                'refund_amount' => $refundAmount,
                'reason' => $request->reason
            ]);

            return back()->with('success', 'Investment cancelled successfully. ' . config('app.currency_symbol', '$') . number_format($refundAmount, 2) . ' refunded to user wallet.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Investment cancellation failed', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to cancel investment. Please try again.']);
        }
    }

    /**
     * @param UserInvestment $investment
     * @return RedirectResponse
     */
    /**
     * @param UserInvestment $investment
     * @return RedirectResponse
     */
    public function complete(UserInvestment $investment): RedirectResponse
    {
        if ($investment->status !== 'active') {
            return back()->withErrors(['error' => 'Only active investments can be completed.']);
        }

        try {
            DB::beginTransaction();

            $wallet = $investment->user->wallet;
            $principalReturnAmount = 0;
            if (in_array($investment->plan->principal_return, ['yes', 'after_maturity'])) {
                $principalReturnAmount = $investment->remaining_principal > 0
                    ? $investment->remaining_principal
                    : $investment->amount - $investment->withdrawn_amount;

                if ($principalReturnAmount > 0) {
                    $wallet->increment('balance', $principalReturnAmount);

                    $investment->user->transactions()->create([
                        'transaction_id' => 'TXN' . strtoupper(uniqid()),
                        'type' => 'credit',
                        'amount' => $principalReturnAmount,
                        'fee' => 0,
                        'post_balance' => $wallet->balance,
                        'status' => 'completed',
                        'details' => json_encode([
                            'type' => 'investment_principal_return',
                            'investment_id' => $investment->investment_id,
                            'plan_name' => $investment->plan->name,
                            'description' => 'Principal return for completed investment #' . $investment->investment_id
                        ]),
                    ]);
                }
            }

            $investment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'remaining_principal' => 0,
            ]);

            $investment->plan->increment('total_completed_investments');
            $investment->plan->increment('total_profit_paid', $investment->earned_amount);

            if ($principalReturnAmount > 0) {
                $investment->plan->decrement('current_investors');
                $investment->plan->decrement('current_invested', $investment->amount);
            }

            EmailTemplateService::sendTemplateEmail('investment_completed', $investment->user, [
                'user_name' => e($investment->user->name),
                'plan_name' => e($investment->plan->name),
                'amount' => round($investment->amount, 2),
                'investment_id' => e($investment->investment_id),
                'expected_return' => round($investment->expected_return, 2),
                'earned_amount' => round($investment->earned_amount, 2),
                'maturity_date' => $investment->maturity_date->format('M d, Y'),
                'completed_at' => $investment->completed_at->format('M d, Y'),
                'principal_returned' => round($principalReturnAmount, 2),
            ]);

            DB::commit();

            Log::info('Investment completed', [
                'investment_id' => $investment->investment_id,
                'user_id' => $investment->user_id,
                'principal_returned' => $principalReturnAmount,
                'total_earned' => $investment->earned_amount
            ]);

            $message = 'Investment completed successfully.';
            if ($principalReturnAmount > 0) {
                $message .= ' Principal amount of ' . config('app.currency_symbol', '$') . number_format($principalReturnAmount, 2) . ' has been returned to user wallet.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Investment completion failed', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to complete investment. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30));
            $dateTo = $request->get('date_to', now());

            $stats = [
                'daily_investments' => UserInvestment::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total_amount')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),

                'plan_wise_distribution' => UserInvestment::with('plan:id,name')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('plan_id, COUNT(*) as count, SUM(amount) as total_amount')
                    ->groupBy('plan_id')
                    ->get(),

                'status_distribution' => UserInvestment::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
                    ->groupBy('status')
                    ->get(),

                'principal_return_stats' => UserInvestment::with('plan:id,name,principal_return')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where('status', 'completed')
                    ->get()
                    ->groupBy('plan.principal_return')
                    ->map(function ($group) {
                        return [
                            'count' => $group->count(),
                            'total_amount' => $group->sum('amount'),
                        ];
                    }),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Investment statistics error', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to fetch statistics'], 500);
        }
    }
}

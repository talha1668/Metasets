<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserStake;
use App\Models\User;
use App\Models\StakingPool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StakingLogController extends Controller
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
            'status' => 'nullable|in:active,completed,cancelled',
            'pool_id' => 'nullable|integer|exists:staking_pools,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_field' => 'nullable|in:stake_id,stake_amount,total_rewards,apy_rate,status,staked_at,unlock_at,created_at',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $status = $request->get('status');
            $poolId = $request->get('pool_id');
            $userId = $request->get('user_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $query = UserStake::with(['user:id,name,email,uid', 'pool:id,name,token_symbol,apy_rate,lock_days'])
                ->select('user_stakes.*');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('stake_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('uid', 'like', "%{$search}%");
                        })
                        ->orWhereHas('pool', function ($poolQuery) use ($search) {
                            $poolQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('token_symbol', 'like', "%{$search}%");
                        });
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($poolId) {
                $query->where('pool_id', $poolId);
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

            $stakes = $query->orderBy($sortField, $sortDirection)
                ->paginate($perPage);

            $stats = [
                'total_stakes' => UserStake::count(),
                'active_stakes' => UserStake::where('status', 'active')->count(),
                'completed_stakes' => UserStake::where('status', 'completed')->count(),
                'cancelled_stakes' => UserStake::where('status', 'cancelled')->count(),
                'total_staked_amount' => UserStake::whereIn('status', ['active', 'completed'])->sum('stake_amount'),
                'total_rewards_paid' => UserStake::whereIn('status', ['active', 'completed'])->sum('total_rewards'),
                'active_stake_balance' => UserStake::where('status', 'active')->sum('current_balance'),
                'average_apy' => UserStake::where('status', 'active')->avg('apy_rate'),
            ];

            $stakingPools = StakingPool::select('id', 'name', 'token_symbol')
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($pool) {
                    return [
                        'value' => $pool->id,
                        'label' => $pool->name . ' (' . $pool->token_symbol . ')'
                    ];
                });

            return Inertia::render('Admin/StakingLogs/Index', [
                'stakes' => $stakes->items(),
                'meta' => [
                    'total' => $stakes->total(),
                    'current_page' => $stakes->currentPage(),
                    'per_page' => $stakes->perPage(),
                    'last_page' => $stakes->lastPage(),
                ],
                'stats' => $stats,
                'filters' => $request->only(['search', 'status', 'pool_id', 'user_id', 'date_from', 'date_to', 'sort_field', 'sort_direction']),
                'status_options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'completed', 'label' => 'Completed'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                ],
                'staking_pools' => $stakingPools,
            ]);

        } catch (\Exception $e) {
            Log::error('Staking logs index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Unable to load staking logs. Please try again.']);
        }
    }

    /**
     * @param UserStake $stake
     * @return Response|RedirectResponse
     */
    public function show(UserStake $stake): Response | RedirectResponse
    {
        try {
            $stake->load([
                'user:id,name,email,uid,avatar',
                'pool:id,name,token_symbol,apy_rate,lock_days,description'
            ]);

            return Inertia::render('Admin/StakingLogs/Show', [
                'stake' => $stake,
            ]);

        } catch (\Exception $e) {
            Log::error('Stake show error', [
                'stake_id' => $stake->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Unable to load stake details. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @param UserStake $stake
     * @return RedirectResponse
     */
    public function cancel(Request $request, UserStake $stake): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        if ($stake->status !== 'active') {
            return back()->withErrors(['error' => 'Only active stakes can be cancelled.']);
        }

        try {
            DB::beginTransaction();

            $wallet = $stake->user->wallet;
            $refundAmount = $stake->current_balance;
            $wallet->increment('balance', $refundAmount);
            $stake->user->transactions()->create([
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'type' => 'credit',
                'amount' => $refundAmount,
                'fee' => 0,
                'post_balance' => $wallet->balance,
                'status' => 'completed',
                'details' => 'Stake cancelled - Refund for stake #' . $stake->stake_id . ' (' . $request->reason . ')',
            ]);

            $stake->pool->decrement('current_staked', $stake->stake_amount);
            $stake->update([
                'status' => 'cancelled',
            ]);

            DB::commit();
            Log::info('Stake cancelled', [
                'stake_id' => $stake->stake_id,
                'user_id' => $stake->user_id,
                'refund_amount' => $refundAmount,
                'reason' => $request->reason
            ]);

            return back()->with('success', 'Stake cancelled successfully. ' . config('app.currency_symbol', '$') . number_format($refundAmount, 2) . ' refunded to user wallet.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stake cancellation failed', [
                'stake_id' => $stake->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to cancel stake. Please try again.']);
        }
    }

    /**
     * @param UserStake $stake
     * @return RedirectResponse
     */
    public function complete(UserStake $stake): RedirectResponse
    {
        if ($stake->status !== 'active') {
            return back()->withErrors(['error' => 'Only active stakes can be completed.']);
        }

        try {
            DB::beginTransaction();

            $wallet = $stake->user->wallet;
            $returnAmount = $stake->current_balance;
            $wallet->increment('balance', $returnAmount);
            $stake->user->transactions()->create([
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'type' => 'credit',
                'amount' => $returnAmount,
                'fee' => 0,
                'post_balance' => $wallet->balance,
                'status' => 'completed',
                'details' => 'Stake completed - Principal and rewards for stake #' . $stake->stake_id,
            ]);

            $stake->pool->decrement('current_staked', $stake->stake_amount);
            $stake->update([
                'status' => 'completed',
                'current_balance' => 0,
            ]);

            DB::commit();
            Log::info('Stake completed', [
                'stake_id' => $stake->stake_id,
                'user_id' => $stake->user_id,
                'return_amount' => $returnAmount,
                'principal' => $stake->stake_amount,
                'total_rewards' => $stake->total_rewards
            ]);

            return back()->with('success', 'Stake completed successfully. ' . config('app.currency_symbol', '$') . number_format($returnAmount, 2) . ' (principal + rewards) has been returned to user wallet.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stake completion failed', [
                'stake_id' => $stake->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to complete stake. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @param UserStake $stake
     * @return RedirectResponse
     */
    public function addRewards(Request $request, UserStake $stake): RedirectResponse
    {
        $request->validate([
            'reward_amount' => 'required|numeric|min:0.01|max:999999999',
            'note' => 'nullable|string|max:500'
        ]);

        if ($stake->status !== 'active') {
            return back()->withErrors(['error' => 'Only active stakes can receive rewards.']);
        }

        try {
            DB::beginTransaction();

            $rewardAmount = $request->reward_amount;
            $stake->increment('total_rewards', $rewardAmount);
            $stake->increment('current_balance', $rewardAmount);

            $stake->rewards()->create([
                'user_id' => $stake->user_id,
                'reward_amount' => $rewardAmount,
                'reward_date' => now()->toDateString(),
                'status' => 'paid',
            ]);

            $stake->user->transactions()->create([
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'type' => 'staking_reward',
                'amount' => $rewardAmount,
                'fee' => 0,
                'post_balance' => $stake->user->wallet->balance,
                'status' => 'completed',
                'details' => 'Staking reward added for stake #' . $stake->stake_id . ' - ' . $request->note,
            ]);

            DB::commit();
            Log::info('Staking rewards added', [
                'stake_id' => $stake->stake_id,
                'user_id' => $stake->user_id,
                'reward_amount' => $rewardAmount,
                'note' => $request->note
            ]);

            return back()->with('success', 'Rewards of ' . config('app.currency_symbol', '$') . number_format($rewardAmount, 2) . ' added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Add staking rewards failed', [
                'stake_id' => $stake->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to add rewards. Please try again.']);
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
                'daily_stakes' => UserStake::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(stake_amount) as total_amount')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),

                'pool_wise_distribution' => UserStake::with('pool:id,name')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('pool_id, COUNT(*) as count, SUM(stake_amount) as total_amount, SUM(total_rewards) as total_rewards')
                    ->groupBy('pool_id')
                    ->get(),

                'status_distribution' => UserStake::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('status, COUNT(*) as count, SUM(stake_amount) as total_amount, SUM(total_rewards) as total_rewards')
                    ->groupBy('status')
                    ->get(),

                'rewards_summary' => UserStake::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('
                        COUNT(*) as total_stakes,
                        SUM(total_rewards) as total_rewards_paid,
                        AVG(apy_rate) as average_apy,
                        SUM(CASE WHEN auto_compound = 1 THEN 1 ELSE 0 END) as auto_compound_count
                    ')
                    ->first(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Staking statistics error', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to fetch statistics'], 500);
        }
    }
}

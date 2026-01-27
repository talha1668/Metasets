<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StakingReward;
use App\Models\StakingPool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StakingRewardController extends Controller
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
            'status' => 'nullable|in:pending,paid,compounded',
            'stake_id' => 'nullable|integer|exists:user_stakes,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'pool_id' => 'nullable|integer|exists:staking_pools,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_field' => 'nullable|in:id,reward_amount,reward_date,status,created_at',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $status = $request->get('status');
            $stakeId = $request->get('stake_id');
            $userId = $request->get('user_id');
            $poolId = $request->get('pool_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $query = StakingReward::with([
                'user:id,name,email,uid',
                'stake:id,stake_id,stake_amount,pool_id',
                'stake.pool:id,name,token_symbol'
            ])->select('staking_rewards.*');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('uid', 'like', "%{$search}%");
                    })
                        ->orWhereHas('stake', function ($stakeQuery) use ($search) {
                            $stakeQuery->where('stake_id', 'like', "%{$search}%");
                        })
                        ->orWhereHas('stake.pool', function ($poolQuery) use ($search) {
                            $poolQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($stakeId) {
                $query->where('stake_id', $stakeId);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            if ($poolId) {
                $query->whereHas('stake', function ($stakeQuery) use ($poolId) {
                    $stakeQuery->where('pool_id', $poolId);
                });
            }

            if ($dateFrom) {
                $query->whereDate('reward_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('reward_date', '<=', $dateTo);
            }

            $rewards = $query->orderBy($sortField, $sortDirection)->paginate($perPage);
            $stats = [
                'total_rewards' => StakingReward::count(),
                'pending_rewards' => StakingReward::where('status', 'pending')->count(),
                'paid_rewards' => StakingReward::where('status', 'paid')->count(),
                'compounded_rewards' => StakingReward::where('status', 'compounded')->count(),
                'total_pending_amount' => StakingReward::where('status', 'pending')->sum('reward_amount'),
                'total_paid_amount' => StakingReward::where('status', 'paid')->sum('reward_amount'),
                'total_compounded_amount' => StakingReward::where('status', 'compounded')->sum('reward_amount'),
                'total_amount' => StakingReward::sum('reward_amount'),
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

            return Inertia::render('Admin/StakingRewards/Index', [
                'rewards' => $rewards->items(),
                'meta' => [
                    'total' => $rewards->total(),
                    'current_page' => $rewards->currentPage(),
                    'per_page' => $rewards->perPage(),
                    'last_page' => $rewards->lastPage(),
                ],
                'stats' => $stats,
                'filters' => $request->only(['search', 'status', 'stake_id', 'user_id', 'pool_id', 'date_from', 'date_to', 'sort_field', 'sort_direction']),
                'status_options' => [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'paid', 'label' => 'Paid'],
                    ['value' => 'compounded', 'label' => 'Compounded'],
                ],
                'staking_pools' => $stakingPools,
            ]);

        } catch (\Exception $e) {
            Log::error('Staking rewards index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Unable to load staking rewards. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function processRewards(Request $request): RedirectResponse
    {
        $request->validate([
            'reward_ids' => 'required|array|min:1',
            'reward_ids.*' => 'required|integer|exists:staking_rewards,id',
        ]);

        try {
            DB::beginTransaction();

            $rewards = StakingReward::with(['user.wallet', 'stake'])
                ->whereIn('id', $request->reward_ids)
                ->where('status', 'pending')
                ->get();

            if ($rewards->isEmpty()) {
                return back()->withErrors(['error' => 'No pending rewards found to process.']);
            }

            $processedCount = 0;
            $totalAmount = 0;

            foreach ($rewards as $reward) {
                $wallet = $reward->user->wallet;
                $wallet->increment('balance', $reward->reward_amount);
                $wallet->increment('total_earnings', $reward->reward_amount);

                $reward->user->transactions()->create([
                    'transaction_id' => 'TXN' . strtoupper(uniqid()),
                    'type' => 'staking_reward',
                    'amount' => $reward->reward_amount,
                    'fee' => 0,
                    'post_balance' => $wallet->balance,
                    'status' => 'completed',
                    'details' => "Staking reward payment - Stake ID: {$reward->stake->stake_id}, Pool: " . ($reward->stake->pool->name ?? 'N/A') . ", Date: {$reward->reward_date}",
                ]);

                $reward->update(['status' => 'paid']);
                $processedCount++;
                $totalAmount += $reward->reward_amount;
            }

            DB::commit();

            Log::info('Staking rewards processed', [
                'processed_by' => auth()->id(),
                'count' => $processedCount,
                'total_amount' => $totalAmount
            ]);

            return back()->with('success', "{$processedCount} reward(s) processed successfully. Total: " . config('app.currency_symbol', '$') . number_format($totalAmount, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Process rewards failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to process rewards. Please try again.']);
        }
    }

    /**
     * @return RedirectResponse
     */
    public function bulkProcess(): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $rewards = StakingReward::with(['user.wallet', 'stake.pool'])
                ->where('status', 'pending')
                ->where('reward_date', '<=', now())
                ->get();

            if ($rewards->isEmpty()) {
                return back()->with('info', 'No pending rewards to process.');
            }

            $processedCount = 0;
            $totalAmount = 0;
            $failedCount = 0;

            foreach ($rewards as $reward) {
                try {
                    $wallet = $reward->user->wallet;
                    if (!$wallet) {
                        $failedCount++;
                        Log::warning('Wallet not found for user', ['user_id' => $reward->user_id]);
                        continue;
                    }

                    $wallet->increment('balance', $reward->reward_amount);
                    $wallet->increment('total_earnings', $reward->reward_amount);
                    $reward->user->transactions()->create([
                        'transaction_id' => 'TXN' . strtoupper(uniqid()),
                        'type' => 'staking_reward',
                        'amount' => $reward->reward_amount,
                        'fee' => 0,
                        'post_balance' => $wallet->balance,
                        'status' => 'completed',
                        'details' => 'Staking reward payment (bulk processed) for stake ' . $reward->stake->stake_id,
                    ]);

                    $reward->update(['status' => 'paid']);
                    $processedCount++;
                    $totalAmount += $reward->reward_amount;

                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Failed to process individual reward', [
                        'reward_id' => $reward->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();
            Log::info('Bulk staking rewards processed', [
                'processed_by' => auth()->id(),
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'total_amount' => $totalAmount
            ]);

            $message = "{$processedCount} reward(s) processed successfully. Total: " . config('app.currency_symbol', '$') . number_format($totalAmount, 2);
            if ($failedCount > 0) {
                $message .= " ({$failedCount} failed)";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk process rewards failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to process rewards. Please try again.']);
        }
    }

    /**
     * @param StakingReward $reward
     * @return RedirectResponse
     */
    public function destroy(StakingReward $reward): RedirectResponse
    {
        if ($reward->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending rewards can be deleted.']);
        }

        try {
            DB::beginTransaction();

            $rewardAmount = $reward->reward_amount;
            $stake = $reward->stake;
            if ($stake) {
                $stake->decrement('total_rewards', $rewardAmount);
                $stake->decrement('current_balance', $rewardAmount);
            }

            $reward->delete();
            DB::commit();
            Log::info('Staking reward deleted', [
                'reward_id' => $reward->id,
                'deleted_by' => auth()->id(),
                'amount' => $rewardAmount
            ]);

            return back()->with('success', 'Reward deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete reward failed', [
                'reward_id' => $reward->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Failed to delete reward. Please try again.']);
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
                'daily_rewards' => StakingReward::whereBetween('reward_date', [$dateFrom, $dateTo])
                    ->selectRaw('DATE(reward_date) as date, COUNT(*) as count, SUM(reward_amount) as total_amount')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),

                'pool_wise_distribution' => StakingReward::with('stake.pool')
                    ->whereBetween('reward_date', [$dateFrom, $dateTo])
                    ->get()
                    ->groupBy('stake.pool.name')
                    ->map(function ($group) {
                        return [
                            'count' => $group->count(),
                            'total_amount' => $group->sum('reward_amount'),
                        ];
                    }),

                'status_distribution' => StakingReward::whereBetween('reward_date', [$dateFrom, $dateTo])
                    ->selectRaw('status, COUNT(*) as count, SUM(reward_amount) as total_amount')
                    ->groupBy('status')
                    ->get(),

                'top_earners' => StakingReward::with('user:id,name,email')
                    ->whereBetween('reward_date', [$dateFrom, $dateTo])
                    ->where('status', '!=', 'pending')
                    ->selectRaw('user_id, COUNT(*) as reward_count, SUM(reward_amount) as total_earned')
                    ->groupBy('user_id')
                    ->orderByDesc('total_earned')
                    ->limit(10)
                    ->get(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Staking rewards statistics error', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to fetch statistics'], 500);
        }
    }
}

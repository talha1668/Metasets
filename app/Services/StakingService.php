<?php
namespace App\Services;

use App\Models\StakingPool;
use App\Models\UserStake;
use App\Models\StakingReward;
use App\Models\Transaction;
use App\Models\User;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\PoolCapacityException;
use App\Exceptions\StakeStillLockedException;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StakingService
{
    /**
     * @return Collection
     */
    /**
     * @return Collection
     */
    public function getActivePools(): Collection
    {
        return StakingPool::where('status', 'active')
            ->get()
            ->map(function ($pool) {
                $currentMonthStaked = UserStake::where('pool_id', $pool->id)
                    ->where('status', 'active')
                    ->where('created_at', '>=', Carbon::now()->startOfMonth())
                    ->sum('stake_amount');

                $previousMonthStaked = UserStake::where('pool_id', $pool->id)
                    ->where('status', 'active')
                    ->whereBetween('created_at', [
                        Carbon::now()->subMonth()->startOfMonth(),
                        Carbon::now()->subMonth()->endOfMonth()
                    ])
                    ->sum('stake_amount');

                $stakingGrowth = 0;
                if ($previousMonthStaked > 0) {
                    $stakingGrowth = (($currentMonthStaked - $previousMonthStaked) / $previousMonthStaked) * 100;
                } elseif ($currentMonthStaked > 0) {
                    $stakingGrowth = 100;
                }

                return [
                    'id' => $pool->id,
                    'name' => $pool->name,
                    'token_symbol' => $pool->token_symbol,
                    'description' => $pool->description,
                    'min_stake_amount' => (float) $pool->min_stake_amount,
                    'max_stake_amount' => $pool->max_stake_amount ? (float) $pool->max_stake_amount : null,
                    'apy_rate' => (float) $pool->apy_rate,
                    'lock_days' => $pool->lock_days,
                    'total_pool_size' => (float) $pool->total_pool_size,
                    'current_staked' => (float) $pool->current_staked,
                    'auto_compound' => (bool) $pool->auto_compound,
                    'status' => $pool->status,
                    'risk_level' => $this->calculatePoolRiskLevel($pool),
                    'utilization_percentage' => $this->calculateUtilization($pool),
                    'staking_growth_this_month' => round($stakingGrowth, 2),
                ];
            });
    }

    /**
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getUserStakes(int $userId, int $limit = 50): Collection
    {
        return UserStake::with('pool')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($stake) {
                return [
                    'id' => $stake->id,
                    'stake_id' => $stake->stake_id,
                    'pool_name' => $stake->pool->name,
                    'stake_amount' => (float) $stake->stake_amount,
                    'current_balance' => (float) $stake->current_balance,
                    'total_rewards' => (float) $stake->total_rewards,
                    'apy_rate' => (float) $stake->apy_rate,
                    'status' => $stake->status,
                    'staked_at' => $stake->staked_at->toISOString(),
                    'unlock_at' => $stake->unlock_at?->toISOString(),
                    'auto_compound' => (bool) $stake->auto_compound,
                    'is_locked' => $this->isStakeLocked($stake),
                    'days_remaining' => $this->getDaysRemaining($stake),
                ];
            });
    }

    /**
     * @param User $user
     * @param StakingPool $pool
     * @param float $amount
     * @param bool $autoCompound
     * @return UserStake
     * @throws InsufficientBalanceException
     * @throws PoolCapacityException
     * @throws Exception
     */
    public function createStake(User $user, StakingPool $pool, float $amount, bool $autoCompound = false): UserStake
    {
        $wallet = $user->wallet;
        if ($wallet->balance < $amount) {
            throw new InsufficientBalanceException(
                __('Insufficient balance. Required: :required, Available: :available', [
                    'required' => config('app.currency_symbol', '$') . number_format($amount, 2),
                    'available' => config('app.currency_symbol', '$') . number_format($wallet->balance, 2),
                ])
            );
        }

        if ($pool->total_pool_size > 0 && ($pool->current_staked + $amount) > $pool->total_pool_size) {
            throw new PoolCapacityException(
                __('Stake amount exceeds pool capacity. Available: :available', [
                    'available' => config('app.currency_symbol', '$') . number_format($pool->total_pool_size - $pool->current_staked, 2),
                ])
            );
        }

        DB::beginTransaction();
        try {
            $unlockAt = $pool->lock_days > 0 ? Carbon::now()->addDays($pool->lock_days) : null;
            $stake = UserStake::create([
                'stake_id' => $this->generateStakeId(),
                'user_id' => $user->id,
                'pool_id' => $pool->id,
                'stake_amount' => $amount,
                'current_balance' => $amount,
                'total_rewards' => 0,
                'apy_rate' => $pool->apy_rate,
                'staked_at' => now(),
                'unlock_at' => $unlockAt,
                'status' => 'active',
                'auto_compound' => $autoCompound,
            ]);

            $wallet->decrement('balance', $amount);
            $wallet->update(['last_activity' => now()]);
            $pool->increment('current_staked', $amount);

            $this->createTransaction($user, 'stake', $amount, $wallet->balance,"Staked {$amount} in {$pool->name} pool (ID: {$stake->stake_id}) at {$pool->apy_rate}% APY for {$pool->lock_days} days" .
                ($autoCompound ? " with auto-compound enabled" : ""));

            DB::commit();
            try {
                EmailTemplateService::sendTemplateEmail('staking_created', $user, [
                    'user_name' => e($user->name),
                    'pool_name' => e($pool->name),
                    'stake_amount' => round($amount, 2),
                    'apy_rate' => round($pool->apy_rate, 2),
                    'lock_days' => $pool->lock_days,
                    'stake_id' => e($stake->stake_id),
                    'staked_at' => $stake->staked_at->format('M d, Y h:i A'),
                    'unlock_at' => $unlockAt ? $unlockAt->format('M d, Y h:i A') : 'Flexible',
                    'auto_compound' => $autoCompound ? 'Enabled' : 'Disabled',
                ]);
            } catch (Exception $e) {
                Log::error('Failed to send staking confirmation email', [
                    'user_id' => $user->id,
                    'stake_id' => $stake->id,
                    'error' => $e->getMessage()
                ]);
            }

            return $stake;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Stake creation failed in service', [
                'user_id' => $user->id,
                'pool_id' => $pool->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param UserStake $stake
     * @return void
     * @throws StakeStillLockedException
     * @throws Exception
     */
    public function unstake(UserStake $stake): void
    {
        if ($stake->unlock_at && Carbon::now()->lt($stake->unlock_at)) {
            throw new StakeStillLockedException(
                __('Stake is still locked until :date', [
                    'date' => $stake->unlock_at->format('Y-m-d H:i')
                ]),
                $stake->unlock_at
            );
        }

        DB::beginTransaction();
        try {
            $user = $stake->user;
            $wallet = $user->wallet;
            $pool = $stake->pool;

            $totalAmount = $stake->current_balance;
            $wallet->increment('balance', $totalAmount);
            $wallet->increment('total_earnings', $stake->total_rewards);
            $wallet->update(['last_activity' => now()]);
            $stake->update(['status' => 'completed']);

            $pool->decrement('current_staked', $stake->stake_amount);
            $this->createTransaction($user, 'unstake', $totalAmount, $wallet->balance, "Unstaked from {$pool->name} pool (ID: {$stake->stake_id}). Principal: " .
                config('app.currency_symbol', '$') . number_format($stake->stake_amount, 2) .
                ", Rewards: " . config('app.currency_symbol', '$') . number_format($stake->total_rewards, 2));

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Unstaking failed in service', [
                'stake_id' => $stake->id,
                'user_id' => $stake->user_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param UserStake $stake
     * @return float
     * @throws Exception
     */
    public function claimRewards(UserStake $stake): float
    {
        DB::beginTransaction();
        try {
            $user = $stake->user;
            $wallet = $user->wallet;
            $rewardAmount = $stake->total_rewards;

            $wallet->increment('balance', $rewardAmount);
            $wallet->increment('total_earnings', $rewardAmount);
            $wallet->update(['last_activity' => now()]);

            $stake->decrement('current_balance', $rewardAmount);
            $stake->update(['total_rewards' => 0]);

            StakingReward::create([
                'user_id' => $user->id,
                'stake_id' => $stake->id,
                'reward_amount' => $rewardAmount,
                'reward_date' => now()->toDateString(),
                'status' => 'paid',
            ]);

            $this->createTransaction($user, 'staking_reward', $rewardAmount, $wallet->balance, "Claimed staking rewards from {$stake->pool->name} pool (ID: {$stake->stake_id})");

            DB::commit();

            return $rewardAmount;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Reward claim failed in service', [
                'stake_id' => $stake->id,
                'user_id' => $stake->user_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param UserStake $stake
     * @return Collection
     */
    public function getRewardHistory(UserStake $stake): Collection
    {
        return StakingReward::where('stake_id', $stake->id)
            ->orderBy('reward_date', 'desc')
            ->get()
            ->map(function ($reward) {
                return [
                    'id' => $reward->id,
                    'reward_amount' => (float) $reward->reward_amount,
                    'reward_date' => $reward->reward_date,
                    'status' => $reward->status,
                    'created_at' => $reward->created_at->toISOString(),
                ];
            });
    }

    /**
     * @return string
     */
    protected function generateStakeId(): string
    {
        do {
            $id = 'STK' . strtoupper(Str::random(12));
        } while (UserStake::where('stake_id', $id)->exists());

        return $id;
    }

    /**
     * @param User $user
     * @param string $type
     * @param float $amount
     * @param float $postBalance
     * @param string $details
     * @return Transaction
     */
    protected function createTransaction(User $user, string $type, float $amount, float $postBalance, string $details): Transaction
    {
        return Transaction::create([
            'transaction_id' => 'TXN' . strtoupper(Str::random(12)),
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'fee' => 0,
            'post_balance' => $postBalance,
            'status' => 'completed',
            'details' => $details,
        ]);
    }

    /**
     * @param StakingPool $pool
     * @return string
     */
    protected function calculatePoolRiskLevel(StakingPool $pool): string
    {
        $apy = $pool->apy_rate;

        if ($apy >= 15) {
            return 'high';
        } elseif ($apy >= 10) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * @param StakingPool $pool
     * @return float
     */
    protected function calculateUtilization(StakingPool $pool): float
    {
        if ($pool->total_pool_size <= 0) {
            return 0;
        }

        return round(($pool->current_staked / $pool->total_pool_size) * 100, 2);
    }

    /**
     * @param UserStake $stake
     * @return bool
     */
    protected function isStakeLocked(UserStake $stake): bool
    {
        if (!$stake->unlock_at) {
            return false;
        }

        return Carbon::now()->lt($stake->unlock_at);
    }

    /**
     * @param UserStake $stake
     * @return int
     */
    protected function getDaysRemaining(UserStake $stake): int
    {
        if (!$stake->unlock_at || !$this->isStakeLocked($stake)) {
            return 0;
        }

        return Carbon::now()->diffInDays($stake->unlock_at);
    }
}

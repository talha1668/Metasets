<?php

namespace App\Console\Commands;

use App\Models\StakingReward;
use App\Models\Transaction;
use App\Models\UserStake;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessStakingRewards extends Command
{
    protected $signature = 'staking:process-rewards
                          {--stake_id= : Process specific stake}
                          {--user_id= : Process rewards for specific user}
                          {--dry-run : Run without making changes}';

    protected $description = 'Calculate and distribute daily staking rewards';

    public function handle(): int
    {
        $this->info('Starting staking rewards processing...');
        $startTime = now();

        $dryRun = $this->option('dry-run');
        $stakeId = $this->option('stake_id');
        $userId = $this->option('user_id');

        $query = UserStake::with(['pool', 'user.wallet'])
            ->where('status', 'active');

        if ($stakeId) {
            $query->where('stake_id', $stakeId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stakes = $query->get();

        if ($stakes->isEmpty()) {
            $this->info('No active stakes found for processing.');
            return 0;
        }

        $this->info("Found {$stakes->count()} stake(s) to process.");

        $processed = 0;
        $failed = 0;
        $totalRewards = 0;

        $progressBar = $this->output->createProgressBar($stakes->count());
        $progressBar->start();

        foreach ($stakes as $stake) {
            try {
                $dailyReward = $this->calculateDailyReward($stake);

                if ($dryRun) {
                    $this->line("\n[DRY RUN] Would process: {$stake->stake_id}");
                    $this->line("  User: {$stake->user->name} (ID: {$stake->user_id})");
                    $this->line("  Daily Reward: {$dailyReward}");
                    $this->line("  Auto Compound: " . ($stake->auto_compound ? 'Yes' : 'No'));
                    $processed++;
                } else {
                    $result = $this->processReward($stake, $dailyReward);
                    if ($result) {
                        $processed++;
                        $totalRewards += $dailyReward;
                    } else {
                        $failed++;
                        $this->error("Failed to process stake: {$stake->stake_id}");
                    }
                }

                $progressBar->advance();
            } catch (\Exception $e) {
                $failed++;
                $this->error("Exception for stake {$stake->stake_id}: {$e->getMessage()}");
                Log::error('Staking reward processing failed', [
                    'stake_id' => $stake->stake_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $executionTime = now()->diffInSeconds($startTime);

        $this->info("Processing completed in {$executionTime} seconds!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processed', $processed],
                ['Failed', $failed],
                ['Total Rewards', $dryRun ? 'N/A (Dry Run)' : '$' . number_format($totalRewards, 2)],
                ['Execution Time', "{$executionTime}s"],
            ]
        );

        return 0;
    }

    protected function calculateDailyReward(UserStake $stake): float
    {
        $apyDecimal = $stake->apy_rate / 100;
        $dailyRate = pow(1 + $apyDecimal, 1/365) - 1;

        return $stake->current_balance * $dailyRate;
    }

    protected function processReward(UserStake $stake, float $rewardAmount): bool
    {
        try {
            return DB::transaction(function () use ($stake, $rewardAmount) {
                $existingReward = StakingReward::where('stake_id', $stake->id)
                    ->where('reward_date', today())
                    ->first();

                if ($existingReward) {
                    Log::info('Reward already processed today', [
                        'stake_id' => $stake->stake_id,
                        'date' => today()
                    ]);
                    return false;
                }

                $wallet = $stake->user->wallet;

                if (!$wallet) {
                    Log::error('Wallet not found for user', [
                        'user_id' => $stake->user_id,
                        'stake_id' => $stake->stake_id
                    ]);
                    throw new \Exception('User wallet not found');
                }

                if ($stake->auto_compound) {
                    $stake->increment('current_balance', $rewardAmount);
                    $stake->increment('total_rewards', $rewardAmount);

                    StakingReward::create([
                        'user_id' => $stake->user_id,
                        'stake_id' => $stake->id,
                        'reward_amount' => $rewardAmount,
                        'reward_date' => today(),
                        'status' => 'compounded',
                    ]);

                    $this->createTransaction(
                        $stake->user_id,
                        'staking_reward',
                        $rewardAmount,
                        $wallet->balance,
                        "Auto-compounded staking reward for {$stake->pool->name} - Stake ID: {$stake->stake_id}"
                    );
                } else {
                    $stake->increment('total_rewards', $rewardAmount);

                    StakingReward::create([
                        'user_id' => $stake->user_id,
                        'stake_id' => $stake->id,
                        'reward_amount' => $rewardAmount,
                        'reward_date' => today(),
                        'status' => 'pending',
                    ]);
                }

                $stake->pool->increment('current_staked', $stake->auto_compound ? $rewardAmount : 0);

                Log::info('Staking reward processed successfully', [
                    'stake_id' => $stake->stake_id,
                    'reward_amount' => $rewardAmount,
                    'status' => $stake->auto_compound ? 'compounded' : 'pending'
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Transaction failed in processReward', [
                'stake_id' => $stake->stake_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function createTransaction(int $userId, string $type, float $amount, float $postBalance, string $details): void
    {
        Transaction::create([
            'transaction_id' => 'TXN' . strtoupper(Str::random(12)),
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'fee' => 0,
            'post_balance' => $postBalance,
            'status' => 'completed',
            'details' => $details,
        ]);
    }
}

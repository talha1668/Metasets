<?php

namespace App\Console\Commands;

use App\Models\UserStake;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UnlockStakes extends Command
{
    protected $signature = 'staking:unlock-stakes
                          {--notify : Send notifications to users}
                          {--dry-run : Run without making changes}';

    protected $description = 'Check and unlock stakes that have reached their unlock date';

    public function handle(): int
    {
        $this->info('Checking for stakes ready to unlock...');
        $startTime = now();

        $dryRun = $this->option('dry-run');
        $notify = $this->option('notify');

        $unlockedStakes = UserStake::with(['pool', 'user'])
            ->where('status', 'active')
            ->where('unlock_at', '<=', now())
            ->whereNotNull('unlock_at')
            ->get();

        if ($unlockedStakes->isEmpty()) {
            $this->info('No stakes found ready to unlock.');
            return 0;
        }

        $this->info("Found {$unlockedStakes->count()} stake(s) ready to unlock.");

        $unlocked = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($unlockedStakes->count());
        $progressBar->start();

        foreach ($unlockedStakes as $stake) {
            try {
                if ($dryRun) {
                    $this->line("\n[DRY RUN] Would unlock: {$stake->stake_id}");
                    $this->line("  User: {$stake->user->name}");
                    $this->line("  Pool: {$stake->pool->name}");
                    $this->line("  Unlock Date: {$stake->unlock_at}");
                    $this->line("  Current Balance: {$stake->current_balance}");
                    $unlocked++;
                } else {
                    $stake->update(['unlock_at' => null]);

                    if ($notify) {
                        Log::info("Stake unlocked notification", [
                            'user_id' => $stake->user_id,
                            'stake_id' => $stake->stake_id
                        ]);
                    }

                    $unlocked++;
                }

                $progressBar->advance();
            } catch (\Exception $e) {
                $failed++;
                Log::error('Stake unlock failed', [
                    'stake_id' => $stake->stake_id,
                    'error' => $e->getMessage()
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
                ['Total Found', $unlockedStakes->count()],
                ['Unlocked', $unlocked],
                ['Failed', $failed],
                ['Notifications Sent', $notify && !$dryRun ? $unlocked : 'N/A'],
                ['Execution Time', "{$executionTime}s"],
            ]
        );

        return 0;
    }
}

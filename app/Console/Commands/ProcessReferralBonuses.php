<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ReferralBonusService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProcessReferralBonuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:process-bonuses
                            {--user= : Process bonuses for specific user ID}
                            {--force : Force process even if already claimed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and credit referral bonuses to eligible users';

    protected ReferralBonusService $bonusService;

    /**
     * Create a new command instance.
     */
    public function __construct(ReferralBonusService $bonusService)
    {
        parent::__construct();
        $this->bonusService = $bonusService;
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting referral bonus processing...');
        $this->newLine();

        $userId = $this->option('user');

        try {
            if ($userId) {
                $this->processSpecificUser($userId);
            } else {
                $this->processAllUsers();
            }

            $this->newLine();
            $this->info('Referral bonus processing completed successfully!');
            return CommandAlias::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return CommandAlias::FAILURE;
        }
    }

    /**
     * @param int $userId
     * @return void
     */
    private function processSpecificUser(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return;
        }

        $this->info("Processing bonuses for: {$user->name} (ID: {$user->id})");
        $this->newLine();

        $result = $this->bonusService->processUserBonuses($user);
        $this->displayResults($result, true);
    }

    /**
     * Process bonuses for all users
     *
     * @return void
     */
    private function processAllUsers(): void
    {
        $this->info('Processing bonuses for all eligible users...');
        $this->newLine();

        $result = $this->bonusService->processAllUsersBonuses();
        $this->info("Summary:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Users Processed', $result['total_users']],
                ['Total Bonuses Claimed', $result['total_bonuses_claimed']],
                ['Total Amount Credited', '$' . number_format($result['total_amount_credited'], 2)],
                ['Errors', count($result['errors'])]
            ]
        );

        if (!empty($result['errors'])) {
            $this->newLine();
            $this->warn('Errors encountered:');
            foreach ($result['errors'] as $error) {
                $this->error('  - ' . ($error['user_id'] ?? 'N/A') . ': ' . $error['error']);
            }
        }
    }

    /**
     * @param array $result
     * @param bool $detailed
     * @return void
     */
    private function displayResults(array $result, bool $detailed = false): void
    {
        if (empty($result['processed'])) {
            $this->warn('No new bonuses to claim at this time.');
            return;
        }

        $this->info('✨ Bonuses Claimed:');
        $this->newLine();

        $tableData = [];
        foreach ($result['processed'] as $bonus) {
            $tableData[] = [
                $bonus['bonus_name'],
                '$' . number_format($bonus['amount'], 2),
                $bonus['message'] ?? 'Claimed'
            ];
        }

        $this->table(
            ['Bonus Name', 'Amount', 'Status'],
            $tableData
        );

        if (!empty($result['errors']) && $detailed) {
            $this->newLine();
            $this->warn('Some bonuses could not be processed:');
            foreach ($result['errors'] as $error) {
                $this->error('  - ' . $error['bonus_name'] . ': ' . $error['error']);
            }
        }
    }
}

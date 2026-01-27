<?php

namespace App\Console\Commands;

use App\Models\UserInvestment;
use App\Models\Transaction;
use App\Services\EmailTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessInvestmentReturns extends Command
{
    protected $signature = 'investments:process-returns';

    protected $description = 'Process investment returns, handle matured investments, and return principal based on plan settings';

    public function handle(): int
    {
        $this->info('Starting investment returns processing...');
        $startTime = now();

        $investments = UserInvestment::with(['plan', 'user.wallet'])
            ->where('status', 'active')
            ->where('next_return_at', '<=', now())
            ->get();

        if ($investments->isEmpty()) {
            $this->info('No investments found for processing.');
            return 0;
        }

        $this->info("Found {$investments->count()} investment(s) to process.");

        $processed = 0;
        $failed = 0;
        $totalProfitPaid = 0;
        $totalPrincipalReturned = 0;
        $completedCount = 0;

        $progressBar = $this->output->createProgressBar($investments->count());
        $progressBar->start();

        foreach ($investments as $investment) {
            try {
                $result = $this->processReturn($investment);
                if ($result['success']) {
                    $processed++;
                    $totalProfitPaid += $result['profit_paid'];
                    $totalPrincipalReturned += $result['principal_returned'];
                    if ($result['completed']) {
                        $completedCount++;
                    }
                } else {
                    $failed++;
                }

                $progressBar->advance();
            } catch (\Exception $e) {
                $failed++;
                Log::error('Investment return processing failed', [
                    'investment_id' => $investment->investment_id,
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
                ['Investments Completed', $completedCount],
                ['Total Profit Paid', '$' . number_format($totalProfitPaid, 2)],
                ['Total Principal Returned', '$' . number_format($totalPrincipalReturned, 2)],
                ['Execution Time', "{$executionTime}s"],
            ]
        );

        return 0;
    }

    protected function processReturn(UserInvestment $investment): array
    {
        $result = [
            'success' => false,
            'profit_paid' => 0,
            'principal_returned' => 0,
            'completed' => false
        ];

        DB::transaction(function () use ($investment, &$result) {
            $wallet = $investment->user->wallet;
            $returnAmount = $investment->return_amount;

            $wallet->increment('balance', $returnAmount);
            $wallet->increment('total_earnings', $returnAmount);
            $wallet->update(['last_activity' => now()]);

            $investment->increment('earned_amount', $returnAmount);
            $investment->increment('returns_paid');

            $schedule = $investment->return_schedule ?? [];
            foreach ($schedule as &$item) {
                if ($item['cycle'] == $investment->returns_paid && $item['status'] === 'pending') {
                    $item['status'] = 'paid';
                    $item['paid_at'] = now()->toISOString();
                    break;
                }
            }
            $investment->return_schedule = $schedule;

            $isCompleted = false;
            $principalReturned = 0;

            if ($investment->returns_paid >= $investment->total_returns_expected) {
                $investment->status = 'completed';
                $investment->completed_at = now();
                $investment->next_return_at = null;
                $isCompleted = true;

                if (in_array($investment->plan->principal_return, ['yes', 'after_maturity'])) {
                    $principalAmount = $investment->amount;
                    $wallet->increment('balance', $principalAmount);

                    $this->createTransaction(
                        $investment->user_id,
                        'credit',
                        $principalAmount,
                        $wallet->balance,
                        "Principal return from investment {$investment->investment_id} - Plan: {$investment->plan->name}"
                    );

                    $principalReturned = $principalAmount;
                }

                $investment->plan->increment('total_completed_investments');
                $investment->plan->decrement('current_investors');
                $investment->plan->decrement('current_invested', $investment->amount);
            } else {
                $investment->next_return_at = $this->getNextReturnDate(
                    $investment->next_return_at,
                    $investment->plan->return_type
                );
            }

            $investment->save();
            $this->createTransaction(
                $investment->user_id,
                'credit',
                $returnAmount,
                $wallet->balance - $principalReturned,
                "Investment return from {$investment->plan->name} - Cycle {$investment->returns_paid}/{$investment->total_returns_expected}"
            );

            $investment->plan->increment('total_profit_paid', $returnAmount);
            if ($isCompleted) {
                $this->sendCompletionEmail($investment);
            }

            $result['success'] = true;
            $result['profit_paid'] = $returnAmount;
            $result['principal_returned'] = $principalReturned;
            $result['completed'] = $isCompleted;
        });

        return $result;
    }

    protected function getNextReturnDate(Carbon $from, string $returnType): Carbon
    {
        return match($returnType) {
            'hourly' => $from->copy()->addHour(),
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addMonth(),
            default => $from->copy()->addDay(),
        };
    }

    protected function createTransaction(int $userId, string $type, float $amount, float $postBalance, string $details): void
    {
        Transaction::create([
            'transaction_id' => 'TXN' . strtoupper(\Illuminate\Support\Str::random(12)),
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'fee' => 0,
            'post_balance' => $postBalance,
            'status' => 'completed',
            'details' => $details,
        ]);
    }

    protected function sendCompletionEmail(UserInvestment $investment): void
    {
        try {
            $user = $investment->user;
            EmailTemplateService::sendTemplateEmail('investment_completed', $user, [
                'user_name' => e($user->name),
                'plan_name' => e($investment->plan->name),
                'amount' => round($investment->amount, 2),
                'investment_id' => e($investment->investment_id),
                'expected_return' => round($investment->expected_return, 2),
                'earned_amount' => round($investment->earned_amount, 2),
                'maturity_date' => $investment->maturity_date->format('M d, Y'),
                'completed_at' => $investment->completed_at->format('M d, Y'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send investment completion email', [
                'user_id' => $investment->user_id,
                'investment_id' => $investment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

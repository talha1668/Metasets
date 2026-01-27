<?php

namespace App\Services;

use App\Models\InvestmentPlan;
use App\Models\UserInvestment;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\PlanCapacityException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvestmentService
{
    /**
     * @param ReferralService $referralService
     */
    public function __construct(protected ReferralService $referralService){

    }

    /**
     * @return Collection
     */
    /**
     * @return Collection
     */
    public function getActivePlans(): Collection
    {
        return InvestmentPlan::whereIn('status', ['active', 'coming_soon'])
            ->orderBy('featured', 'desc')
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($plan) {
                $newInvestorsThisWeek = UserInvestment::where('plan_id', $plan->id)
                    ->where('created_at', '>=', Carbon::now()->subWeek())
                    ->distinct('user_id')
                    ->count('user_id');

                $currentMonthInvested = UserInvestment::where('plan_id', $plan->id)
                    ->where('created_at', '>=', Carbon::now()->startOfMonth())
                    ->sum('amount');

                $previousMonthInvested = UserInvestment::where('plan_id', $plan->id)
                    ->whereBetween('created_at', [
                        Carbon::now()->subMonth()->startOfMonth(),
                        Carbon::now()->subMonth()->endOfMonth()
                    ])
                    ->sum('amount');

                $investmentGrowth = 0;
                if ($previousMonthInvested > 0) {
                    $investmentGrowth = (($currentMonthInvested - $previousMonthInvested) / $previousMonthInvested) * 100;
                } elseif ($currentMonthInvested > 0) {
                    $investmentGrowth = 100;
                }

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'features' => $plan->features,
                    'min_amount' => (float) $plan->min_amount,
                    'max_amount' => (float) $plan->max_amount,
                    'interest_rate' => (float) $plan->interest_rate,
                    'interest_type' => $plan->interest_type,
                    'return_type' => $plan->return_type,
                    'duration_days' => $plan->duration_days,
                    'principal_return' => $plan->principal_return,
                    'risk_level' => $plan->risk_level,
                    'color_scheme' => $plan->color_scheme,
                    'featured' => (bool) $plan->featured,
                    'status' => $plan->status,
                    'current_investors' => $plan->current_investors,
                    'max_investors' => $plan->max_investors,
                    'current_invested' => (float) $plan->current_invested,
                    'total_cap' => (float) $plan->total_cap,
                    'success_rate' => (float) $plan->success_rate,
                    'auto_reinvest_available' => (bool) $plan->auto_reinvest_available,
                    'partial_withdrawal_allowed' => (bool) $plan->partial_withdrawal_allowed,
                    'new_investors_this_week' => $newInvestorsThisWeek,
                    'investment_growth_this_month' => round($investmentGrowth, 2),
                ];
            });
    }

    /**
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getUserInvestments(int $userId, int $limit = 50): Collection
    {
        return UserInvestment::with('plan')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($investment) {
                return [
                    'id' => $investment->id,
                    'investment_id' => $investment->investment_id,
                    'plan_name' => $investment->plan->name,
                    'amount' => (float) $investment->amount,
                    'expected_return' => (float) $investment->expected_return,
                    'earned_amount' => (float) $investment->earned_amount,
                    'status' => $investment->status,
                    'started_at' => $investment->started_at?->toISOString(),
                    'next_return_at' => $investment->next_return_at?->toISOString(),
                    'maturity_date' => $investment->maturity_date?->toISOString(),
                    'returns_paid' => $investment->returns_paid,
                    'total_returns_expected' => $investment->total_returns_expected,
                    'progress_percentage' => $this->calculateProgressPercentage($investment),
                ];
            });
    }

    /**
     * @param User $user
     * @return array
     */
    public function getHeaderStatistics(User $user): array
    {
        $wallet = $user->wallet;

        return [
            'totalInvested' => (float) $wallet->total_invested,
            'totalStaked' => (float) $this->getUserTotalStaked($user->id),
            'totalReturns' => (float) $wallet->total_earnings,
            'activeUsers' => $this->getActiveUsersCount(),
            'totalPlans' => InvestmentPlan::where('status', 'active')->count(),
            'activePlans' => UserInvestment::where('status', 'active')->distinct('plan_id')->count('plan_id'),
            'bestApy' => (float) ($this->getBestApy() ?? 0),
            'investedGrowth' => $this->calculateGrowthRate($user->id, 'investment'),
            'stakedGrowth' => $this->calculateGrowthRate($user->id, 'staking'),
            'returnsGrowth' => $this->calculateReturnsGrowth($user->id),
            'newUsers' => $this->getNewUsersCount(),
        ];
    }

    /**
     * @param User $user
     * @param InvestmentPlan $plan
     * @param float $amount
     * @return UserInvestment
     * @throws InsufficientBalanceException
     * @throws PlanCapacityException
     * @throws \Exception
     */
    public function createInvestment(User $user, InvestmentPlan $plan, float $amount): UserInvestment
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

        if ($plan->max_investors > 0 && $plan->current_investors >= $plan->max_investors) {
            throw new PlanCapacityException(__('Investment plan has reached maximum investor capacity.'));
        }

        if ($plan->total_cap > 0 && ($plan->current_invested + $amount) > $plan->total_cap) {
            throw new PlanCapacityException(__('Investment amount exceeds plan total capacity.'));
        }

        DB::beginTransaction();
        try {
            $returnCalculations = $this->calculateReturns($plan, $amount);
            $investment = UserInvestment::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'investment_id' => $this->generateInvestmentId(),
                'amount' => $amount,
                'expected_return' => $returnCalculations['expected_return'],
                'earned_amount' => 0,
                'withdrawn_amount' => 0,
                'remaining_principal' => $amount,
                'status' => 'active',
                'approved_at' => now(),
                'started_at' => now(),
                'next_return_at' => $returnCalculations['next_return_at'],
                'maturity_date' => $returnCalculations['maturity_date'],
                'total_returns_expected' => $returnCalculations['total_returns'],
                'returns_paid' => 0,
                'return_amount' => $returnCalculations['return_amount'],
                'return_schedule' => $returnCalculations['schedule'],
                'auto_reinvest' => false,
                'reinvest_percentage' => 0,
                'reinvested_amount' => 0,
                'ai_confidence_score' => $this->calculateAIConfidence($plan, $user),
                'source' => request()->userAgent() ? 'web' : 'api',
            ]);

            $wallet->decrement('balance', $amount);
            $wallet->increment('total_invested', $amount);
            $wallet->update(['last_activity' => now()]);

            $plan->increment('current_investors');
            $plan->increment('current_invested', $amount);

            $this->createTransaction($user, 'debit', $amount, $wallet->balance,
                "Investment in {$plan->name} plan. Amount: " . config('app.currency_symbol', '$') . number_format($amount, 2) .
                ", Interest Rate: {$plan->interest_rate}%, Duration: {$plan->duration_days} days, Investment ID: {$investment->investment_id}"
            );

            if ($user->referred_by) {
                try {
                    $this->referralService->processInvestmentCommission($user, $amount, $investment);
                } catch (\Exception $e) {
                    Log::error('Failed to process investment referral commission', [
                        'user_id' => $user->id,
                        'investment_id' => $investment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();
            try {
                EmailTemplateService::sendTemplateEmail('investment_approved', $user, [
                    'user_name' => e($user->name),
                    'plan_name' => e($plan->name),
                    'amount' => round($amount, 2),
                    'investment_id' => e($investment->investment_id),
                    'expected_return' => round($returnCalculations['expected_return'], 2),
                    'maturity_date' => $investment->maturity_date->format('M d, Y'),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send investment confirmation email', [
                    'user_id' => $user->id,
                    'investment_id' => $investment->id,
                    'error' => $e->getMessage()
                ]);
            }

            return $investment;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Investment creation failed in service', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param InvestmentPlan $plan
     * @param float $amount
     * @return array
     */
    protected function calculateReturns(InvestmentPlan $plan, float $amount): array
    {
        $returnAmount = ($amount * $plan->interest_rate) / 100;
        $totalReturns = $this->calculateTotalReturns($plan);

        $schedule = [];
        $currentDate = Carbon::now();

        for ($i = 1; $i <= $totalReturns; $i++) {
            $schedule[] = [
                'cycle' => $i,
                'date' => $this->getNextReturnDate($currentDate, $plan->return_type)->toISOString(),
                'amount' => $returnAmount,
                'status' => 'pending',
            ];
            $currentDate = $this->getNextReturnDate($currentDate, $plan->return_type);
        }

        return [
            'return_amount' => $returnAmount,
            'expected_return' => $returnAmount * $totalReturns,
            'total_returns' => $totalReturns,
            'next_return_at' => $this->getNextReturnDate(Carbon::now(), $plan->return_type),
            'maturity_date' => Carbon::now()->addDays($plan->duration_days),
            'schedule' => $schedule,
        ];
    }

    /**
     * @param InvestmentPlan $plan
     * @return int
     */
    protected function calculateTotalReturns(InvestmentPlan $plan): int
    {
        return match($plan->return_type) {
            'hourly' => $plan->duration_days * 24,
            'daily' => $plan->duration_days,
            'weekly' => (int) ceil($plan->duration_days / 7),
            'monthly' => (int) ceil($plan->duration_days / 30),
            default => $plan->duration_days,
        };
    }

    /**
     * @param Carbon $from
     * @param string $returnType
     * @return Carbon
     */
    protected function getNextReturnDate(Carbon $from, string $returnType): Carbon
    {
        return match($returnType) {
            'hourly' => $from->copy()->addHour(),
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addMonth(),
            default => $from->copy()->addDay(),
        };
    }

    /**
     * @return string
     */
    protected function generateInvestmentId(): string
    {
        do {
            $id = 'INV' . strtoupper(Str::random(12));
        } while (UserInvestment::where('investment_id', $id)->exists());

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
     * @param UserInvestment $investment
     * @return float
     */
    public function calculateProgressPercentage(UserInvestment $investment): float
    {
        if ($investment->total_returns_expected <= 0) {
            return 0;
        }

        return round(($investment->returns_paid / $investment->total_returns_expected) * 100, 2);
    }

    /**
     * @param InvestmentPlan $plan
     * @param User $user
     * @return float
     */
    protected function calculateAIConfidence(InvestmentPlan $plan, User $user): float
    {
        $baseScore = $plan->success_rate;
        $riskAdjustment = match($user->risk_tolerance) {
            'conservative' => $plan->risk_level === 'low' ? 10 : -10,
            'moderate' => $plan->risk_level === 'medium' ? 10 : 0,
            'aggressive' => $plan->risk_level === 'high' ? 10 : -5,
            default => 0,
        };

        return min(100, max(0, $baseScore + $riskAdjustment));
    }

    /**
     * @param UserInvestment $investment
     * @return array
     */
    public function getReturnSchedule(UserInvestment $investment): array
    {
        return $investment->return_schedule ?? [];
    }

    /**
     * @param int $userId
     * @return float
     */
    protected function getUserTotalStaked(int $userId): float
    {
        return (float) DB::table('user_stakes')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->sum('stake_amount');
    }

    /**
     * @return int
     */
    protected function getActiveUsersCount(): int
    {
        return Cache::remember('active_users_count', 300, function () {
            return UserInvestment::where('status', 'active')
                ->distinct('user_id')
                ->count('user_id');
        });
    }

    /**
     * @return float|null
     */
    protected function getBestApy(): ?float
    {
        return Cache::remember('best_apy', 300, function () {
            return DB::table('staking_pools')
                ->where('status', 'active')
                ->max('apy_rate');
        });
    }


    /**
     * Calculate month-over-month growth rate for investments or staking
     *
     * @param int $userId
     * @param string $type 'investment' or 'staking'
     * @return float
     */
    protected function calculateGrowthRate(int $userId, string $type): float
    {
        $cacheKey = "growth_rate_{$userId}_{$type}";

        return Cache::remember($cacheKey, 300, function () use ($userId, $type) {
            $currentMonth = Carbon::now()->startOfMonth();
            $previousMonth = Carbon::now()->subMonth()->startOfMonth();
            $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            if ($type === 'investment') {
                // Current month total invested
                $currentTotal = (float) UserInvestment::where('user_id', $userId)
                    ->where('status', 'active')
                    ->where('created_at', '>=', $currentMonth)
                    ->sum('amount');

                // Previous month total invested
                $previousTotal = (float) UserInvestment::where('user_id', $userId)
                    ->where('status', 'active')
                    ->whereBetween('created_at', [$previousMonth, $previousMonthEnd])
                    ->sum('amount');

            } else { // staking
                // Current month total staked
                $currentTotal = (float) DB::table('user_stakes')
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->where('created_at', '>=', $currentMonth)
                    ->sum('stake_amount');

                // Previous month total staked
                $previousTotal = (float) DB::table('user_stakes')
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->whereBetween('created_at', [$previousMonth, $previousMonthEnd])
                    ->sum('stake_amount');
            }

            // Avoid division by zero
            if ($previousTotal == 0) {
                return $currentTotal > 0 ? 100.0 : 0.0;
            }

            // Calculate percentage growth: ((current - previous) / previous) * 100
            $growthRate = (($currentTotal - $previousTotal) / $previousTotal) * 100;

            return round($growthRate, 2);
        });
    }

    /**
     * Calculate returns growth rate (month-over-month earnings comparison)
     *
     * @param int $userId
     * @return float
     */
    protected function calculateReturnsGrowth(int $userId): float
    {
        $cacheKey = "returns_growth_{$userId}";
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $currentMonth = Carbon::now()->startOfMonth();
            $previousMonth = Carbon::now()->subMonth()->startOfMonth();
            $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            $currentEarnings = (float) UserInvestment::where('user_id', $userId)
                ->where('status', 'active')
                ->where('updated_at', '>=', $currentMonth)
                ->sum('earned_amount');

            $currentTransactionEarnings = (float) Transaction::where('user_id', $userId)
                ->where('type', 'credit')
                ->where('created_at', '>=', $currentMonth)
                ->where('details', 'LIKE', '%return%')
                ->sum('amount');

            $currentTotal = $currentEarnings + $currentTransactionEarnings;
            $previousEarnings = (float) UserInvestment::where('user_id', $userId)
                ->where('status', 'active')
                ->whereBetween('updated_at', [$previousMonth, $previousMonthEnd])
                ->sum('earned_amount');

            $previousTransactionEarnings = (float) Transaction::where('user_id', $userId)
                ->where('type', 'credit')
                ->whereBetween('created_at', [$previousMonth, $previousMonthEnd])
                ->where('details', 'LIKE', '%return%')
                ->sum('amount');

            $previousTotal = $previousEarnings + $previousTransactionEarnings;
            if ($previousTotal == 0) {
                return $currentTotal > 0 ? 100.0 : 0.0;
            }

            $growthRate = (($currentTotal - $previousTotal) / $previousTotal) * 100;

            return round($growthRate, 2);
        });
    }


    /**
     * @return int
     */
    protected function getNewUsersCount(): int
    {
        return Cache::remember('new_users_week', 300, function () {
            return DB::table('users')
                ->where('created_at', '>=', Carbon::now()->subWeek())
                ->count();
        });
    }
}

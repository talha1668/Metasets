<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ReferralCommission;
use App\Models\UserInvestment;
use App\Models\Transaction;
use App\Models\AiRecommendation;
use App\Models\UserReferral;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{

    /**
     * @return Response
     */
    public function index(): Response
    {
        try {
            $user = Auth::user();
            $dashboardData = $this->getDashboardData($user);
            return Inertia::render('User/Dashboard', $dashboardData);
        } catch (\Exception $e) {
            Log::error('Dashboard Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getErrorDashboard('Unable to load dashboard data. Please try again.');
        }
    }


    /**
     * @param $user
     * @return array
     */
    private function getDashboardData($user): array
    {
        $wallet = $user->wallet()->first();
        $dailyChange = $this->calculateDailyChange($user);
        $activeInvestments = $this->getActiveInvestments($user);
        $recentTransactions = $this->getRecentTransactions($user);
        $aiRecommendations = $this->getAiRecommendations($user);
        $portfolioChartData = $this->getPortfolioChartData($user);
        $investmentDistribution = $this->getInvestmentDistribution($user);
        $referralStats = $this->getReferralStats($user);

        return [
            'wallet' => [
                'balance' => (float) ($wallet->balance ?? 0),
                'total_invested' => (float) ($wallet->total_invested ?? 0),
                'total_earnings' => (float) ($wallet->total_earnings ?? 0),
                'total_referral_bonus' => (float) ($wallet->total_referral_bonus ?? 0),
                'dailyChange' => $dailyChange,
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'referral_code' => $user->referral_code,
                'total_referrals' => $user->total_referrals ?? 0,
                'active_referrals' => $user->active_referrals ?? 0,
                'kyc_status' => $user->kyc_status,
                'status' => $user->status,
            ],
            'activeInvestments' => $activeInvestments,
            'recentTransactions' => $recentTransactions,
            'aiRecommendations' => $aiRecommendations,
            'portfolioChartData' => $portfolioChartData,
            'investmentDistribution' => $investmentDistribution,
            'referralStats' => $referralStats,
        ];
    }


    /**
     * @param $user
     * @return array
     */
    private function getReferralStats($user): array
    {
        $totalReferrals = UserReferral::where('referrer_id', $user->id)->count();
        $activeReferrals = UserReferral::where('referrer_id', $user->id)
            ->where('status', 'active')
            ->count();

        $totalEarned = ReferralCommission::where('referrer_id', $user->id)
            ->where('status', 'approved')
            ->sum('commission_amount');

        $pendingCommission = ReferralCommission::where('referrer_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('commission_amount');

        $thisMonthEarnings = ReferralCommission::where('referrer_id', $user->id)
            ->where('status', 'approved')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('commission_amount');

        $lastMonthEarnings = ReferralCommission::where('referrer_id', $user->id)
            ->where('status', 'approved')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('commission_amount');

        $monthlyGrowth = 0;
        if ($lastMonthEarnings > 0) {
            $monthlyGrowth = round((($thisMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100, 2);
        } elseif ($thisMonthEarnings > 0) {
            $monthlyGrowth = 100;
        }

        $teamInvestment = UserReferral::where('referrer_id', $user->id)
            ->where('status', 'active')
            ->sum('total_referral_investment');

        return [
            'totalReferrals' => $totalReferrals,
            'activeReferrals' => $activeReferrals,
            'totalEarned' => (float) $totalEarned,
            'pendingCommission' => (float) $pendingCommission,
            'thisMonthEarnings' => (float) $thisMonthEarnings,
            'monthlyGrowth' => (float) $monthlyGrowth,
            'teamInvestment' => (float) $teamInvestment,
        ];
    }


    /**
     * @param $user
     * @return float
     */
    private function calculateDailyChange($user): float
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $todayEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward'])
            ->where('status', 'completed')
            ->where('created_at', '>=', $today)
            ->sum('amount');

        $yesterdayEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$yesterday, $today])
            ->sum('amount');

        if ($yesterdayEarnings == 0) {
            return $todayEarnings > 0 ? 100 : 0;
        }

        $change = (($todayEarnings - $yesterdayEarnings) / $yesterdayEarnings) * 100;
        return round($change, 1);
    }


    /**
     * @param $user
     * @return array
     */
    private function getActiveInvestments($user): array
    {
        return UserInvestment::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan:id,name,interest_rate')
            ->select([
                'id',
                'investment_id',
                'plan_id',
                'amount',
                'earned_amount',
                'returns_paid',
                'total_returns_expected',
                'status'
            ])
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(function ($investment) {
                return [
                    'id' => $investment->id,
                    'investment_id' => $investment->investment_id,
                    'plan_name' => $investment->plan->name ?? 'Unknown Plan',
                    'amount' => (float) $investment->amount,
                    'earned_amount' => (float) $investment->earned_amount,
                    'interest_rate' => (float) ($investment->plan->interest_rate ?? 0),
                    'return_type' => 'daily',
                    'returns_paid' => $investment->returns_paid,
                    'total_returns_expected' => $investment->total_returns_expected,
                    'status' => $investment->status,
                ];
            })
            ->toArray();
    }


    /**
     * @param $user
     * @return array
     */
    private function getRecentTransactions($user): array
    {
        return Transaction::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'pending'])
            ->select([
                'id',
                'type',
                'amount',
                'status',
                'created_at'
            ])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->toISOString(),
                ];
            })
            ->toArray();
    }


    /**
     * @param $user
     * @return array
     */
    private function getAiRecommendations($user): array
    {
        return AiRecommendation::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('is_read', false)
            ->select([
                'id',
                'title',
                'description',
                'confidence_score',
                'type',
                'priority'
            ])
            ->orderByDesc('priority')
            ->orderByDesc('confidence_score')
            ->limit(2)
            ->get()
            ->map(function ($rec) {
                return [
                    'id' => $rec->id,
                    'title' => $rec->title,
                    'description' => $rec->description,
                    'confidence_score' => (int) $rec->confidence_score,
                    'type' => $rec->type,
                    'priority' => $rec->priority,
                ];
            })
            ->toArray();
    }


    /**
     * @param $user
     * @return array[]
     */
    private function getPortfolioChartData($user): array
    {
        return [
            '7D' => $this->getChartDataForDays($user, 7),
            '30D' => $this->getChartDataForDays($user, 30),
            '90D' => $this->getChartDataForDays($user, 90),
        ];
    }


    /**
     * @param $user
     * @param int $days
     * @return array
     */
    private function getChartDataForDays($user, int $days): array
    {
        $wallet = $user->wallet();
        $currentBalance = $wallet->balance ?? 0;

        $dailyEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward'])
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $totalEarningsInPeriod = $dailyEarnings->sum('total');
        $runningBalance = $currentBalance - $totalEarningsInPeriod;

        $chartData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->toDateString();
            $dayEarnings = (float) ($dailyEarnings->get($dateStr)->total ?? 0);

            $runningBalance += $dayEarnings;
            $label = $days <= 7 ? $date->format('M d') : $date->format('m/d');

            $chartData[] = [
                'date' => $label,
                'value' => round($runningBalance, 2),
            ];
        }

        return $chartData;
    }


    /**
     * @param $user
     * @return array
     */
    private function getInvestmentDistribution($user): array
    {
        $investments = UserInvestment::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan:id,name')
            ->select('plan_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('plan_id')
            ->get();

        $totalInvested = $investments->sum('total_amount');

        if ($totalInvested == 0) {
            return [];
        }

        $colors = ['#86efac', '#34d399', '#10b981', '#059669'];
        return $investments->map(function ($investment, $index) use ($totalInvested, $colors) {
            $percentage = round(($investment->total_amount / $totalInvested) * 100, 1);

            return [
                'name' => $investment->plan->name ?? 'Unknown Plan',
                'value' => $percentage,
                'color' => $colors[$index % count($colors)],
            ];
        })->toArray();
    }


    /**
     * @param string $message
     * @return Response
     */
    private function getErrorDashboard(string $message): Response
    {
        return Inertia::render('User/Dashboard', [
            'wallet' => [
                'balance' => 0,
                'total_invested' => 0,
                'total_earnings' => 0,
                'total_referral_bonus' => 0,
                'dailyChange' => 0,
            ],
            'user' => [
                'name' => Auth::user()->name ?? '',
                'email' => Auth::user()->email ?? '',
                'referral_code' => Auth::user()->referral_code ?? '',
                'total_referrals' => 0,
                'active_referrals' => 0,
                'kyc_status' => 'pending',
                'status' => 'pending',
            ],
            'activeInvestments' => [],
            'recentTransactions' => [],
            'aiRecommendations' => [],
            'portfolioChartData' => [
                '7D' => [],
                '30D' => [],
                '90D' => [],
            ],
            'investmentDistribution' => [],
            'error' => $message
        ]);
    }
}

<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\UserInvestment;
use App\Models\StakingReward;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class EarningsController extends Controller
{

    /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): Response
    {
        try {
            $user = Auth::user();
            $filterType = request()->get('filter', 'all');
            $earningsData = $this->getEarningsData($user, $filterType);

            return Inertia::render('User/Earnings', $earningsData);

        } catch (\Exception $e) {
            Log::error('Earnings Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getErrorEarnings();
        }
    }


    /**
     * @param $user
     * @param string $filterType
     * @return array
     */
    private function getEarningsData($user, string $filterType = 'all'): array
    {
        $wallet = $user->wallet;
        $earningsStats = $this->getEarningsStats($user, $wallet);
        $transactions = $this->getTransactionHistory($user, $filterType);
        $monthlyBreakdown = $this->getMonthlyBreakdown($user);
        $quickStats = $this->getQuickStats($user);
        $transactionSummary = $this->getTransactionSummary($user);

        return [
            'earningsStats' => $earningsStats,
            'transactions' => $transactions,
            'monthlyBreakdown' => $monthlyBreakdown,
            'quickStats' => $quickStats,
            'transactionSummary' => $transactionSummary,
            'filterType' => $filterType,
        ];
    }


    /**
     * @param $user
     * @param $wallet
     * @return array
     */
    private function getEarningsStats($user, $wallet): array
    {
        $investmentProfit = UserInvestment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->sum('earned_amount');

        $referralCommission = $wallet->total_referral_bonus ?? 0;
        $stakingRewards = StakingReward::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('reward_amount');

        $totalEarnings = $wallet->total_earnings ?? 0;
        $thisMonthEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward'])
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $monthlyGrowth = $totalEarnings > 0 ?
            round(($thisMonthEarnings / $totalEarnings) * 100, 1) : 0;

        $activeInvestments = UserInvestment::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        $activeStakes = DB::table('user_stakes')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        return [
            'totalEarnings' => (float) $totalEarnings,
            'investmentProfit' => (float) $investmentProfit,
            'referralCommission' => (float) $referralCommission,
            'stakingRewards' => (float) $stakingRewards,
            'monthlyGrowth' => $monthlyGrowth,
            'activeInvestments' => $activeInvestments,
            'totalReferrals' => $user->total_referrals ?? 0,
            'activeStakes' => $activeStakes,
        ];
    }


    /**
     * @param $user
     * @param string $filterType
     * @return array
     */
    private function getTransactionHistory($user, string $filterType): array
    {
        $query = Transaction::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'pending']);

        if ($filterType !== 'all') {
            $query->where('type', $filterType);
        }

        return $query->select([
            'id',
            'transaction_id',
            'type',
            'amount',
            'fee',
            'post_balance',
            'status',
            'details',
            'created_at'
        ])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'fee' => (float) $transaction->fee,
                    'post_balance' => (float) $transaction->post_balance,
                    'status' => $transaction->status,
                    'details' => $transaction->details,
                    'created_at' => $transaction->created_at->toISOString(),
                ];
            })
            ->toArray();
    }


    /**
     * @param $user
     * @return float[]
     */
    private function getMonthlyBreakdown($user): array
    {
        $startOfMonth = now()->startOfMonth();
        $investmentProfit = Transaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $referralCommission = Transaction::where('user_id', $user->id)
            ->where('type', 'bonus')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $stakingRewards = Transaction::where('user_id', $user->id)
            ->where('type', 'staking_reward')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        return [
            'investment' => (float) $investmentProfit,
            'referral' => (float) $referralCommission,
            'staking' => (float) $stakingRewards,
        ];
    }


    /**
     * @param $user
     * @return float[]
     */
    private function getQuickStats($user): array
    {
        $startOfWeek = now()->startOfWeek();
        $startOfMonth = now()->startOfMonth();
        $last30DaysEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward'])
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        $avgDailyEarnings = $last30DaysEarnings / 30;
        $thisWeekEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward'])
            ->where('status', 'completed')
            ->where('created_at', '>=', $startOfWeek)
            ->sum('amount');

        $thisMonthEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward'])
            ->where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $allTimeEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward'])
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'avgDailyEarnings' => (float) round($avgDailyEarnings, 2),
            'thisWeek' => (float) $thisWeekEarnings,
            'thisMonth' => (float) $thisMonthEarnings,
            'allTime' => (float) $allTimeEarnings,
        ];
    }

    /**
     * @param $user
     * @return array
     */
    private function getTransactionSummary($user): array
    {
        $totalCredits = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward', 'deposit'])
            ->where('status', 'completed')
            ->count();

        $totalDebits = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['debit', 'withdrawal', 'stake'])
            ->where('status', 'completed')
            ->count();

        $totalFees = Transaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('fee');

        $totalEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['credit', 'bonus', 'staking_reward'])
            ->where('status', 'completed')
            ->sum('amount');

        $totalExpenses = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['debit', 'withdrawal', 'stake'])
            ->where('status', 'completed')
            ->sum('amount');

        $netEarnings = $totalEarnings - $totalExpenses - $totalFees;

        return [
            'totalCredits' => $totalCredits,
            'totalDebits' => $totalDebits,
            'totalFees' => (float) $totalFees,
            'netEarnings' => (float) $netEarnings,
        ];
    }


    /**
     * @return Response
     */
    private function getErrorEarnings(): Response
    {
        return Inertia::render('User/Earnings', [
            'earningsStats' => [
                'totalEarnings' => 0,
                'investmentProfit' => 0,
                'referralCommission' => 0,
                'stakingRewards' => 0,
                'monthlyGrowth' => 0,
                'activeInvestments' => 0,
                'totalReferrals' => 0,
                'activeStakes' => 0,
            ],
            'transactions' => [],
            'monthlyBreakdown' => [
                'investment' => 0,
                'referral' => 0,
                'staking' => 0,
            ],
            'quickStats' => [
                'avgDailyEarnings' => 0,
                'thisWeek' => 0,
                'thisMonth' => 0,
                'allTime' => 0,
            ],
            'transactionSummary' => [
                'totalCredits' => 0,
                'totalDebits' => 0,
                'totalFees' => 0,
                'netEarnings' => 0,
            ],
            'filterType' => 'all',
            'error' => 'Unable to load earnings data. Please try again.'
        ]);
    }
}

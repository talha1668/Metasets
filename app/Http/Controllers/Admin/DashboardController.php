<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\UserInvestment;
use App\Models\UserStake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{

    /**
     * @return Response
     */
    public function index(): Response
    {
        $stats = Cache::remember('dashboard_stats', 300, function () {
            return $this->getStats();
        });

        $recentActivity = $this->getRecentActivity();
        $chartData = $this->getChartData();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'chart_data' => $chartData,
        ]);
    }


    /**
     * @return array
     */
    private function getStats(): array
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        return [
            'users' => $this->getUserStats($today, $weekStart, $weekEnd),
            'financial' => $this->getFinancialStats($today, $monthStart),
            'investment' => $this->getInvestmentStats($today, $monthStart),
            'staking' => $this->getStakingStats($today, $monthStart),
            'referral' => $this->getReferralStats($today, $monthStart),
        ];
    }

    /**
     * @param string $today
     * @param string $weekStart
     * @param string $weekEnd
     * @return array
     */
    private function getUserStats(string $today, string $weekStart, string $weekEnd): array
    {
        $userStats = DB::table('users')
            ->selectRaw("
                COUNT(CASE WHEN role != 'admin' THEN 1 END) as total_users,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
                COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_users,
                COUNT(CASE WHEN status = 'banned' THEN 1 END) as banned_users,
                COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as new_today,
                COUNT(CASE WHEN DATE(created_at) BETWEEN ? AND ? THEN 1 END) as new_week,
                COUNT(CASE WHEN email_verified_at IS NOT NULL THEN 1 END) as verified_users,
                COUNT(CASE WHEN email_verified_at IS NULL THEN 1 END) as unverified_users,
                COUNT(CASE WHEN kyc_status = 'pending' THEN 1 END) as pending_kyc,
                COUNT(CASE WHEN kyc_status = 'reviewing' THEN 1 END) as reviewing_kyc,
                COUNT(CASE WHEN kyc_status = 'approved' THEN 1 END) as approved_kyc,
                COUNT(CASE WHEN kyc_status = 'rejected' THEN 1 END) as rejected_kyc,
                COUNT(CASE WHEN total_referrals > 0 THEN 1 END) as users_with_referrals
            ", [$today, $weekStart, $weekEnd])
            ->first();

        return [
            'total' => $userStats->total_users ?? 0,
            'active' => $userStats->active_users ?? 0,
            'suspended' => $userStats->suspended_users ?? 0,
            'banned' => $userStats->banned_users ?? 0,
            'new_today' => $userStats->new_today ?? 0,
            'new_week' => $userStats->new_week ?? 0,
            'verified' => $userStats->verified_users ?? 0,
            'unverified' => $userStats->unverified_users ?? 0,
            'pending_kyc' => $userStats->pending_kyc ?? 0,
            'reviewing_kyc' => $userStats->reviewing_kyc ?? 0,
            'approved_kyc' => $userStats->approved_kyc ?? 0,
            'rejected_kyc' => $userStats->rejected_kyc ?? 0,
            'users_with_referrals' => $userStats->users_with_referrals ?? 0,
        ];
    }


    /**
     * @param string $today
     * @param string $monthStart
     * @return array
     */
    private function getFinancialStats(string $today, string $monthStart): array
    {
        $depositStats = DB::table('deposits')
            ->selectRaw("
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
                SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as total_rejected,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                SUM(CASE WHEN DATE(created_at) = ? AND status = 'approved' THEN amount ELSE 0 END) as today_approved,
                SUM(CASE WHEN DATE(created_at) >= ? AND status = 'approved' THEN amount ELSE 0 END) as month_approved
            ", [$today, $monthStart])
            ->first();

        $withdrawalStats = DB::table('withdrawals')
            ->selectRaw("
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
                SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as total_rejected,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                SUM(CASE WHEN DATE(created_at) = ? AND status = 'approved' THEN amount ELSE 0 END) as today_approved,
                SUM(CASE WHEN DATE(created_at) >= ? AND status = 'approved' THEN amount ELSE 0 END) as month_approved
            ", [$today, $monthStart])
            ->first();

        $walletStats = DB::table('wallets')
            ->selectRaw("
                SUM(balance) as total_balance,
                SUM(bonus_balance) as total_bonus,
                SUM(total_invested) as total_invested,
                SUM(total_withdrawn) as total_withdrawn,
                SUM(total_earnings) as total_earnings,
                SUM(total_referral_bonus) as total_referral_bonus,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_wallets,
                COUNT(CASE WHEN status = 'locked' THEN 1 END) as locked_wallets
            ")
            ->first();

        return [
            'total_deposits' => $depositStats->total_approved ?? 0,
            'pending_deposits' => $depositStats->total_pending ?? 0,
            'rejected_deposits' => $depositStats->total_rejected ?? 0,
            'pending_deposits_count' => $depositStats->pending_count ?? 0,
            'deposits_today' => $depositStats->today_approved ?? 0,
            'deposits_month' => $depositStats->month_approved ?? 0,

            'total_withdrawals' => $withdrawalStats->total_approved ?? 0,
            'pending_withdrawals' => $withdrawalStats->total_pending ?? 0,
            'rejected_withdrawals' => $withdrawalStats->total_rejected ?? 0,
            'pending_withdrawals_count' => $withdrawalStats->pending_count ?? 0,
            'withdrawals_today' => $withdrawalStats->today_approved ?? 0,
            'withdrawals_month' => $withdrawalStats->month_approved ?? 0,

            'total_balance' => $walletStats->total_balance ?? 0,
            'total_bonus' => $walletStats->total_bonus ?? 0,
            'total_invested' => $walletStats->total_invested ?? 0,
            'total_withdrawn' => $walletStats->total_withdrawn ?? 0,
            'total_earnings' => $walletStats->total_earnings ?? 0,
            'total_referral_bonus' => $walletStats->total_referral_bonus ?? 0,
            'active_wallets' => $walletStats->active_wallets ?? 0,
            'locked_wallets' => $walletStats->locked_wallets ?? 0,
        ];
    }

    /**
     * @param string $today
     * @param string $monthStart
     * @return array
     */
    private function getInvestmentStats(string $today, string $monthStart): array
    {
        $investmentStats = DB::table('user_investments')
            ->selectRaw("
                COUNT(*) as total_investments,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_investments,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_investments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_investments,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_investments,
                SUM(amount) as total_invested,
                SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END) as active_invested,
                SUM(earned_amount) as total_earned,
                SUM(withdrawn_amount) as total_withdrawn,
                SUM(expected_return) as total_expected_return,
                SUM(CASE WHEN DATE(created_at) = ? THEN amount ELSE 0 END) as today_invested,
                SUM(CASE WHEN DATE(created_at) >= ? THEN amount ELSE 0 END) as month_invested,
                COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as today_count,
                COUNT(CASE WHEN DATE(created_at) >= ? THEN 1 END) as month_count
            ", [$today, $monthStart, $today, $monthStart])
            ->first();

        $planStats = DB::table('investment_plans')
            ->selectRaw("
                COUNT(*) as total_plans,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_plans,
                COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_plans,
                SUM(current_investors) as total_plan_investors,
                SUM(current_invested) as total_plan_invested,
                SUM(total_profit_paid) as total_profit_paid
            ")
            ->first();

        return [
            'total_investments' => $investmentStats->total_investments ?? 0,
            'active_investments' => $investmentStats->active_investments ?? 0,
            'completed_investments' => $investmentStats->completed_investments ?? 0,
            'pending_investments' => $investmentStats->pending_investments ?? 0,
            'cancelled_investments' => $investmentStats->cancelled_investments ?? 0,
            'total_invested' => $investmentStats->total_invested ?? 0,
            'active_invested' => $investmentStats->active_invested ?? 0,
            'total_earned' => $investmentStats->total_earned ?? 0,
            'total_withdrawn' => $investmentStats->total_withdrawn ?? 0,
            'total_expected_return' => $investmentStats->total_expected_return ?? 0,
            'today_invested' => $investmentStats->today_invested ?? 0,
            'month_invested' => $investmentStats->month_invested ?? 0,
            'today_count' => $investmentStats->today_count ?? 0,
            'month_count' => $investmentStats->month_count ?? 0,

            'total_plans' => $planStats->total_plans ?? 0,
            'active_plans' => $planStats->active_plans ?? 0,
            'featured_plans' => $planStats->featured_plans ?? 0,
            'total_plan_investors' => $planStats->total_plan_investors ?? 0,
            'total_plan_invested' => $planStats->total_plan_invested ?? 0,
            'total_profit_paid' => $planStats->total_profit_paid ?? 0,
        ];
    }

    /**
     * @param string $today
     * @param string $monthStart
     * @return array
     */
    private function getStakingStats(string $today, string $monthStart): array
    {
        $stakingStats = DB::table('user_stakes')
            ->selectRaw("
                COUNT(*) as total_stakes,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_stakes,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_stakes,
                SUM(stake_amount) as total_staked,
                SUM(CASE WHEN status = 'active' THEN stake_amount ELSE 0 END) as active_staked,
                SUM(current_balance) as total_current_balance,
                SUM(total_rewards) as total_rewards_paid,
                SUM(CASE WHEN DATE(created_at) = ? THEN stake_amount ELSE 0 END) as today_staked,
                SUM(CASE WHEN DATE(created_at) >= ? THEN stake_amount ELSE 0 END) as month_staked
            ", [$today, $monthStart])
            ->first();

        $poolStats = DB::table('staking_pools')
            ->selectRaw("
                COUNT(*) as total_pools,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_pools,
                SUM(total_pool_size) as total_pool_size,
                SUM(current_staked) as total_current_staked,
                AVG(apy_rate) as average_apy
            ")
            ->first();

        return [
            'total_stakes' => $stakingStats->total_stakes ?? 0,
            'active_stakes' => $stakingStats->active_stakes ?? 0,
            'completed_stakes' => $stakingStats->completed_stakes ?? 0,
            'total_staked' => $stakingStats->total_staked ?? 0,
            'active_staked' => $stakingStats->active_staked ?? 0,
            'total_current_balance' => $stakingStats->total_current_balance ?? 0,
            'total_rewards_paid' => $stakingStats->total_rewards_paid ?? 0,
            'today_staked' => $stakingStats->today_staked ?? 0,
            'month_staked' => $stakingStats->month_staked ?? 0,

            'total_pools' => $poolStats->total_pools ?? 0,
            'active_pools' => $poolStats->active_pools ?? 0,
            'total_pool_size' => $poolStats->total_pool_size ?? 0,
            'total_current_staked' => $poolStats->total_current_staked ?? 0,
            'average_apy' => $poolStats->average_apy ?? 0,
        ];
    }

    /**
     * @param string $today
     * @param string $monthStart
     * @return array
     */
    private function getReferralStats(string $today, string $monthStart): array
    {
        $referralStats = DB::table('referral_commissions')
            ->selectRaw("
                COUNT(*) as total_commissions,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_commissions,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_commissions,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_commissions,
                SUM(commission_amount) as total_commission_amount,
                SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_commission_amount,
                SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_commission_amount,
                SUM(CASE WHEN DATE(created_at) = ? THEN commission_amount ELSE 0 END) as today_commission,
                SUM(CASE WHEN DATE(created_at) >= ? THEN commission_amount ELSE 0 END) as month_commission
            ", [$today, $monthStart])
            ->first();

        $userReferralStats = DB::table('users')
            ->selectRaw("
                SUM(total_referrals) as total_referrals,
                SUM(active_referrals) as total_active_referrals,
                COUNT(CASE WHEN total_referrals > 0 THEN 1 END) as users_with_referrals
            ")
            ->first();

        return [
            'total_commissions' => $referralStats->total_commissions ?? 0,
            'pending_commissions' => $referralStats->pending_commissions ?? 0,
            'approved_commissions' => $referralStats->approved_commissions ?? 0,
            'paid_commissions' => $referralStats->paid_commissions ?? 0,
            'total_commission_amount' => $referralStats->total_commission_amount ?? 0,
            'paid_commission_amount' => $referralStats->paid_commission_amount ?? 0,
            'pending_commission_amount' => $referralStats->pending_commission_amount ?? 0,
            'today_commission' => $referralStats->today_commission ?? 0,
            'month_commission' => $referralStats->month_commission ?? 0,

            'total_referrals' => $userReferralStats->total_referrals ?? 0,
            'total_active_referrals' => $userReferralStats->total_active_referrals ?? 0,
            'users_with_referrals' => $userReferralStats->users_with_referrals ?? 0,
        ];
    }

    /**
     * @return array
     */
    private function getRecentActivity(): array
    {
        return [
            'users' => User::select('id', 'name', 'email', 'avatar', 'created_at', 'status', 'kyc_status', 'last_login_at')
                ->where('role', '!=', 'admin')
                ->latest()
                ->take(8)
                ->get(),

            'deposits' => Deposit::with('user:id,name,email,avatar')
                ->select('id', 'user_id', 'amount', 'currency', 'status', 'created_at', 'trx')
                ->latest()
                ->take(8)
                ->get(),

            'withdrawals' => Withdrawal::with('user:id,name,email,avatar')
                ->select('id', 'user_id', 'amount', 'currency', 'status', 'created_at', 'trx')
                ->latest()
                ->take(8)
                ->get(),

            'investments' => UserInvestment::with(['user:id,name,email,avatar', 'plan:id,name'])
                ->select('id', 'user_id', 'plan_id', 'investment_id', 'amount', 'status', 'created_at')
                ->latest()
                ->take(8)
                ->get(),

            'stakes' => UserStake::with(['user:id,name,email,avatar', 'pool:id,name,token_symbol'])
                ->select('id', 'user_id', 'pool_id', 'stake_id', 'stake_amount', 'status', 'staked_at')
                ->latest()
                ->take(8)
                ->get(),
        ];
    }

    /**
     * @return array
     */
    private function getChartData(): array
    {
        return [
            'user_growth' => $this->getUserGrowthData(),
            'revenue' => $this->getRevenueData(),
            'investments' => $this->getInvestmentData(),
        ];
    }


    /**
     * @return array
     */
    private function getUserGrowthData(): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays(29);

        $data = DB::table('users')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('role', '!=', 'admin')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->toDateString();
            $result[] = [
                'date' => $dateStr,
                'count' => $data->get($dateStr)->count ?? 0
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getRevenueData(): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays(29);

        $deposits = DB::table('deposits')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as amount')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        $withdrawals = DB::table('withdrawals')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as amount')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        $result = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->toDateString();
            $result[] = [
                'date' => $dateStr,
                'deposits' => $deposits->get($dateStr)->amount ?? 0,
                'withdrawals' => $withdrawals->get($dateStr)->amount ?? 0
            ];
        }

        return $result;
    }


    /**
     * @return array
     */
    private function getInvestmentData(): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays(29);

        $investments = DB::table('user_investments')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as amount'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        $result = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->toDateString();
            $result[] = [
                'date' => $dateStr,
                'amount' => $investments->get($dateStr)->amount ?? 0,
                'count' => $investments->get($dateStr)->count ?? 0
            ];
        }

        return $result;
    }


    /**
     * @return Response
     */
    public function profile(): Response
    {
        $user = Auth::user();
        return Inertia::render('Admin/Profile', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'last_login_at' => $user->last_login_at,
                'role' => $user->role,
                'kyc_status' => $user->kyc_status,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar ? asset('assets/files/'.$user->avatar) : null,
            ]
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();
            DB::transaction(function () use ($user, $validated) {
                $user->update($validated);
            });

            return back()->with('success', 'Profile updated successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator);
        }
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $validator = Validator::make($request->all(), [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();
            DB::transaction(function () use ($user, $validated) {
                $user->update([
                    'password' => Hash::make($validated['password']),
                ]);

                Log::info('Password updated', ['user_id' => $user->id]);
            });

            return back()->with('success', 'Password updated successfully!');

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator);
        }
    }
}

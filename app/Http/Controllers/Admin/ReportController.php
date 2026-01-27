<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserStake;
use App\Models\Transaction;
use App\Models\ReferralCommission;
use App\Models\Deposit;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{

    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function financial(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'period' => 'nullable|in:today,week,month,year,custom'
        ]);

        try {
            $dateRange = $this->getDateRange($request);
            $stats = [
                'total_deposits' => Deposit::where('status', 'approved')
                    ->whereBetween('created_at', $dateRange)
                    ->sum('final_amount'),
                'total_withdrawals' => Withdrawal::where('status', 'approved')
                    ->whereBetween('created_at', $dateRange)
                    ->sum('final_amount'),
                'total_investments' => UserInvestment::whereIn('status', ['active', 'completed'])
                    ->whereBetween('created_at', $dateRange)
                    ->sum('amount'),
                'total_earnings_paid' => UserInvestment::whereBetween('created_at', $dateRange)
                    ->sum('earned_amount'),
                'total_commissions' => ReferralCommission::where('status', 'paid')
                    ->whereBetween('created_at', $dateRange)
                    ->sum('commission_amount'),
                'net_balance' => 12,
            ];

            $stats['net_balance'] = $stats['total_deposits'] - $stats['total_withdrawals'] - $stats['total_earnings_paid'];
            $dailyTransactions = Transaction::whereBetween('created_at', $dateRange)
                ->selectRaw('DATE(created_at) as date, type, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('date', 'type')
                ->orderBy('date')
                ->get()
                ->groupBy('date')
                ->map(function ($transactions, $date) {
                    $data = ['date' => $date];
                    foreach ($transactions as $transaction) {
                        $data[$transaction->type] = (float) $transaction->total;
                        $data[$transaction->type . '_count'] = $transaction->count;
                    }
                    return $data;
                })
                ->values();

            $revenueExpenseChart = [
                'revenue' => [
                    'deposits' => (float) $stats['total_deposits'],
                    'investments' => (float) $stats['total_investments'],
                ],
                'expenses' => [
                    'withdrawals' => (float) $stats['total_withdrawals'],
                    'earnings_paid' => (float) $stats['total_earnings_paid'],
                    'commissions' => (float) $stats['total_commissions'],
                ]
            ];

            $monthlyComparison = Deposit::where('status', 'approved')
                ->whereBetween('created_at', [now()->subMonths(6), now()])
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(final_amount) as total')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => date('M Y', mktime(0, 0, 0, $item->month, 1, $item->year)),
                        'deposits' => (float) $item->total,
                    ];
                });

            $transactionTypeDistribution = Transaction::whereBetween('created_at', $dateRange)
                ->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('type')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst(str_replace('_', ' ', $item->type)),
                        'value' => (float) $item->total,
                        'count' => $item->count
                    ];
                });

            $cashFlowTimeline = DB::table(DB::raw('(
                SELECT created_at as date, final_amount as amount, "deposit" as type FROM deposits WHERE status = "approved"
                UNION ALL
                SELECT created_at as date, -final_amount as amount, "withdrawal" as type FROM withdrawals WHERE status = "approved"
                UNION ALL
                SELECT created_at as date, -earned_amount as amount, "earnings" as type FROM user_investments
            ) as cash_flow'))
                ->whereBetween('date', $dateRange)
                ->selectRaw('DATE(date) as date, SUM(amount) as net_flow')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'net_flow' => (float) $item->net_flow
                    ];
                });

            return Inertia::render('Admin/Reports/Financial', [
                'stats' => $stats,
                'charts' => [
                    'daily_transactions' => $dailyTransactions,
                    'revenue_expense' => $revenueExpenseChart,
                    'monthly_comparison' => $monthlyComparison,
                    'transaction_distribution' => $transactionTypeDistribution,
                    'cash_flow_timeline' => $cashFlowTimeline,
                ],
                'filters' => $request->only(['date_from', 'date_to', 'period'])
            ]);

        } catch (\Exception $e) {
            Log::error('Financial report error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Unable to load financial report.']);
        }
    }


    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function investments(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'plan_id' => 'nullable|exists:investment_plans,id',
            'status' => 'nullable|in:pending,active,completed,cancelled'
        ]);

        try {
            $dateRange = $this->getDateRange($request);

            $query = UserInvestment::with(['plan:id,name', 'user:id,name,email'])
                ->whereBetween('created_at', $dateRange);

            if ($request->plan_id) {
                $query->where('plan_id', $request->plan_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $stats = [
                'total_investments' => $query->count(),
                'total_amount_invested' => (float) $query->sum('amount'),
                'total_returns_paid' => (float) $query->sum('earned_amount'),
                'active_investments' => UserInvestment::where('status', 'active')
                    ->whereBetween('created_at', $dateRange)
                    ->count(),
                'completed_investments' => UserInvestment::where('status', 'completed')
                    ->whereBetween('created_at', $dateRange)
                    ->count(),
                'average_investment' => (float) $query->avg('amount'),
            ];

            // Plan-wise performance chart
            $planWiseData = UserInvestment::with('plan:id,name')
                ->whereBetween('created_at', $dateRange)
                ->selectRaw('plan_id, COUNT(*) as count, SUM(amount) as total_amount, SUM(earned_amount) as total_earned, AVG(amount) as avg_amount')
                ->groupBy('plan_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'plan_name' => $item->plan->name ?? 'Unknown',
                        'count' => $item->count,
                        'total_invested' => (float) $item->total_amount,
                        'total_earned' => (float) $item->total_earned,
                        'avg_investment' => (float) $item->avg_amount,
                        'roi' => $item->total_amount > 0 ? round(($item->total_earned / $item->total_amount) * 100, 2) : 0
                    ];
                });

            // Status distribution pie chart
            $statusDistribution = UserInvestment::whereBetween('created_at', $dateRange)
                ->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('status')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst($item->status),
                        'value' => $item->count,
                        'amount' => (float) $item->total_amount
                    ];
                });

            // Investment trend over time
            $investmentTrend = UserInvestment::whereBetween('created_at', $dateRange)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total, SUM(earned_amount) as earned')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'count' => $item->count,
                        'invested' => (float) $item->total,
                        'earned' => (float) $item->earned
                    ];
                });

            // ROI comparison chart
            $roiComparison = UserInvestment::with('plan:id,name')
                ->where('status', 'completed')
                ->whereBetween('created_at', $dateRange)
                ->get()
                ->groupBy('plan_id')
                ->map(function ($investments, $planId) {
                    $totalInvested = $investments->sum('amount');
                    $totalEarned = $investments->sum('earned_amount');
                    return [
                        'plan_name' => $investments->first()->plan->name ?? 'Unknown',
                        'roi' => $totalInvested > 0 ? round(($totalEarned / $totalInvested) * 100, 2) : 0,
                        'total_invested' => (float) $totalInvested,
                        'total_earned' => (float) $totalEarned
                    ];
                })
                ->values();

            // Investment size distribution
            $sizeDistribution = UserInvestment::whereBetween('created_at', $dateRange)
                ->select(DB::raw("
        CASE
            WHEN amount < 100 THEN 'less_100'
            WHEN amount >= 100 AND amount < 500 THEN '100_500'
            WHEN amount >= 500 AND amount < 1000 THEN '500_1000'
            WHEN amount >= 1000 AND amount < 5000 THEN '1000_5000'
            ELSE 'more_5000'
        END as `range`
    "))
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(amount) as total')
                ->groupBy('range')
                ->get()
                ->map(function ($item) {
                    $ranges = [
                        'less_100' => '< $100',
                        '100_500' => '$100 - $500',
                        '500_1000' => '$500 - $1,000',
                        '1000_5000' => '$1,000 - $5,000',
                        'more_5000' => '> $5,000'
                    ];

                    return [
                        'range' => $ranges[$item->range] ?? $item->range,
                        'count' => $item->count,
                        'total' => (float) $item->total
                    ];
                });

            return Inertia::render('Admin/Reports/Investments', [
                'stats' => $stats,
                'charts' => [
                    'plan_wise' => $planWiseData,
                    'status_distribution' => $statusDistribution,
                    'investment_trend' => $investmentTrend,
                    'roi_comparison' => $roiComparison,
                    'size_distribution' => $sizeDistribution,
                ],
                'filters' => $request->only(['date_from', 'date_to', 'plan_id', 'status'])
            ]);

        } catch (\Exception $e) {
            dd($e->getMessage());

            Log::error('Investment report error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Unable to load investment report.']);
        }
    }


    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function staking(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'pool_id' => 'nullable|exists:staking_pools,id',
            'status' => 'nullable|in:active,completed,cancelled'
        ]);

        try {
            $dateRange = $this->getDateRange($request);
            $query = UserStake::with(['pool:id,name', 'user:id,name,email'])->whereBetween('created_at', $dateRange);
            if ($request->pool_id) {
                $query->where('pool_id', $request->pool_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $stats = [
                'total_stakes' => $query->count(),
                'total_staked_amount' => (float) $query->sum('stake_amount'),
                'total_rewards_paid' => (float) $query->sum('total_rewards'),
                'active_stakes' => UserStake::where('status', 'active')
                    ->whereBetween('created_at', $dateRange)
                    ->count(),
                'total_current_balance' => (float) UserStake::where('status', 'active')
                    ->whereBetween('created_at', $dateRange)
                    ->sum('current_balance'),
                'average_stake' => (float) $query->avg('stake_amount'),
            ];

            $poolWiseData = UserStake::with('pool:id,name,apy_rate')
                ->whereBetween('created_at', $dateRange)
                ->selectRaw('pool_id, COUNT(*) as count, SUM(stake_amount) as total_staked, SUM(total_rewards) as total_rewards, AVG(stake_amount) as avg_stake')
                ->groupBy('pool_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'pool_name' => $item->pool->name ?? 'Unknown',
                        'apy_rate' => (float) ($item->pool->apy_rate ?? 0),
                        'count' => $item->count,
                        'total_staked' => (float) $item->total_staked,
                        'total_rewards' => (float) $item->total_rewards,
                        'avg_stake' => (float) $item->avg_stake
                    ];
                });

            $stakingTrend = UserStake::whereBetween('created_at', $dateRange)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(stake_amount) as total_staked, SUM(total_rewards) as rewards')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'count' => $item->count,
                        'staked' => (float) $item->total_staked,
                        'rewards' => (float) $item->rewards
                    ];
                });

            $statusDistribution = UserStake::whereBetween('created_at', $dateRange)
                ->selectRaw('status, COUNT(*) as count, SUM(stake_amount) as total')
                ->groupBy('status')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst($item->status),
                        'value' => $item->count,
                        'amount' => (float) $item->total
                    ];
                });

            $rewardsTimeline = DB::table('staking_rewards')
                ->whereBetween('created_at', $dateRange)
                ->selectRaw('DATE(reward_date) as date, SUM(reward_amount) as total_rewards, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'rewards' => (float) $item->total_rewards,
                        'count' => $item->count
                    ];
                });

            $apyPerformance = UserStake::with('pool:id,name,apy_rate')
                ->where('status', 'active')
                ->whereBetween('created_at', $dateRange)
                ->get()
                ->groupBy('pool_id')
                ->map(function ($stakes, $poolId) {
                    $totalStaked = $stakes->sum('stake_amount');
                    $totalRewards = $stakes->sum('total_rewards');
                    $pool = $stakes->first()->pool;

                    return [
                        'pool_name' => $pool->name ?? 'Unknown',
                        'expected_apy' => (float) ($pool->apy_rate ?? 0),
                        'actual_return' => $totalStaked > 0 ? round(($totalRewards / $totalStaked) * 100, 2) : 0,
                        'total_staked' => (float) $totalStaked
                    ];
                })
                ->values();

            return Inertia::render('Admin/Reports/Staking', [
                'stats' => $stats,
                'charts' => [
                    'pool_wise' => $poolWiseData,
                    'staking_trend' => $stakingTrend,
                    'status_distribution' => $statusDistribution,
                    'rewards_timeline' => $rewardsTimeline,
                    'apy_performance' => $apyPerformance,
                ],
                'filters' => $request->only(['date_from', 'date_to', 'pool_id', 'status'])
            ]);

        } catch (\Exception $e) {
            Log::error('Staking report error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Unable to load staking report.']);
        }
    }


    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function userActivity(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|in:active,suspended,pending,banned'
        ]);

        try {
            $dateRange = $this->getDateRange($request);

            $stats = [
                'total_users' => User::whereBetween('created_at', $dateRange)->count(),
                'active_users' => User::where('status', 'active')
                    ->whereBetween('created_at', $dateRange)
                    ->count(),
                'users_with_investments' => User::whereHas('investments', function ($q) use ($dateRange) {
                    $q->whereBetween('created_at', $dateRange);
                })->count(),
                'users_with_stakes' => User::whereHas('stakes', function ($q) use ($dateRange) {
                    $q->whereBetween('created_at', $dateRange);
                })->count(),
                'kyc_approved' => User::where('kyc_status', 'approved')
                    ->whereBetween('created_at', $dateRange)
                    ->count(),
            ];

            $registrationTrend = User::whereBetween('created_at', $dateRange)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'registrations' => $item->count
                    ];
                });

            $statusDistribution = User::whereBetween('created_at', $dateRange)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst($item->status),
                        'value' => $item->count
                    ];
                });

            $kycDistribution = User::whereBetween('created_at', $dateRange)
                ->selectRaw('kyc_status, COUNT(*) as count')
                ->groupBy('kyc_status')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst($item->kyc_status),
                        'value' => $item->count
                    ];
                });

            $topInvestors = User::withSum(['investments' => function ($q) use ($dateRange) {
                $q->whereBetween('created_at', $dateRange);
            }], 'amount')
                ->orderByDesc('investments_sum_amount')
                ->limit(10)
                ->get(['id', 'name', 'email'])
                ->map(function ($user) {
                    return [
                        'name' => $user->name,
                        'email' => $user->email,
                        'total_invested' => (float) ($user->investments_sum_amount ?? 0)
                    ];
                });

            $engagementMetrics = [
                'with_wallet' => User::whereNotNull('wallet_address')
                    ->whereBetween('created_at', $dateRange)
                    ->count(),
                'with_referrals' => User::where('total_referrals', '>', 0)
                    ->whereBetween('created_at', $dateRange)
                    ->count(),
                'active_last_7_days' => User::where('last_login_at', '>=', now()->subDays(7))
                    ->whereBetween('created_at', $dateRange)
                    ->count(),
                'with_investments' => $stats['users_with_investments'],
            ];

            $genderDistribution = User::whereBetween('created_at', $dateRange)
                ->whereNotNull('gender')
                ->selectRaw('gender, COUNT(*) as count')
                ->groupBy('gender')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst($item->gender),
                        'value' => $item->count
                    ];
                });

            $riskToleranceDistribution = User::whereBetween('created_at', $dateRange)
                ->selectRaw('risk_tolerance, COUNT(*) as count')
                ->groupBy('risk_tolerance')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst($item->risk_tolerance),
                        'value' => $item->count
                    ];
                });

            return Inertia::render('Admin/Reports/UserActivity', [
                'stats' => $stats,
                'charts' => [
                    'registration_trend' => $registrationTrend,
                    'status_distribution' => $statusDistribution,
                    'kyc_distribution' => $kycDistribution,
                    'top_investors' => $topInvestors,
                    'engagement_metrics' => $engagementMetrics,
                    'gender_distribution' => $genderDistribution,
                    'risk_tolerance' => $riskToleranceDistribution,
                ],
                'filters' => $request->only(['date_from', 'date_to', 'status'])
            ]);
        } catch (\Exception $e) {
            Log::error('User activity report error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Unable to load user activity report.']);
        }
    }


    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function commissions(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'level' => 'nullable|integer|min:1',
            'type' => 'nullable|in:deposit,investment_profit,reinvestment',
            'status' => 'nullable|in:pending,approved,paid'
        ]);

        try {
            $dateRange = $this->getDateRange($request);
            $query = ReferralCommission::with(['referrer:id,name,email', 'referred:id,name,email'])
                ->whereBetween('created_at', $dateRange);

            if ($request->level) {
                $query->where('level', $request->level);
            }

            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $stats = [
                'total_commissions' => (float) $query->sum('commission_amount'),
                'pending_commissions' => (float) ReferralCommission::where('status', 'pending')
                    ->whereBetween('created_at', $dateRange)
                    ->sum('commission_amount'),
                'paid_commissions' => (float) ReferralCommission::where('status', 'paid')
                    ->whereBetween('created_at', $dateRange)
                    ->sum('commission_amount'),
                'total_transactions' => $query->count(),
                'average_commission' => (float) $query->avg('commission_amount'),
            ];

            $levelWiseData = ReferralCommission::whereBetween('created_at', $dateRange)
                ->selectRaw('level, COUNT(*) as count, SUM(commission_amount) as total, AVG(commission_amount) as average')
                ->groupBy('level')
                ->orderBy('level')
                ->get()
                ->map(function ($item) {
                    return [
                        'level' => 'Level ' . $item->level,
                        'count' => $item->count,
                        'total' => (float) $item->total,
                        'average' => (float) $item->average
                    ];
                });

            $typeWiseData = ReferralCommission::whereBetween('created_at', $dateRange)
                ->selectRaw('type, COUNT(*) as count, SUM(commission_amount) as total')
                ->groupBy('type')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst(str_replace('_', ' ', $item->type)),
                        'value' => (float) $item->total,
                        'count' => $item->count
                    ];
                });

            $commissionTrend = ReferralCommission::whereBetween('created_at', $dateRange)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(commission_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'commissions' => (float) $item->total,
                        'count' => $item->count
                    ];
                });

            $topEarners = User::withSum(['referralCommissionsEarned' => function ($q) use ($dateRange) {
                $q->where('status', 'paid')
                    ->whereBetween('created_at', $dateRange);
            }], 'commission_amount')
                ->withCount(['referralCommissionsEarned' => function ($q) use ($dateRange) {
                    $q->whereBetween('created_at', $dateRange);
                }])
                ->orderByDesc('referral_commissions_earned_sum_commission_amount')
                ->limit(10)
                ->get(['id', 'name', 'email', 'total_referrals'])
                ->map(function ($user) {
                    return [
                        'name' => $user->name,
                        'email' => $user->email,
                        'total_earned' => (float) ($user->referral_commissions_earned_sum_commission_amount ?? 0),
                        'total_referrals' => $user->total_referrals,
                        'commission_count' => $user->referral_commissions_earned_count
                    ];
                });

            $statusDistribution = ReferralCommission::whereBetween('created_at', $dateRange)
                ->selectRaw('status, COUNT(*) as count, SUM(commission_amount) as total')
                ->groupBy('status')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst($item->status),
                        'value' => $item->count,
                        'amount' => (float) $item->total
                    ];
                });

            $rateAnalysis = ReferralCommission::whereBetween('created_at', $dateRange)
                ->selectRaw('
                    level,
                    AVG(commission_rate) as avg_rate,
                    MIN(commission_rate) as min_rate,
                    MAX(commission_rate) as max_rate,
                    SUM(base_amount) as total_base,
                    SUM(commission_amount) as total_commission
                ')
                ->groupBy('level')
                ->orderBy('level')
                ->get()
                ->map(function ($item) {
                    return [
                        'level' => 'Level ' . $item->level,
                        'avg_rate' => (float) $item->avg_rate,
                        'min_rate' => (float) $item->min_rate,
                        'max_rate' => (float) $item->max_rate,
                        'total_base' => (float) $item->total_base,
                        'total_commission' => (float) $item->total_commission
                    ];
                });

            return Inertia::render('Admin/Reports/Commissions', [
                'stats' => $stats,
                'charts' => [
                    'level_wise' => $levelWiseData,
                    'type_wise' => $typeWiseData,
                    'commission_trend' => $commissionTrend,
                    'top_earners' => $topEarners,
                    'status_distribution' => $statusDistribution,
                    'rate_analysis' => $rateAnalysis,
                ],
                'filters' => $request->only(['date_from', 'date_to', 'level', 'type','status'])
            ]);

        } catch (\Exception $e) {
            Log::error('Commission report error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Unable to load commission report.']);
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        try {
            $dateRange = $this->getDateRange($request);
            $platformStats = [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'total_deposits' => (float) Deposit::where('status', 'approved')->sum('final_amount'),
                'total_withdrawals' => (float) Withdrawal::where('status', 'approved')->sum('final_amount'),
                'total_investments' => (float) UserInvestment::whereIn('status', ['active', 'completed'])->sum('amount'),
                'total_staked' => (float) UserStake::where('status', 'active')->sum('stake_amount'),
                'total_commissions_paid' => (float) ReferralCommission::where('status', 'paid')->sum('commission_amount'),
            ];

            $currentPeriodDeposits = Deposit::where('status', 'approved')
                ->whereBetween('created_at', $dateRange)
                ->sum('final_amount');

            $periodLength = \Carbon\Carbon::parse($dateRange[0])->diffInDays(\Carbon\Carbon::parse($dateRange[1]));
            $previousPeriodStart = \Carbon\Carbon::parse($dateRange[0])->subDays($periodLength);
            $previousPeriodEnd = \Carbon\Carbon::parse($dateRange[0]);

            $previousPeriodDeposits = Deposit::where('status', 'approved')
                ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
                ->sum('final_amount');

            $depositGrowth = $previousPeriodDeposits > 0
                ? round((($currentPeriodDeposits - $previousPeriodDeposits) / $previousPeriodDeposits) * 100, 2)
                : 0;

            $recentActivities = [
                'deposits' => Deposit::with('user:id,name,email')
                    ->where('status', 'approved')
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn($d) => [
                        'type' => 'deposit',
                        'user' => $d->user->name ?? 'N/A',
                        'amount' => (float) $d->final_amount,
                        'date' => $d->created_at->format('Y-m-d H:i:s')
                    ]),
                'investments' => UserInvestment::with(['user:id,name,email', 'plan:id,name'])
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn($i) => [
                        'type' => 'investment',
                        'user' => $i->user->name ?? 'N/A',
                        'plan' => $i->plan->name ?? 'N/A',
                        'amount' => (float) $i->amount,
                        'date' => $i->created_at->format('Y-m-d H:i:s')
                    ]),
            ];

            return response()->json([
                'platform_stats' => $platformStats,
                'growth_rate' => $depositGrowth,
                'recent_activities' => $recentActivities,
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard stats error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch dashboard statistics'], 500);
        }
    }


    /**
     * @param Request $request
     * @return array
     */
    private function getDateRange(Request $request): array
    {
        $period = $request->get('period', 'month');

        if ($period === 'custom' && $request->date_from && $request->date_to) {
            return [$request->date_from, $request->date_to];
        }

        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}

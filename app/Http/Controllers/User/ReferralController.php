<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ReferralBonus;
use App\Models\User;
use App\Models\UserReferral;
use App\Models\ReferralCommission;
use App\Models\ReferralSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    /**
     * @return Response
     */
    public function index(): Response
    {
        try {
            $user = Auth::user();
            $referralData = $this->getReferralData($user);
            return Inertia::render('User/Referral/Index', $referralData);
        } catch (\Exception $e) {
            Log::error('Referral Dashboard Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getErrorReferral();
        }
    }


    /**
     * @param $user
     * @return array
     */
    private function getReferralData($user): array
    {
        $referralStats = $this->getReferralStats($user);
        $commissionTiers = $this->getCommissionTiers();
        $recentCommissions = $this->getRecentCommissions($user);
        $topReferrals = $this->getTopReferrals($user);
        $referralLink = $this->generateReferralLink($user);

        return [
            'referralStats' => $referralStats,
            'commissionTiers' => $commissionTiers,
            'recentCommissions' => $recentCommissions,
            'topReferrals' => $topReferrals,
            'referralCode' => $user->referral_code,
            'referralLink' => $referralLink,
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
     * @return array
     */
    private function getCommissionTiers(): array
    {
        return ReferralSetting::where('is_active', true)
            ->orderBy('level')
            ->select([
                'id',
                'level',
                'commission_rate',
                'applies_to',
                'commission_type',
                'fixed_amount'
            ])
            ->get()
            ->map(function ($tier) {
                return [
                    'id' => $tier->id,
                    'level' => $tier->level,
                    'commission_rate' => (float) $tier->commission_rate,
                    'applies_to' => $tier->applies_to,
                    'commission_type' => $tier->commission_type,
                    'fixed_amount' => (float) ($tier->fixed_amount ?? 0),
                ];
            })
            ->toArray();
    }


    /**
     * @param $user
     * @return array
     */
    private function getRecentCommissions($user): array
    {
        return ReferralCommission::where('referrer_id', $user->id)
            ->with('referredUser:id,name,email')
            ->select([
                'id',
                'referred_id',
                'type',
                'commission_amount',
                'status',
                'level',
                'created_at'
            ])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'referrer_name' => $commission->referredUser->name ?? 'Unknown User',
                    'type' => $commission->type,
                    'commission_amount' => (float) $commission->commission_amount,
                    'status' => $commission->status,
                    'level' => $commission->level,
                    'created_at' => $commission->created_at->toISOString(),
                ];
            })
            ->toArray();
    }


    /**
     * @param $user
     * @return array
     */
    private function getTopReferrals($user): array
    {
        return UserReferral::where('referrer_id', $user->id)
            ->with('referredUser:id,name,email')
            ->select([
                'id',
                'referred_id',
                'level',
                'total_commission_earned',
                'total_referral_investment',
                'status'
            ])
            ->orderByDesc('total_commission_earned')
            ->limit(10)
            ->get()
            ->map(function ($referral) {
                return [
                    'id' => $referral->id,
                    'name' => $referral->referredUser->name ?? 'Unknown User',
                    'level' => $referral->level,
                    'total_commission_earned' => (float) $referral->total_commission_earned,
                    'total_investment' => (float) $referral->total_referral_investment,
                    'status' => $referral->status,
                ];
            })
            ->toArray();
    }


    /**
     * @param $user
     * @return string
     */
    private function generateReferralLink($user): string
    {
        $baseUrl = config('app.url');
        return $baseUrl . '?ref=' . $user->referral_code;
    }

    /**
     * @return Response
     */
    private function getErrorReferral(): Response
    {
        $user = Auth::user();

        return Inertia::render('User/Referral/Index', [
            'referralStats' => [
                'totalReferrals' => 0,
                'activeReferrals' => 0,
                'totalEarned' => 0,
                'pendingCommission' => 0,
                'monthlyGrowth' => 0,
                'thisMonthEarnings' => 0,
                'teamInvestment' => 0,
            ],
            'commissionTiers' => [],
            'recentCommissions' => [],
            'topReferrals' => [],
            'referralCode' => $user->referral_code ?? '',
            'referralLink' => $this->generateReferralLink($user),
            'error' => 'Unable to load referral data. Please try again.'
        ]);
    }


    /**
     * @return Response
     */
    public function network(): Response
    {
        try {
            $user = Auth::user();
            $networkData = $this->buildNetworkTree($user);
            $stats = $this->calculateNetworkStats($user);
            $referralBonuses = ReferralBonus::where('is_active', true)
                ->orderBy('type')
                ->orderBy('min_active_referrals')
                ->get()
                ->map(function ($bonus) use ($user, $stats) {
                    $achievement = DB::table('user_referral_achievements')
                        ->where('user_id', $user->id)
                        ->where('bonus_id', $bonus->id)
                        ->first();

                    $activeReferrals = $stats['active_members'] ?? 0;
                    $teamInvestment = $stats['team_investment'] ?? 0;

                    $referralProgress = $bonus->min_active_referrals > 0
                        ? min(100, ($activeReferrals / $bonus->min_active_referrals) * 100)
                        : 100;

                    $investmentProgress = $bonus->min_team_investment > 0
                        ? min(100, ($teamInvestment / $bonus->min_team_investment) * 100)
                        : 100;

                    $isEligible = $activeReferrals >= $bonus->min_active_referrals
                        && $teamInvestment >= $bonus->min_team_investment;

                    return [
                        'id' => $bonus->id,
                        'name' => $bonus->name,
                        'description' => $bonus->description,
                        'type' => $bonus->type,
                        'min_active_referrals' => $bonus->min_active_referrals,
                        'min_team_investment' => $bonus->min_team_investment,
                        'time_frame_days' => $bonus->time_frame_days,
                        'reward_amount' => $bonus->reward_amount,
                        'reward_details' => $bonus->reward_details,
                        'is_recurring' => $bonus->is_recurring,
                        'is_claimed' => $achievement !== null,
                        'claimed_at' => $achievement->claimed_at ?? null,
                        'is_eligible' => $isEligible && !$achievement,
                        'referral_progress' => round($referralProgress, 1),
                        'investment_progress' => round($investmentProgress, 1),
                    ];
                });

            return Inertia::render('User/Referral/Network', [
                'network' => $networkData,
                'stats' => $stats,
                'referralBonuses' => $referralBonuses
            ]);
        } catch (\Exception $e) {
            Log::error('Referral Network Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('User/Referral/Network', [
                'network' => [],
                'stats' => [
                    'total_network' => 0,
                    'active_members' => 0,
                    'total_earnings' => 0,
                    'network_depth' => 0
                ],
                'referralBonuses' => [],
                'error' => 'Unable to load network data.'
            ]);
        }
    }

    /**
     * @param User $user
     * @param int $currentLevel
     * @param int $maxLevel
     * @return array
     */
    private function buildNetworkTree($user, int $currentLevel = 1, int $maxLevel = 10): array
    {
        if ($currentLevel > $maxLevel) {
            return [];
        }

        $directReferrals = User::where('referred_by', $user->id)
            ->where('status', 'active')
            ->select('id', 'name', 'email', 'status', 'created_at')
            ->get();

        return $directReferrals->map(function ($referredUser) use ($user, $currentLevel, $maxLevel) {
            $referralData = UserReferral::where('referrer_id', $user->id)
                ->where('referred_id', $referredUser->id)
                ->where('level', 1)
                ->first();

            $children = $this->buildNetworkTree($referredUser, $currentLevel + 1, $maxLevel);
            $generationCount = $this->countAllReferrals($referredUser);

            return [
                'id' => $referredUser->id,
                'name' => $referredUser->name,
                'email' => $referredUser->email,
                'level' => $currentLevel,
                'joined_at' => $referredUser->created_at->toISOString(),
                'status' => $referredUser->status,
                'total_earned' => $referralData ? (float) $referralData->total_commission_earned : 0.00,
                'total_investment' => $referralData ? (float) $referralData->total_referral_investment : 0.00,
                'generation_count' => $generationCount,
                'children' => $children
            ];
        })->toArray();
    }

    /**
     * @param User $user
     * @return int
     */
    private function countAllReferrals($user): int
    {
        $directReferrals = User::where('referred_by', $user->id)->count();

        if ($directReferrals === 0) {
            return 0;
        }

        $children = User::where('referred_by', $user->id)->get();
        $totalGenerations = $directReferrals;

        foreach ($children as $child) {
            $totalGenerations += $this->countAllReferrals($child);
        }

        return $totalGenerations;
    }

    /**
     * @param User $user
     * @return array
     */
    private function calculateNetworkStats($user): array
    {
        $allReferralIds = $this->getAllReferralIds($user);
        $totalNetwork = count($allReferralIds);

        $activeMembers = User::whereIn('id', $allReferralIds)
            ->where('status', 'active')
            ->count();

        $totalEarnings = UserReferral::where('referrer_id', $user->id)
            ->sum('total_commission_earned');

        $networkDepth = $this->calculateNetworkDepth($user);

        return [
            'total_network' => $totalNetwork,
            'active_members' => $activeMembers,
            'total_earnings' => (float) $totalEarnings,
            'network_depth' => $networkDepth
        ];
    }

    /**
     * @param User $user
     * @return array
     */
    private function getAllReferralIds($user): array
    {
        $directReferrals = User::where('referred_by', $user->id)->pluck('id')->toArray();

        if (empty($directReferrals)) {
            return [];
        }

        $allIds = $directReferrals;

        foreach ($directReferrals as $referralId) {
            $referredUser = User::find($referralId);
            if ($referredUser) {
                $childIds = $this->getAllReferralIds($referredUser);
                $allIds = array_merge($allIds, $childIds);
            }
        }

        return $allIds;
    }

    /**
     * @param User $user
     * @param int $currentDepth
     * @return int
     */
    private function calculateNetworkDepth($user, int $currentDepth = 0): int
    {
        $directReferrals = User::where('referred_by', $user->id)->get();
        if ($directReferrals->isEmpty()) {
            return $currentDepth;
        }

        $maxDepth = $currentDepth + 1;

        foreach ($directReferrals as $referral) {
            $childDepth = $this->calculateNetworkDepth($referral, $currentDepth + 1);
            $maxDepth = max($maxDepth, $childDepth);
        }

        return $maxDepth;
    }

    /**
     * @return JsonResponse
     */
    public function levelBreakdown(): JsonResponse
    {
        try {
            $user = Auth::user();
            $breakdown = [];
            for ($level = 1; $level <= 10; $level++) {
                $referralsAtLevel = $this->getReferralsAtLevel($user, $level);

                if (empty($referralsAtLevel)) {
                    break;
                }

                $breakdown[] = [
                    'level' => $level,
                    'count' => count($referralsAtLevel),
                    'active' => User::whereIn('id', $referralsAtLevel)
                        ->where('status', 'active')
                        ->count(),
                    'total_earned' => UserReferral::where('referrer_id', $user->id)
                        ->whereIn('referred_id', $referralsAtLevel)
                        ->sum('total_commission_earned')
                ];
            }

            return response()->json($breakdown);
        } catch (\Exception $e) {
            Log::error('Level Breakdown Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to load level breakdown'], 500);
        }
    }

    /**
     * @param User $user
     * @param int $targetLevel
     * @param int $currentLevel
     * @return array
     */
    private function getReferralsAtLevel($user, int $targetLevel, int $currentLevel = 0): array
    {
        if ($currentLevel === $targetLevel) {
            return [$user->id];
        }

        $directReferrals = User::where('referred_by', $user->id)->get();
        if ($directReferrals->isEmpty()) {
            return [];
        }

        $referralsAtLevel = [];
        foreach ($directReferrals as $referral) {
            if ($currentLevel + 1 === $targetLevel) {
                $referralsAtLevel[] = $referral->id;
            } else {
                $childReferrals = $this->getReferralsAtLevel($referral, $targetLevel, $currentLevel + 1);
                $referralsAtLevel = array_merge($referralsAtLevel, $childReferrals);
            }
        }

        return $referralsAtLevel;
    }


    /**
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $user = Auth::user();
            $stats = $this->getReferralStats($user);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Referral Statistics API Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch referral statistics'
            ], 500);
        }
    }


    /**
     * @return Response
     */
    public function commissions(): Response
    {
        try {
            $user = Auth::user();

            $commissions = ReferralCommission::where('referrer_id', $user->id)
                ->with('referredUser:id,name,email')
                ->select([
                    'id',
                    'referred_id',
                    'type',
                    'base_amount',
                    'commission_rate',
                    'commission_amount',
                    'level',
                    'status',
                    'created_at',
                    'approved_at',
                    'paid_at'
                ])
                ->orderByDesc('created_at')
                ->paginate(10)
                ->through(function ($commission) {
                    return [
                        'id' => $commission->id,
                        'referrer_name' => $commission->referredUser->name ?? 'Unknown User',
                        'referrer_email' => $commission->referredUser->email ?? '',
                        'type' => $commission->type,
                        'base_amount' => (float) $commission->base_amount,
                        'commission_rate' => (float) $commission->commission_rate,
                        'commission_amount' => (float) $commission->commission_amount,
                        'level' => $commission->level,
                        'status' => $commission->status,
                        'created_at' => $commission->created_at->toISOString(),
                        'approved_at' => $commission->approved_at?->toISOString(),
                        'paid_at' => $commission->paid_at?->toISOString(),
                    ];
                });

            $totalCommissions = ReferralCommission::where('referrer_id', $user->id)
                ->sum('commission_amount');

            $paidCommissions = ReferralCommission::where('referrer_id', $user->id)
                ->where('status', 'paid')
                ->sum('commission_amount');

            $pendingCommissions = ReferralCommission::where('referrer_id', $user->id)
                ->where('status', 'pending')
                ->sum('commission_amount');

            $approvedCommissions = ReferralCommission::where('referrer_id', $user->id)
                ->where('status', 'approved')
                ->sum('commission_amount');

            return Inertia::render('User/Referral/Commissions', [
                'commissions' => $commissions,
                'summary' => [
                    'total' => (float) $totalCommissions,
                    'paid' => (float) $paidCommissions,
                    'pending' => (float) $pendingCommissions,
                    'approved' => (float) $approvedCommissions,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Referral Commissions Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('User/Referral/Commissions', [
                'commissions' => [
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 10,
                    'total' => 0
                ],
                'summary' => [
                    'total' => 0,
                    'paid' => 0,
                    'pending' => 0,
                    'approved' => 0,
                ],
                'error' => 'Unable to load commission data.'
            ]);
        }
    }

    /**
     * @return Response
     */
    public function referrals(): Response
    {
        try {
            $user = Auth::user();

            $referrals = UserReferral::where('referrer_id', $user->id)
                ->with('referredUser:id,name,email,created_at,status')
                ->select([
                    'id',
                    'referred_id',
                    'level',
                    'status',
                    'total_commission_earned',
                    'total_referral_investment',
                    'generation_count',
                    'created_at'
                ])
                ->orderByDesc('created_at')
                ->paginate(10)
                ->through(function ($referral) {
                    return [
                        'id' => $referral->id,
                        'name' => $referral->referredUser->name ?? 'Unknown User',
                        'email' => $referral->referredUser->email ?? '',
                        'level' => $referral->level,
                        'status' => $referral->status,
                        'total_commission_earned' => (float) $referral->total_commission_earned,
                        'total_referral_investment' => (float) $referral->total_referral_investment,
                        'generation_count' => $referral->generation_count,
                        'joined_at' => $referral->referredUser->created_at?->toISOString() ?? $referral->created_at->toISOString(),
                        'user_status' => $referral->referredUser->status ?? 'unknown',
                    ];
                });

            $totalReferrals = UserReferral::where('referrer_id', $user->id)->count();
            $activeReferrals = UserReferral::where('referrer_id', $user->id)
                ->where('status', 'active')
                ->count();

            $totalEarnings = UserReferral::where('referrer_id', $user->id)
                ->sum('total_commission_earned');

            $totalInvestments = UserReferral::where('referrer_id', $user->id)
                ->where('status', 'active')
                ->sum('total_referral_investment');

            $levelBreakdown = UserReferral::where('referrer_id', $user->id)
                ->where('status', 'active')
                ->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->orderBy('level')
                ->get()
                ->pluck('count', 'level')
                ->toArray();

            return Inertia::render('User/Referral/List', [
                'referrals' => $referrals,
                'summary' => [
                    'total' => $totalReferrals,
                    'active' => $activeReferrals,
                    'totalEarnings' => (float) $totalEarnings,
                    'totalInvestments' => (float) $totalInvestments,
                    'levelBreakdown' => $levelBreakdown,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Referral List Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('User/Referral/List', [
                'referrals' => [
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 10,
                    'total' => 0
                ],
                'summary' => [
                    'total' => 0,
                    'active' => 0,
                    'totalEarnings' => 0,
                    'totalInvestments' => 0,
                    'levelBreakdown' => [],
                ],
                'error' => 'Unable to load referral data.'
            ]);
        }
    }

    /**
     * @return Response
     */
    public function referralLevelBreakdown(): Response
    {
        $user = Auth::user();
        $allLevels = ReferralSetting::where('is_active', true)
            ->orderBy('level', 'asc')
            ->get(['level', 'commission_rate', 'min_referrals_required', 'min_investment_required', 'applies_to']);

        $activeReferrals = UserReferral::where('referrer_id', $user->id)
            ->where('status', 'active')
            ->count();

        $totalInvestment = UserReferral::where('referrer_id', $user->id)
            ->sum('total_referral_investment');

        $userStats = [
            'activeReferrals' => $activeReferrals,
            'totalInvestment' => (float) $totalInvestment
        ];

        return Inertia::render('User/Referral/Level', [
            'allLevels' => $allLevels,
            'userStats' => $userStats,
        ]);
    }
}

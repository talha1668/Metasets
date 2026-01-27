<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralSetting;
use App\Models\ReferralBonus;
use App\Models\UserReferral;
use App\Models\ReferralCommission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{

    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function index(Request $request): Response | RedirectResponse
    {
        try {
            $stats = [
                'total_referrals' => UserReferral::count(),
                'active_referrals' => UserReferral::where('status', 'active')->count(),
                'total_commissions_paid' => ReferralCommission::where('status', 'paid')->sum('commission_amount'),
                'pending_commissions' => ReferralCommission::where('status', 'pending')->sum('commission_amount'),
                'total_levels' => ReferralSetting::where('is_active', true)->count(),
                'total_bonuses' => ReferralBonus::where('is_active', true)->count(),
                'top_referrers' => User::withCount('referrals')
                    ->orderBy('referrals_count', 'desc')
                    ->limit(10)
                    ->get(),
                'recent_commissions' => ReferralCommission::with(['referrer', 'referred'])
                    ->latest()
                    ->limit(10)
                    ->get(),
            ];

            return Inertia::render('Admin/Referrals/Index', [
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to load referral overview. Please try again.']);
        }
    }


    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function settings(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:5|max:100',
            'sort_field' => 'nullable|in:level,commission_rate,commission_type,applies_to,is_active',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        try {
            $perPage = $request->get('per_page', 20);
            $sortField = $request->get('sort_field', 'level');
            $sortDirection = $request->get('sort_direction', 'asc');

            $settings = ReferralSetting::orderBy($sortField, $sortDirection)
                ->paginate($perPage);

            return Inertia::render('Admin/Referrals/Settings', [
                'settings' => $settings->items(),
                'meta' => [
                    'total' => $settings->total(),
                    'current_page' => $settings->currentPage(),
                    'per_page' => $settings->perPage(),
                    'last_page' => $settings->lastPage(),
                ],
                'filters' => $request->only(['sort_field', 'sort_direction']),
                'commission_type_options' => [
                    ['value' => 'percentage', 'label' => 'Percentage'],
                    ['value' => 'fixed', 'label' => 'Fixed Amount'],
                ],
                'applies_to_options' => [
                    ['value' => 'deposit', 'label' => 'Deposit Only'],
                    ['value' => 'profit', 'label' => 'Profit Only'],
                    ['value' => 'both', 'label' => 'Both Deposit & Profit'],
                ],
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to load referral settings. Please try again.']);
        }
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeSetting(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'level' => 'required|integer|min:1|max:50|unique:referral_settings,level',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_type' => 'required|in:percentage,fixed',
            'fixed_amount' => 'nullable|numeric|min:0',
            'applies_to' => 'required|in:deposit,profit,both',
            'min_referrals_required' => 'nullable|integer|min:0',
            'min_investment_required' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            $data['fixed_amount'] = $data['fixed_amount'] ?? 0;
            $data['min_referrals_required'] = $data['min_referrals_required'] ?? 0;
            $data['min_investment_required'] = $data['min_investment_required'] ?? 0;

            ReferralSetting::create($data);

            return back()->with('success', 'Referral level setting created successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create referral setting. Please try again.']);
        }
    }


    /**
     * @param Request $request
     * @param ReferralSetting $setting
     * @return RedirectResponse
     */
    public function updateSetting(Request $request, ReferralSetting $setting): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'level' => 'required|integer|min:1|max:50|unique:referral_settings,level,' . $setting->id,
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_type' => 'required|in:percentage,fixed',
            'fixed_amount' => 'nullable|numeric|min:0',
            'applies_to' => 'required|in:deposit,profit,both',
            'min_referrals_required' => 'nullable|integer|min:0',
            'min_investment_required' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['is_active'] = $request->boolean('is_active');
            $data['fixed_amount'] = $data['fixed_amount'] ?? 0;
            $data['min_referrals_required'] = $data['min_referrals_required'] ?? 0;
            $data['min_investment_required'] = $data['min_investment_required'] ?? 0;

            $setting->update($data);

            return back()->with('success', 'Referral setting updated successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update referral setting. Please try again.']);
        }
    }

    /**
     * @param ReferralSetting $setting
     * @return RedirectResponse
     */
    public function destroySetting(ReferralSetting $setting): RedirectResponse
    {
        try {
            $setting->delete();
            return back()->with('success', 'Referral setting deleted successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete referral setting. Please try again.']);
        }
    }


    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function bonuses(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:5|max:100',
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|in:milestone,achievement,loyalty',
            'status' => 'nullable|in:active,inactive',
            'sort_field' => 'nullable|in:name,type,reward_amount,min_active_referrals,is_active,created_at',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $type = $request->get('type');
            $status = $request->get('status');
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $query = ReferralBonus::query();
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($type) {
                $query->where('type', $type);
            }

            if ($status) {
                $isActive = $status === 'active';
                $query->where('is_active', $isActive);
            }

            $bonuses = $query->withCount([
                'achievements',
                'achievements as claimed_count' => function ($q) {
                    $q->where('status', 'claimed');
                },
                'achievements as earned_count' => function ($q) {
                    $q->where('status', 'earned');
                }
            ])
                ->orderBy($sortField, $sortDirection)
                ->paginate($perPage);

            return Inertia::render('Admin/Referrals/Bonuses', [
                'bonuses' => $bonuses->items(),
                'meta' => [
                    'total' => $bonuses->total(),
                    'current_page' => $bonuses->currentPage(),
                    'per_page' => $bonuses->perPage(),
                    'last_page' => $bonuses->lastPage(),
                ],
                'filters' => $request->only(['search', 'type', 'status', 'sort_field', 'sort_direction']),
                'type_options' => [
                    ['value' => 'milestone', 'label' => 'Milestone'],
                    ['value' => 'achievement', 'label' => 'Achievement'],
                    ['value' => 'loyalty', 'label' => 'Loyalty'],
                ],
                'reward_type_options' => [
                    ['value' => 'fixed_amount', 'label' => 'Fixed Amount'],
                    ['value' => 'percentage', 'label' => 'Percentage'],
                    ['value' => 'upgrade', 'label' => 'Account Upgrade'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Referral bonuses loading error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Unable to load referral bonuses. Please try again.']);
        }
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeBonus(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'required|string',
            'type' => 'required|in:milestone,achievement,loyalty',
            'min_active_referrals' => 'nullable|integer|min:0',
            'min_team_investment' => 'nullable|numeric|min:0',
            'time_frame_days' => 'nullable|integer|min:0',
            'reward_type' => 'required|in:fixed_amount,percentage,upgrade',
            'reward_amount' => 'nullable|numeric|min:0',
            'reward_details' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_recurring' => 'nullable|boolean',
            'max_claims_per_user' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            $data['is_recurring'] = $request->boolean('is_recurring', false);
            $data['min_active_referrals'] = $data['min_active_referrals'] ?? 0;
            $data['min_team_investment'] = $data['min_team_investment'] ?? 0;
            $data['time_frame_days'] = $data['time_frame_days'] ?? 0;
            $data['reward_amount'] = $data['reward_amount'] ?? 0;
            $data['max_claims_per_user'] = $data['max_claims_per_user'] ?? 1;

            ReferralBonus::create($data);
            return back()->with('success', 'Referral bonus created successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create referral bonus. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @param ReferralBonus $bonus
     * @return RedirectResponse
     */
    public function updateBonus(Request $request, ReferralBonus $bonus): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'required|string',
            'type' => 'required|in:milestone,achievement,loyalty',
            'min_active_referrals' => 'nullable|integer|min:0',
            'min_team_investment' => 'nullable|numeric|min:0',
            'time_frame_days' => 'nullable|integer|min:0',
            'reward_type' => 'required|in:fixed_amount,percentage,upgrade',
            'reward_amount' => 'nullable|numeric|min:0',
            'reward_details' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_recurring' => 'nullable|boolean',
            'max_claims_per_user' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['is_active'] = $request->boolean('is_active');
            $data['is_recurring'] = $request->boolean('is_recurring');
            $data['min_active_referrals'] = $data['min_active_referrals'] ?? 0;
            $data['min_team_investment'] = $data['min_team_investment'] ?? 0;
            $data['time_frame_days'] = $data['time_frame_days'] ?? 0;
            $data['reward_amount'] = $data['reward_amount'] ?? 0;
            $data['max_claims_per_user'] = $data['max_claims_per_user'] ?? 1;

            $bonus->update($data);

            return back()->with('success', 'Referral bonus updated successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update referral bonus. Please try again.']);
        }
    }


    /**
     * @param ReferralBonus $bonus
     * @return RedirectResponse
     */
    public function destroyBonus(ReferralBonus $bonus): RedirectResponse
    {
        try {
            $bonus->delete();
            return back()->with('success', 'Referral bonus deleted successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete referral bonus. Please try again.']);
        }
    }


    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function commissions(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:5|max:100',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,approved,paid',
            'type' => 'nullable|in:deposit,investment_profit,reinvestment',
            'level' => 'nullable|integer|min:1',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_field' => 'nullable|in:commission_amount,level,type,status,created_at',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $status = $request->get('status');
            $type = $request->get('type');
            $level = $request->get('level');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $query = ReferralCommission::with(['referrer', 'referred', 'investment']);

            if ($search) {
                $query->whereHas('referrer', function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                })->orWhereHas('referred', function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($type) {
                $query->where('type', $type);
            }

            if ($level) {
                $query->where('level', $level);
            }

            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            $commissions = $query->orderBy($sortField, $sortDirection)
                ->paginate($perPage);

            $stats = [
                'total_commissions' => ReferralCommission::sum('commission_amount'),
                'pending_commissions' => ReferralCommission::where('status', 'pending')->sum('commission_amount'),
                'approved_commissions' => ReferralCommission::where('status', 'approved')->sum('commission_amount'),
                'paid_commissions' => ReferralCommission::where('status', 'paid')->sum('commission_amount'),
            ];

            return Inertia::render('Admin/Referrals/Commissions', [
                'commissions' => $commissions->items(),
                'meta' => [
                    'total' => $commissions->total(),
                    'current_page' => $commissions->currentPage(),
                    'per_page' => $commissions->perPage(),
                    'last_page' => $commissions->lastPage(),
                ],
                'stats' => $stats,
                'filters' => $request->only(['search', 'status', 'type', 'level', 'date_from', 'date_to', 'sort_field', 'sort_direction']),
                'status_options' => [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'approved', 'label' => 'Approved'],
                    ['value' => 'paid', 'label' => 'Paid'],
                ],
                'type_options' => [
                    ['value' => 'deposit', 'label' => 'Deposit Commission'],
                    ['value' => 'investment_profit', 'label' => 'Investment Profit'],
                    ['value' => 'reinvestment', 'label' => 'Reinvestment'],
                ],
                'level_options' => ReferralSetting::orderBy('level')->pluck('level')->map(function ($level) {
                    return ['value' => $level, 'label' => "Level {$level}"];
                }),
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to load referral commissions. Please try again.']);
        }
    }


    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function network(Request $request): Response|RedirectResponse
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'max_levels' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            $userId = $request->get('user_id');
            $maxLevels = $request->get('max_levels', 5);

            if ($userId) {
                $user = User::find($userId);

                if (!$user) {
                    return back()->withErrors(['error' => 'User not found.']);
                }

                $network = $this->buildReferralNetwork($user, $maxLevels);
            } else {
                $network = null;
                $user = null;
            }

            $topReferrers = User::withCount('referredUsers')
            ->orderBy('referred_users_count', 'desc')
                ->where('role', 'user')
                ->limit(20)
                ->get();

            return Inertia::render('Admin/Referrals/Network', [
                'user' => $user,
                'network' => $network,
                'top_referrers' => $topReferrers,
                'max_levels' => $maxLevels,
            ]);
        } catch (\Exception $e) {
            \Log::error('Referral network error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Unable to load referral network.']);
        }
    }

    /**
     * @param User $user
     * @param int $maxLevels
     * @param int $currentLevel
     * @return array|null
     */
    private function buildReferralNetwork(User $user, int $maxLevels, int $currentLevel = 0): ?array
    {
        if ($currentLevel >= $maxLevels) {
            return null;
        }

        $directReferrals = User::where('referred_by', $user->id)
            ->where('role', 'user')
            ->where('status', 'active')
            ->with('wallet:user_id,total_invested,total_referral_bonus')
            ->select('id', 'uid', 'name', 'email', 'created_at')
            ->get();

        $referralsCount = $directReferrals->count();

        $network = [
            'user' => [
                'id' => $user->id,
                'uid' => $user->uid,
                'username' => $user->name,
                'name' => $user->name,
                'email' => $user->email,
                'total_invested' => $user->wallet->total_invested ?? 0,
                'total_referral_bonus' => $user->wallet->total_referral_bonus ?? 0,
                'referrals_count' => $referralsCount,
                'created_at' => $user->created_at,
            ],
            'level' => $currentLevel,
            'referrals' => [],
        ];

        foreach ($directReferrals as $referredUser) {
            $childNetwork = $this->buildReferralNetwork(
                $referredUser,
                $maxLevels,
                $currentLevel + 1
            );

            if ($childNetwork) {
                $network['referrals'][] = $childNetwork;
            }
        }

        return $network;
    }
}

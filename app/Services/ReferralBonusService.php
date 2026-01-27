<?php

namespace App\Services;

use App\Models\ReferralBonus;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserReferral;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReferralBonusService
{
    /**
     * @param User $user
     * @return array
     */
    public function processUserBonuses(User $user): array
    {
        $processed = [];
        $errors = [];

        try {
            $stats = $this->getUserStats($user);
            $bonuses = ReferralBonus::where('is_active', true)->get();

            foreach ($bonuses as $bonus) {
                try {
                    $result = $this->processSingleBonus($user, $bonus, $stats);
                    if ($result['eligible'] && $result['claimed']) {
                        $processed[] = $result;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'bonus_id' => $bonus->id,
                        'bonus_name' => $bonus->name,
                        'error' => $e->getMessage()
                    ];
                    Log::error('Error processing bonus', [
                        'user_id' => $user->id,
                        'bonus_id' => $bonus->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return [
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'total_processed' => count($processed)
            ];

        } catch (\Exception $e) {
            Log::error('Error in processUserBonuses', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'processed' => $processed,
                'errors' => array_merge($errors, [['error' => $e->getMessage()]]),
                'total_processed' => 0
            ];
        }
    }

    /**
     * @param User $user
     * @param ReferralBonus $bonus
     * @param array|null $stats
     * @return array
     * @throws \Exception
     */
    public function processSingleBonus(User $user, ReferralBonus $bonus, array $stats = null): array
    {
        if (!$stats) {
            $stats = $this->getUserStats($user);
        }

        $existingClaim = DB::table('user_referral_achievements')
            ->where('user_id', $user->id)
            ->where('bonus_id', $bonus->id)
            ->count();

        if ($existingClaim >= $bonus->max_claims_per_user) {
            return [
                'eligible' => false,
                'claimed' => false,
                'reason' => 'Max claims reached',
                'bonus_name' => $bonus->name
            ];
        }

        $isEligible = $this->checkEligibility($user, $bonus, $stats);
        if (!$isEligible['eligible']) {
            return [
                'eligible' => false,
                'claimed' => false,
                'reason' => $isEligible['reason'],
                'bonus_name' => $bonus->name
            ];
        }

        if ($bonus->is_recurring && $bonus->time_frame_days > 0) {
            $lastClaim = DB::table('user_referral_achievements')
                ->where('user_id', $user->id)
                ->where('bonus_id', $bonus->id)
                ->orderBy('claimed_at', 'desc')
                ->first();

            if ($lastClaim) {
                $daysSinceLastClaim = now()->diffInDays($lastClaim->claimed_at);
                if ($daysSinceLastClaim < $bonus->time_frame_days) {
                    return [
                        'eligible' => false,
                        'claimed' => false,
                        'reason' => 'Time frame not met. Days since last claim: ' . $daysSinceLastClaim,
                        'bonus_name' => $bonus->name
                    ];
                }
            }
        }

        DB::beginTransaction();
        try {
            $this->creditBonus($user, $bonus);
            DB::table('user_referral_achievements')->insert([
                'user_id' => $user->id,
                'bonus_id' => $bonus->id,
                'claimed_at' => now(),
                'reward_amount' => $bonus->reward_amount,
                'earned_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Referral bonus processed successfully', [
                'user_id' => $user->id,
                'bonus_id' => $bonus->id,
                'bonus_name' => $bonus->name,
                'amount' => $bonus->reward_amount
            ]);

            return [
                'eligible' => true,
                'claimed' => true,
                'bonus_name' => $bonus->name,
                'amount' => $bonus->reward_amount,
                'message' => "Successfully claimed {$bonus->name}!"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error claiming bonus', [
                'user_id' => $user->id,
                'bonus_id' => $bonus->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * @param User $user
     * @param ReferralBonus $bonus
     * @param array $stats
     * @return array
     */
    private function checkEligibility(User $user, ReferralBonus $bonus, array $stats): array
    {
        if ($bonus->min_active_referrals > 0) {
            if ($stats['active_referrals'] < $bonus->min_active_referrals) {
                return [
                    'eligible' => false,
                    'reason' => "Need {$bonus->min_active_referrals} active referrals, have {$stats['active_referrals']}"
                ];
            }
        }

        if ($bonus->min_team_investment > 0) {
            if ($stats['team_investment'] < $bonus->min_team_investment) {
                return [
                    'eligible' => false,
                    'reason' => "Need \${$bonus->min_team_investment} team investment, have \${$stats['team_investment']}"
                ];
            }
        }

        if ($bonus->time_frame_days > 0 && $bonus->type === 'achievement') {
            $maintained = $this->checkTimeFrameMaintenance($user, $bonus);
            if (!$maintained) {
                return [
                    'eligible' => false,
                    'reason' => "Requirements not maintained for {$bonus->time_frame_days} days"
                ];
            }
        }

        return ['eligible' => true];
    }

    /**
     * @param User $user
     * @return array
     */
    private function getUserStats(User $user): array
    {
        $activeReferrals = UserReferral::where('referrer_id', $user->id)
            ->where('status', 'active')
            ->count();

        $teamInvestment = UserReferral::where('referrer_id', $user->id)
            ->where('status', 'active')
            ->sum('total_referral_investment');

        return [
            'active_referrals' => $activeReferrals,
            'team_investment' => (float) $teamInvestment
        ];
    }

    /**
     * @param User $user
     * @param ReferralBonus $bonus
     * @return void
     */
    private function creditBonus(User $user, ReferralBonus $bonus): void
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'total_invested' => 0,
                'total_earnings' => 0,
                'total_referral_bonus' => 0
            ]
        );

        $wallet->balance += $bonus->reward_amount;
        $wallet->total_referral_bonus += $bonus->reward_amount;
        $wallet->save();

        $wallet->refresh();
        Transaction::create([
            'transaction_id' => 'BONUS' . strtoupper(Str::random(10)),
            'user_id' => $user->id,
            'type' => 'bonus',
            'amount' => $bonus->reward_amount,
            'fee' => 0,
            'post_balance' => $wallet->balance,
            'status' => 'completed',
            'details' => "Referral bonus: {$bonus->name} - {$bonus->description}"
        ]);

        Log::info('Bonus credited to wallet', [
            'user_id' => $user->id,
            'bonus_id' => $bonus->id,
            'amount' => $bonus->reward_amount,
            'new_balance' => $wallet->balance
        ]);
    }

    /**
     * @param User $user
     * @param ReferralBonus $bonus
     * @return bool
     */
    private function checkTimeFrameMaintenance(User $user, ReferralBonus $bonus): bool
    {
        if ($bonus->time_frame_days > 0) {
            $memberSince = $user->created_at;
            $daysSinceMember = now()->diffInDays($memberSince);

            if ($daysSinceMember < $bonus->time_frame_days) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function processAllUsersBonuses(): array
    {
        $results = [
            'total_users' => 0,
            'total_bonuses_claimed' => 0,
            'total_amount_credited' => 0,
            'errors' => []
        ];

        try {
            $users = User::get();
            $results['total_users'] = $users->count();

            Log::info('Batch bonus processing completed', $results);

            foreach ($users as $user) {
                try {
                    $result = $this->processUserBonuses($user);

                    $results['total_bonuses_claimed'] += $result['total_processed'];

                    foreach ($result['processed'] as $processed) {
                        $results['total_amount_credited'] += $processed['amount'] ?? 0;
                    }

                    if (!empty($result['errors'])) {
                        $results['errors'] = array_merge($results['errors'], $result['errors']);
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info('Batch bonus processing completed', $results);

            return $results;

        } catch (\Exception $e) {
            Log::error('Error in processAllUsersBonuses', [
                'error' => $e->getMessage()
            ]);

            return array_merge($results, [
                'errors' => [['error' => $e->getMessage()]]
            ]);
        }
    }
}

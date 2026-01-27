<?php

namespace App\Services;

use App\Models\ReferralCommission;
use App\Models\ReferralSetting;
use App\Models\ReferralBonus;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserReferral;
use App\Models\UserReferralAchievement;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReferralService
{
    /**
     * @return bool
     */
    protected function isReferralSystemEnabled(): bool
    {
        return (bool) Setting::where('key', 'referral_system_enabled')->value('value') ?? false;
    }

    /**
     * @return bool
     */
    protected function shouldAutoApprove(): bool
    {
        return (bool) Setting::where('key', 'referral_commission_auto_approve')->value('value') ?? true;
    }

    /**
     * @param int $referredUserId
     * @param float $amount
     * @return bool
     * @throws Exception
     */
    public function processDepositReferral(int $referredUserId, float $amount): bool
    {
        if (!$this->isReferralSystemEnabled()) {
            return false;
        }

        $referredUser = User::find($referredUserId);
        if (!$referredUser || !$referredUser->referred_by) {
            return false;
        }

        try {
            $this->processMultiLevelCommissions($referredUser, $amount, 'deposit');
            $this->checkAndAwardBonuses($referredUser->referred_by);

            return true;

        } catch (\Exception $e) {
            Log::error('Deposit referral processing failed', [
                'referred_user_id' => $referredUserId,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }

    /**
     * @param User $user
     * @param float $amount
     * @param UserInvestment $investment
     * @return void
     */
    public function processInvestmentCommission(User $user, float $amount, UserInvestment $investment): void
    {
        if (!$this->isReferralSystemEnabled()) {
            return;
        }

        if (!$user->referred_by) {
            return;
        }

        try {
            $this->processMultiLevelCommissions($user, $amount, 'investment_profit', $investment->id);
            $this->updateReferralInvestmentStats($user, $amount);
            $this->checkAndAwardBonuses($user->referred_by);

        } catch (\Exception $e) {
            Log::error('Investment referral commission processing failed', [
                'user_id' => $user->id,
                'investment_id' => $investment->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param User $referredUser
     * @param float $amount
     * @param string $type
     * @param int|null $investmentId
     * @return void
     * @throws Exception
     */
    protected function processMultiLevelCommissions(User $referredUser, float $amount, string $type = 'deposit', ?int $investmentId = null): void
    {
        $currentUser = $referredUser;
        $uplinePosition = 1;
        $maxLevels = 10;

        while ($currentUser->referred_by && $uplinePosition <= $maxLevels) {
            $referrer = User::find($currentUser->referred_by);
            if (!$referrer) {
                break;
            }

            $qualificationSetting = $this->determineReferrerQualificationLevel($referrer, $type);

            if (!$qualificationSetting) {
                $currentUser = $referrer;
                $uplinePosition++;
                continue;
            }

            $commissionAmount = $this->calculateCommission($amount, $qualificationSetting);
            if ($commissionAmount > 0) {
                $this->createAndPayCommission(
                    $referrer,
                    $currentUser,
                    $investmentId,
                    $uplinePosition,
                    $amount,
                    $qualificationSetting->commission_rate,
                    $commissionAmount,
                    $type
                );
            }

            $currentUser = $referrer;
            $uplinePosition++;
        }
    }


    protected function determineReferrerQualificationLevel(User $referrer, string $type): ?ReferralSetting
    {
        $settings = ReferralSetting::where('is_active', true)
            ->where(function($query) use ($type) {
                $query->where('applies_to', 'both')
                    ->orWhere('applies_to', $type === 'deposit' ? 'deposit' : 'profit');
            })
            ->orderBy('level', 'desc')
            ->get();

        $activeReferrals = UserReferral::where('referrer_id', $referrer->id)
            ->where('status', 'active')
            ->count();

        $totalInvestment = UserReferral::where('referrer_id', $referrer->id)
            ->sum('total_referral_investment');

        foreach ($settings as $setting) {
            if ($activeReferrals >= $setting->min_referrals_required
                && $totalInvestment >= $setting->min_investment_required) {
                return $setting;
            }
        }

        return null;
    }

    /**
     * @param float $baseAmount
     * @param ReferralSetting $settings
     * @return float
     */
    protected function calculateCommission(float $baseAmount, ReferralSetting $settings): float
    {
        if ($settings->commission_type === 'fixed') {
            return (float) $settings->fixed_amount;
        }

        return ($baseAmount * $settings->commission_rate) / 100;
    }

    /**
     * @param User $referrer
     * @param User $referred
     * @param int|null $investmentId
     * @param int $level
     * @param float $baseAmount
     * @param float $commissionRate
     * @param float $commissionAmount
     * @param string $type
     * @return void
     * @throws Exception
     */
    protected function createAndPayCommission(
        User $referrer,
        User $referred,
        ?int $investmentId,
        int $level,
        float $baseAmount,
        float $commissionRate,
        float $commissionAmount,
        string $type
    ): void {
        DB::beginTransaction();
        try {
            $autoApprove = $this->shouldAutoApprove();
            $status = $autoApprove ? 'approved' : 'pending';
            $approvedAt = $autoApprove ? now() : null;
            $paidAt = $autoApprove ? now() : null;

            $commission = ReferralCommission::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $referred->id,
                'investment_id' => $investmentId,
                'level' => $level,
                'base_amount' => $baseAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'currency' => Setting::get('default_currency', 'USD'),
                'type' => $type,
                'status' => $status,
                'approved_at' => $approvedAt,
                'paid_at' => $paidAt,
                'description' => "Level {$level} {$type} commission from {$referred->name}",
                'calculation_details' => json_encode([
                    'base_amount' => $baseAmount,
                    'rate' => $commissionRate,
                    'level' => $level,
                    'type' => $type
                ])
            ]);

            if ($autoApprove) {
                $this->payCommissionToWallet($referrer, $commission);
                EmailTemplateService::sendTemplateEmail('referral_commission_earned', $referrer, [
                    'user_name' => e($referrer->name),
                    'commission_amount' => number_format($commissionAmount, 2),
                    'commission_type' => ucfirst(str_replace('_', ' ', $type)),
                    'level' => $level,
                    'referred_user' => e($referred->name),
                    'base_amount' => number_format($baseAmount, 2),
                    'commission_rate' => number_format($commissionRate, 2),
                    'earned_date' => now()->format('M d, Y'),
                    'commission_id' => $commission->id,
                ]);
            }

            UserReferral::where('referrer_id', $referrer->id)
                ->where('referred_id', $referred->id)
                ->update([
                    'total_commission_earned' => DB::raw("total_commission_earned + {$commissionAmount}"),
                ]);

            DB::commit();
            Log::info('Referral commission created', [
                'referrer_id' => $referrer->id,
                'referred_id' => $referred->id,
                'level' => $level,
                'amount' => $commissionAmount,
                'type' => $type,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @param User $referrer
     * @param ReferralCommission $commission
     * @return void
     */
    protected function payCommissionToWallet(User $referrer, ReferralCommission $commission): void
    {
        $wallet = Wallet::where('user_id', $referrer->id)->first();
        if (!$wallet) {
            Log::error('Wallet not found for referrer', ['referrer_id' => $referrer->id]);
            return;
        }

        $previousBalance = (float) $wallet->balance;
        $newBalance = round($previousBalance + $commission->commission_amount, 8);

        $wallet->update([
            'balance' => $newBalance,
            'total_referral_bonus' => DB::raw("total_referral_bonus + {$commission->commission_amount}"),
            'last_activity' => now()
        ]);

        Transaction::create([
            'transaction_id' => 'REF' . strtoupper(Str::random(12)),
            'user_id' => $referrer->id,
            'type' => 'bonus',
            'amount' => $commission->commission_amount,
            'fee' => 0,
            'post_balance' => $newBalance,
            'status' => 'completed',
            'details' => "Level {$commission->level} referral commission from {$commission->referred->name} - " .
                ucfirst($commission->type) . ": $" . number_format($commission->base_amount, 2)
        ]);
    }

    /**
     * @param User $user
     * @param float $amount
     * @return void
     */
    protected function updateReferralInvestmentStats(User $user, float $amount): void
    {
        UserReferral::where('referred_id', $user->id)
            ->update([
                'total_referral_investment' => DB::raw("total_referral_investment + {$amount}"),
            ]);
    }

    /**
     * @param int $userId
     * @return void
     */
    protected function checkAndAwardBonuses(int $userId): void
    {
        $user = User::find($userId);

        if (!$user) {
            return;
        }

        $bonuses = ReferralBonus::where('is_active', true)->get();
        foreach ($bonuses as $bonus) {
            if ($this->isEligibleForBonus($user, $bonus)) {
                $this->awardBonus($user, $bonus);
            }
        }
    }

    /**
     * @param User $user
     * @param ReferralBonus $bonus
     * @return bool
     */
    protected function isEligibleForBonus(User $user, ReferralBonus $bonus): bool
    {
        if (!$bonus->is_recurring) {
            $alreadyClaimed = UserReferralAchievement::where('user_id', $user->id)
                ->where('bonus_id', $bonus->id)
                ->exists();

            if ($alreadyClaimed) {
                return false;
            }
        } else {
            $claimCount = UserReferralAchievement::where('user_id', $user->id)
                ->where('bonus_id', $bonus->id)
                ->count();

            if ($claimCount >= $bonus->max_claims_per_user) {
                return false;
            }
        }

        if ($bonus->min_active_referrals > 0) {
            $activeReferrals = $user->active_referrals ?? 0;

            if ($activeReferrals < $bonus->min_active_referrals) {
                return false;
            }
        }

        if ($bonus->min_team_investment > 0) {
            $teamInvestment = UserReferral::where('referrer_id', $user->id)
                ->sum('total_referral_investment');

            if ($teamInvestment < $bonus->min_team_investment) {
                return false;
            }
        }

        if ($bonus->time_frame_days > 0) {
            $dateThreshold = now()->subDays($bonus->time_frame_days);
            $recentReferrals = UserReferral::where('referrer_id', $user->id)
                ->where('created_at', '>=', $dateThreshold)
                ->where('status', 'active')
                ->count();

            if ($recentReferrals < $bonus->min_active_referrals) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param User $user
     * @param ReferralBonus $bonus
     * @return void
     */
    protected function awardBonus(User $user, ReferralBonus $bonus): void
    {
        DB::beginTransaction();
        try {
            $rewardAmount = 0;
            if ($bonus->reward_type === 'fixed_amount') {
                $rewardAmount = (float) $bonus->reward_amount;
            } elseif ($bonus->reward_type === 'percentage') {
                $teamInvestment = UserReferral::where('referrer_id', $user->id)
                    ->sum('total_referral_investment');
                $rewardAmount = ($teamInvestment * $bonus->reward_amount) / 100;
            }

            UserReferralAchievement::create([
                'user_id' => $user->id,
                'bonus_id' => $bonus->id,
                'status' => 'claimed',
                'reward_amount' => $rewardAmount,
                'earned_at' => now(),
                'claimed_at' => now(),
                'achievement_data' => json_encode([
                    'bonus_name' => $bonus->name,
                    'bonus_type' => $bonus->type,
                    'active_referrals' => $user->active_referrals,
                    'earned_date' => now()->toDateString()
                ])
            ]);

            if ($bonus->reward_type !== 'upgrade' && $rewardAmount > 0) {
                $wallet = Wallet::where('user_id', $user->id)->first();
                if ($wallet) {
                    $previousBalance = (float) $wallet->balance;
                    $newBalance = round($previousBalance + $rewardAmount, 8);

                    $wallet->update([
                        'balance' => $newBalance,
                        'total_referral_bonus' => DB::raw("total_referral_bonus + {$rewardAmount}"),
                        'last_activity' => now()
                    ]);

                    Transaction::create([
                        'transaction_id' => 'BONUS' . strtoupper(Str::random(10)),
                        'user_id' => $user->id,
                        'type' => 'bonus',
                        'amount' => $rewardAmount,
                        'fee' => 0,
                        'post_balance' => $newBalance,
                        'status' => 'completed',
                        'details' => "Referral bonus: {$bonus->name} - {$bonus->description}"
                    ]);
                }
            }

            DB::commit();

            Log::info('Referral bonus awarded', [
                'user_id' => $user->id,
                'bonus_id' => $bonus->id,
                'bonus_name' => $bonus->name,
                'reward_amount' => $rewardAmount
            ]);

            try {
                EmailTemplateService::sendTemplateEmail('referral_bonus_earned', $user, [
                    'user_name' => e($user->name),
                    'bonus_name' => e($bonus->name),
                    'bonus_description' => e($bonus->description),
                    'reward_amount' => round($rewardAmount, 2),
                    'bonus_type' => ucfirst(str_replace('_', ' ', $bonus->type)),
                    'active_referrals' => $user->active_referrals ?? 0,
                    'earned_date' => now()->format('M d, Y'),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send referral bonus email', [
                    'user_id' => $user->id,
                    'bonus_id' => $bonus->id,
                    'error' => $e->getMessage()
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to award referral bonus', [
                'user_id' => $user->id,
                'bonus_id' => $bonus->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

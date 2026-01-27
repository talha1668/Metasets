<?php

namespace App\Models;

use App\Enums\Wallet\Type;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'uid',
        'name',
        'email',
        'email_verified_at',
        'password',
        'gender',
        'role',
        'status',
        'kyc_status',
        'avatar',
        'last_login_at',
        'is_admin',
        'referral_code',
        'referred_by',
        'total_referrals',
        'active_referrals',
        'ai_risk_profile',
        'risk_tolerance',
        'ai_score',
        'wallet_address',
        'wallet_type',
        'nonce',
        'nonce_expires_at',
    ];


    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($user) {
            $user->uid = mt_rand(100000, 999999);
            $user->referral_code = Str::random(10);
        });
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_recovery_codes' => 'json',
        'last_login_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'nonce_expires_at' => 'datetime',
    ];

    protected $appends = ['avatar_url'];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('assets/files/'.$this->avatar) : null;
    }


    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class, 'email', 'email');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referredBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function kycVerification(): HasOne
    {
        return $this->hasOne(KycVerification::class);
    }

    public function hasKycVerification(): bool
    {
        return $this->kycVerification !== null;
    }

    public function isKycApproved(): bool
    {
        return $this->kycVerification && $this->kycVerification->isApproved();
    }

    public function kycStatus(): string
    {
        return $this->kycVerification ? $this->kycVerification->status : 'not_submitted';
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get user's investments
     */
    public function investments(): HasMany
    {
        return $this->hasMany(UserInvestment::class);
    }

    /**
     * Get user's active investments
     */
    public function activeInvestments(): HasMany
    {
        return $this->investments()->where('status', 'active');
    }

    /**
     * Get user's stakes
     */
    public function stakes(): HasMany
    {
        return $this->hasMany(UserStake::class);
    }

    /**
     * Get user's active stakes
     */
    public function activeStakes(): HasMany
    {
        return $this->stakes()->where('status', 'active');
    }

    /**
     * Get user's deposits
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    /**
     * Get user's approved deposits
     */
    public function approvedDeposits(): HasMany
    {
        return $this->deposits()->where('status', 'approved');
    }

    /**
     * Get user's withdrawals
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Get user's approved withdrawals
     */
    public function approvedWithdrawals(): HasMany
    {
        return $this->withdrawals()->where('status', 'approved');
    }

    /**
     * Get user's referral commissions earned (as referrer)
     */
    public function referralCommissionsEarned(): HasMany
    {
        return $this->hasMany(ReferralCommission::class, 'referrer_id');
    }

    /**
     * Get user's referral commissions given (as referred user)
     */
    public function referralCommissionsGiven(): HasMany
    {
        return $this->hasMany(ReferralCommission::class, 'referred_id');
    }

    /**
     * Get user's AI recommendations
     */
    public function aiRecommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class);
    }

    /**
     * Get active AI recommendations
     */
    public function activeRecommendations(): HasMany
    {
        return $this->aiRecommendations()->where('status', 'active');
    }

    /**
     * Get unread AI recommendations count
     */
    public function getUnreadRecommendationsCountAttribute(): int
    {
        return $this->aiRecommendations()->where('is_read', false)->count();
    }

    /**
     * Get user's total investment amount
     */
    public function getTotalInvestedAttribute(): float
    {
        return $this->investments()->whereIn('status', ['active', 'completed'])->sum('amount');
    }

    /**
     * Get user's total earned amount
     */
    public function getTotalEarnedAttribute(): float
    {
        return $this->investments()->sum('earned_amount');
    }

    /**
     * Get user's total staked amount
     */
    public function getTotalStakedAttribute(): float
    {
        return $this->stakes()->where('status', 'active')->sum('stake_amount');
    }

    /**
     * Get user's total referral commission
     */
    public function getTotalReferralCommissionAttribute(): float
    {
        return $this->referralCommissionsEarned()->where('status', 'paid')->sum('commission_amount');
    }

    /**
     * Check if user has any investments
     */
    public function hasInvestments(): bool
    {
        return $this->investments()->exists();
    }

    /**
     * Check if user has any stakes
     */
    public function hasStakes(): bool
    {
        return $this->stakes()->exists();
    }

    /**
     * Check if user has wallet address
     */
    public function hasWalletAddress(): bool
    {
        return !is_null($this->wallet_address);
    }

    /**
     * Check if user has referrals
     */
    public function hasReferrals(): bool
    {
        return $this->total_referrals > 0;
    }
}

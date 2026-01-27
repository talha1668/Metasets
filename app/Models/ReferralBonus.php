<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralBonus extends Model
{
    use HasFactory;

    protected $table = 'referral_bonus';

    protected $fillable = [
        'name',
        'description',
        'type',
        'min_active_referrals',
        'min_team_investment',
        'time_frame_days',
        'reward_type',
        'reward_amount',
        'reward_details',
        'is_active',
        'is_recurring',
        'max_claims_per_user',
    ];

    protected $casts = [
        'min_team_investment' => 'decimal:8',
        'reward_amount' => 'decimal:8',
        'is_active' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get all user achievements for this bonus
     */
    public function achievements()
    {
        return $this->hasMany(UserReferralAchievement::class, 'bonus_id');
    }

    /**
     * Get users who have earned this bonus
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_referral_achievements', 'bonus_id', 'user_id')
            ->withPivot(['status', 'reward_amount', 'earned_at', 'claimed_at', 'achievement_data'])
            ->withTimestamps();
    }

    /**
     * Scope for active bonuses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific bonus type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}

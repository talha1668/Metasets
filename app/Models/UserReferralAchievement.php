<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReferralAchievement extends Model
{
    use HasFactory;

    protected $table = 'user_referral_achievements';

    protected $fillable = [
        'user_id',
        'bonus_id',
        'status',
        'reward_amount',
        'earned_at',
        'claimed_at',
        'achievement_data',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:8',
        'earned_at' => 'datetime',
        'claimed_at' => 'datetime',
        'achievement_data' => 'array',
    ];

    /**
     * Get the user that owns the achievement
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bonus that this achievement belongs to
     */
    public function bonus()
    {
        return $this->belongsTo(ReferralBonus::class, 'bonus_id');
    }

    /**
     * Scope for earned achievements
     */
    public function scopeEarned($query)
    {
        return $query->where('status', 'earned');
    }

    /**
     * Scope for claimed achievements
     */
    public function scopeClaimed($query)
    {
        return $query->where('status', 'claimed');
    }

    /**
     * Mark achievement as claimed
     */
    public function markAsClaimed()
    {
        $this->update([
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);
    }
}

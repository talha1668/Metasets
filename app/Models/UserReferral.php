<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReferral extends Model
{
    protected $table = 'user_referrals';

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'level',
        'status',
        'total_commission_earned',
        'total_referral_investment',
        'generation_count'
    ];

    protected $casts = [
        'total_commission_earned' => 'decimal:8',
        'total_referral_investment' => 'decimal:8',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }
}

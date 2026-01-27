<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCommission extends Model
{
    protected $table = 'referral_commissions';

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'investment_id',
        'level',
        'base_amount',
        'commission_rate',
        'commission_amount',
        'currency',
        'type',
        'status',
        'approved_at',
        'paid_at',
        'description',
        'calculation_details'
    ];

    protected $casts = [
        'base_amount' => 'decimal:8',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:8',
        'calculation_details' => 'array',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(UserInvestment::class, 'investment_id');
    }
}

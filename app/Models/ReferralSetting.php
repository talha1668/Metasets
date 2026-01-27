<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    protected $table = 'referral_settings';

    protected $fillable = [
        'level',
        'commission_rate',
        'commission_type',
        'fixed_amount',
        'applies_to',
        'min_referrals_required',
        'min_investment_required',
        'is_active'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'min_investment_required' => 'decimal:8',
        'is_active' => 'boolean',
    ];
}

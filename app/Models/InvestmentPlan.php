<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'features',
        'min_amount',
        'max_amount',
        'interest_rate',
        'interest_type',
        'return_type',
        'duration_days',
        'principal_return',
        'auto_reinvest_available',
        'auto_reinvest_rate',
        'partial_withdrawal_allowed',
        'early_withdrawal_fee',
        'max_investors',
        'current_investors',
        'total_cap',
        'current_invested',
        'risk_level',
        'ai_recommendation_score',
        'success_rate',
        'color_scheme',
        'badges',
        'sort_order',
        'featured',
        'status',
        'starts_at',
        'ends_at',
        'total_profit_paid',
        'total_completed_investments',
    ];

    protected $casts = [
        'features' => 'array',
        'min_amount' => 'decimal:8',
        'max_amount' => 'decimal:8',
        'interest_rate' => 'decimal:4',
        'auto_reinvest_rate' => 'decimal:2',
        'early_withdrawal_fee' => 'decimal:2',
        'total_cap' => 'decimal:8',
        'current_invested' => 'decimal:8',
        'ai_recommendation_score' => 'array',
        'success_rate' => 'decimal:2',
        'badges' => 'array',
        'auto_reinvest_available' => 'boolean',
        'partial_withdrawal_allowed' => 'boolean',
        'featured' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'total_profit_paid' => 'decimal:8',
    ];
}

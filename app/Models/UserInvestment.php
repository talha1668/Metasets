<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserInvestment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'investment_id',
        'amount',
        'expected_return',
        'earned_amount',
        'withdrawn_amount',
        'remaining_principal',
        'status',
        'approved_at',
        'started_at',
        'next_return_at',
        'maturity_date',
        'completed_at',
        'total_returns_expected',
        'returns_paid',
        'return_amount',
        'return_schedule',
        'auto_reinvest',
        'reinvest_percentage',
        'reinvested_amount',
        'ai_prediction_data',
        'ai_confidence_score',
        'notes',
        'source',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'expected_return' => 'decimal:8',
        'earned_amount' => 'decimal:8',
        'withdrawn_amount' => 'decimal:8',
        'remaining_principal' => 'decimal:8',
        'return_amount' => 'decimal:8',
        'reinvest_percentage' => 'decimal:2',
        'reinvested_amount' => 'decimal:8',
        'ai_confidence_score' => 'decimal:2',
        'return_schedule' => 'array',
        'ai_prediction_data' => 'array',
        'auto_reinvest' => 'boolean',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'next_return_at' => 'datetime',
        'maturity_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the investment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the investment plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class, 'plan_id');
    }

    /**
     * Get all transactions for this investment
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'investment_id', 'investment_id');
    }

    /**
     * Get investment returns/payouts
     */
    public function returns(): HasMany
    {
        return $this->hasMany(InvestmentReturn::class, 'investment_id');
    }

    /**
     * Scope a query to only include active investments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include completed investments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending investments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if investment is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if investment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if investment has matured
     */
    public function hasMatured(): bool
    {
        return $this->maturity_date && now()->gte($this->maturity_date);
    }

    /**
     * Get remaining days until maturity
     */
    public function remainingDays(): int
    {
        if (!$this->maturity_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->maturity_date, false));
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_returns_expected <= 0) {
            return 0;
        }

        return round(($this->returns_paid / $this->total_returns_expected) * 100, 2);
    }

    /**
     * Get remaining returns
     */
    public function getRemainingReturns(): int
    {
        return max(0, $this->total_returns_expected - $this->returns_paid);
    }

    /**
     * Get total profit
     */
    public function getTotalProfit(): float
    {
        return $this->earned_amount;
    }

    /**
     * Get ROI percentage
     */
    public function getRoiPercentage(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }

        return round(($this->earned_amount / $this->amount) * 100, 2);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'recommendation_data',
        'confidence_score',
        'priority',
        'status',
        'is_read',
    ];


    /**
     * @var string[]
     */
    protected $casts = [
        'recommendation_data' => 'array',
        'confidence_score' => 'decimal:2',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @var array
     */
    protected $hidden = [];


    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }


    /**
     * @param $query
     * @return mixed
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }


    /**
     * @param $query
     * @return mixed
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }


    /**
     * @param $query
     * @param string $type
     * @return mixed
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }


    /**
     * @return bool
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }


    /**
     * @return bool
     */
    public function markAsApplied(): bool
    {
        return $this->update(['status' => 'applied']);
    }


    /**
     * @return bool
     */
    public function dismiss(): bool
    {
        return $this->update(['status' => 'dismissed']);
    }


    /**
     * @return null
     */
    public function getInvestmentPlanAttribute()
    {
        if ($this->type === 'investment_plan' && isset($this->recommendation_data['plan_id'])) {
            return InvestmentPlan::find($this->recommendation_data['plan_id']);
        }
        return null;
    }

    /**
     * @return string
     */
    public function getConfidenceLevelAttribute(): string
    {
        $score = $this->confidence_score;

        if ($score >= 85) return 'Very High';
        if ($score >= 70) return 'High';
        if ($score >= 55) return 'Moderate';
        return 'Low';
    }


    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->status === 'active'
            && $this->created_at->diffInDays(now()) > 30;
    }


    /**
     * @return string
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'gray',
            default => 'gray',
        };
    }


    /**
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'applied' => 'blue',
            'dismissed' => 'gray',
            default => 'gray',
        };
    }


    /**
     * @return string
     */
    public function getFormattedConfidenceAttribute(): string
    {
        return number_format($this->confidence_score, 1) . '%';
    }

    /**
     * @return mixed|null
     */
    public function getSuggestedAmountAttribute()
    {
        return $this->recommendation_data['suggested_amount'] ?? null;
    }


    /**
     * @return mixed|null
     */
    public function getExpectedRoiAttribute()
    {
        return $this->recommendation_data['expected_roi'] ?? null;
    }

    // Add to App\Models\AiRecommendation.php

    /**
     * Check if this is an investment plan recommendation
     */
    public function isInvestmentPlan(): bool
    {
        return $this->type === 'investment_plan';
    }

    /**
     * Apply the recommendation
     */
    public function apply(): bool
    {
        return $this->update(['status' => 'applied']);
    }
}

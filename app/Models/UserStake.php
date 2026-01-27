<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserStake extends Model
{
    use HasFactory;

    protected $fillable = [
        'stake_id',
        'user_id',
        'pool_id',
        'stake_amount',
        'current_balance',
        'total_rewards',
        'apy_rate',
        'staked_at',
        'unlock_at',
        'status',
        'auto_compound',
    ];


    protected $casts = [
        'stake_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'total_rewards' => 'decimal:2',
        'apy_rate' => 'decimal:2',
        'staked_at' => 'datetime',
        'unlock_at' => 'datetime',
        'auto_compound' => 'boolean',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function pool(): BelongsTo
    {
        return $this->belongsTo(StakingPool::class, 'pool_id');
    }

    /**
     * @return HasMany
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(StakingReward::class, 'stake_id');
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
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }


    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        if (!$this->unlock_at) {
            return false;
        }

        return now()->lt($this->unlock_at);
    }


    /**
     * @return int
     */
    public function daysUntilUnlock(): int
    {
        if (!$this->unlock_at || !$this->isLocked()) {
            return 0;
        }

        return now()->diffInDays($this->unlock_at);
    }
}

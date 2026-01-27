<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StakingPool extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token_symbol',
        'description',
        'min_stake_amount',
        'max_stake_amount',
        'apy_rate',
        'lock_days',
        'total_pool_size',
        'current_staked',
        'status',
        'auto_compound',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_stake_amount' => 'decimal:8',
        'max_stake_amount' => 'decimal:8',
        'apy_rate' => 'decimal:4',
        'lock_days' => 'integer',
        'total_pool_size' => 'decimal:8',
        'current_staked' => 'decimal:8',
        'auto_compound' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the utilization percentage of the pool.
     *
     * @return float
     */
    public function getUtilizationAttribute(): float
    {
        if (!$this->total_pool_size || $this->total_pool_size == 0) {
            return 0;
        }

        return ($this->current_staked / $this->total_pool_size) * 100;
    }

    /**
     * Check if the pool is full.
     *
     * @return bool
     */
    public function isFull(): bool
    {
        if (!$this->total_pool_size || $this->total_pool_size == 0) {
            return false;
        }

        return $this->current_staked >= $this->total_pool_size;
    }

    /**
     * Check if the pool is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get remaining pool capacity.
     *
     * @return float
     */
    public function getRemainingCapacityAttribute(): float
    {
        if (!$this->total_pool_size || $this->total_pool_size == 0) {
            return PHP_FLOAT_MAX;
        }

        return max(0, $this->total_pool_size - $this->current_staked);
    }
}

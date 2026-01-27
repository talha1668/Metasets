<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StakingReward extends Model
{
    protected $fillable = [
        'user_id',
        'stake_id',
        'reward_amount',
        'reward_date',
        'status',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:8',
        'reward_date' => 'date',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stake(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UserStake::class, 'stake_id');
    }
}

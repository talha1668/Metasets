<?php

namespace App\Models;

use App\Enums\Wallet\Status;
use App\Enums\Wallet\Type;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'balance',
        'bonus_balance',
        'total_invested',
        'total_withdrawn',
        'total_earnings',
        'total_referral_bonus',
        'status',
        'last_activity'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function isActive(): bool
    {
        return $this->status == 'active';
    }
}

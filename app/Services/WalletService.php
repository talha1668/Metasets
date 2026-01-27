<?php

namespace App\Services;

use App\Enums\Wallet\Status;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function createDefaultWallets(User $user): void
    {
        $currency = Setting::get('default_currency', 'USDT');
        DB::transaction(function () use ($user, &$currency) {
            try {
                return Wallet::create([
                    'user_id' => $user->id,
                    'name' => $this->generateWalletName($currency),
                    'currency' => Setting::get('default_currency', 'USD'),
                    'address' => $this->generateWalletAddress($currency),
                    'balance' => 0.00000000,
                    'status' => 'active',
                    'last_activity' => now(),
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to create {$currency} wallets for user {$user->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    private function generateWalletName(string $currency): string
    {
        return "{$currency} Wallet";
    }

    private function generateWalletAddress(string $currency): string
    {
        return strtolower($currency) . '_' . uniqid();
    }
}

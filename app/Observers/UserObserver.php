<?php

namespace App\Observers;

use App\Enums\User\RoleStatus;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Facades\Log;

readonly class UserObserver
{
    public function __construct(protected WalletService $walletService)
    {
    }


    /**
     * @param User $user
     * @return void
     */
    public function creating(User $user): void
    {
        Log::info("User creating event triggered", ['email' => $user->email]);
    }


    /**
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        try {
            if($user->role == RoleStatus::ADMIN->value) {
                Log::info("Skipping wallet creation for admin user", ['user_id' => $user->id]);
                return;
            }

            $this->walletService->createDefaultWallets($user);
        } catch (\Exception $e) {
            Log::error("Failed to create default wallets for user", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {

    }


    /**
     * @param User $user
     * @return void
     */
    public function deleted(User $user): void
    {

    }


    /**
     * @param User $user
     * @return void
     */
    public function restored(User $user): void
    {

    }


    /**
     * @param User $user
     * @return void
     */
    public function forceDeleted(User $user): void
    {

    }
}

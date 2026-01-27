<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AiRecommendationSeeder::class,
            SettingsSeeder::class,
            InvestmentPlanSeeder::class,
            LanguageSeeder::class,
            PaymentGatewaySeeder::class,
            WithdrawalGatewaySeeder::class,
            StakingPoolSeeder::class,
            ReferralSettingsSeeder::class,
            ReferralBonusSeeder::class,
        ]);
    }
}

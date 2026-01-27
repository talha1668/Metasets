<?php

namespace Database\Seeders;

use App\Models\ReferralSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('referral_settings')->truncate();
        $settings = [
            [
                'level' => 1,
                'commission_rate' => 5.00,
                'commission_type' => 'percentage',
                'fixed_amount' => 0,
                'applies_to' => 'both',
                'min_referrals_required' => 0,
                'min_investment_required' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 2,
                'commission_rate' => 3.00,
                'commission_type' => 'percentage',
                'fixed_amount' => 0,
                'applies_to' => 'both',
                'min_referrals_required' => 5,
                'min_investment_required' => 100,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 3,
                'commission_rate' => 2.00,
                'commission_type' => 'percentage',
                'fixed_amount' => 0,
                'applies_to' => 'both',
                'min_referrals_required' => 10,
                'min_investment_required' => 500,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 4,
                'commission_rate' => 1.50,
                'commission_type' => 'percentage',
                'fixed_amount' => 0,
                'applies_to' => 'profit',
                'min_referrals_required' => 15,
                'min_investment_required' => 1000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 5,
                'commission_rate' => 1.00,
                'commission_type' => 'percentage',
                'fixed_amount' => 0,
                'applies_to' => 'profit',
                'min_referrals_required' => 20,
                'min_investment_required' => 2000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 6,
                'commission_rate' => 0,
                'commission_type' => 'fixed',
                'fixed_amount' => 10.00,
                'applies_to' => 'deposit',
                'min_referrals_required' => 25,
                'min_investment_required' => 5000,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            ReferralSetting::create($setting);
        }

        $this->command->info('Referral settings seeded successfully!');
    }
}

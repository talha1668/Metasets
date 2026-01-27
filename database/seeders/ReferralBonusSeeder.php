<?php

namespace Database\Seeders;

use App\Models\ReferralBonus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferralBonusSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        DB::table('user_referral_achievements')->delete();
        DB::table('referral_bonus')->truncate();

        $bonuses = [
            [
                'name' => 'First 5 Referrals Bonus',
                'description' => 'Earn a bonus when you successfully refer your first 5 active members',
                'type' => 'milestone',
                'min_active_referrals' => 5,
                'min_team_investment' => 0,
                'time_frame_days' => 0,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 50.00,
                'reward_details' => '$50 bonus credited to your account',
                'is_active' => true,
                'is_recurring' => false,
                'max_claims_per_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silver Recruiter - 10 Referrals',
                'description' => 'Achieve Silver status by referring 10 active members',
                'type' => 'milestone',
                'min_active_referrals' => 10,
                'min_team_investment' => 1000,
                'time_frame_days' => 0,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 150.00,
                'reward_details' => '$150 bonus + Silver badge',
                'is_active' => true,
                'is_recurring' => false,
                'max_claims_per_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold Recruiter - 25 Referrals',
                'description' => 'Achieve Gold status by referring 25 active members with minimum team investment',
                'type' => 'milestone',
                'min_active_referrals' => 25,
                'min_team_investment' => 5000,
                'time_frame_days' => 0,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 500.00,
                'reward_details' => '$500 bonus + Gold badge + 2% commission boost',
                'is_active' => true,
                'is_recurring' => false,
                'max_claims_per_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Platinum Recruiter - 50 Referrals',
                'description' => 'Achieve Platinum status by referring 50 active members with substantial team investment',
                'type' => 'milestone',
                'min_active_referrals' => 50,
                'min_team_investment' => 15000,
                'time_frame_days' => 0,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 1500.00,
                'reward_details' => '$1,500 bonus + Platinum badge + 5% commission boost',
                'is_active' => true,
                'is_recurring' => false,
                'max_claims_per_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Diamond Elite - 100 Referrals',
                'description' => 'Join the Diamond Elite by referring 100 active members',
                'type' => 'milestone',
                'min_active_referrals' => 100,
                'min_team_investment' => 50000,
                'time_frame_days' => 0,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 5000.00,
                'reward_details' => '$5,000 bonus + Diamond badge + 10% commission boost + VIP support',
                'is_active' => true,
                'is_recurring' => false,
                'max_claims_per_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Super Team Builder',
                'description' => 'Build a team with total investments exceeding $50,000',
                'type' => 'achievement',
                'min_active_referrals' => 0,
                'min_team_investment' => 50000,
                'time_frame_days' => 0,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 1000.00,
                'reward_details' => '$1,000 super team builder bonus',
                'is_active' => true,
                'is_recurring' => false,
                'max_claims_per_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Active Network Leader',
                'description' => 'Maintain 20+ active referrals for 90 consecutive days',
                'type' => 'achievement',
                'min_active_referrals' => 20,
                'min_team_investment' => 0,
                'time_frame_days' => 90,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 300.00,
                'reward_details' => '$300 leadership bonus',
                'is_active' => true,
                'is_recurring' => false,
                'max_claims_per_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Monthly Loyalty Bonus',
                'description' => 'Monthly bonus for maintaining at least 10 active referrals',
                'type' => 'loyalty',
                'min_active_referrals' => 10,
                'min_team_investment' => 2000,
                'time_frame_days' => 30,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 50.00,
                'reward_details' => '$50 monthly loyalty bonus',
                'is_active' => true,
                'is_recurring' => true,
                'max_claims_per_user' => 999,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Quarterly Elite Bonus',
                'description' => 'Quarterly bonus for top performers with 25+ active referrals',
                'type' => 'loyalty',
                'min_active_referrals' => 25,
                'min_team_investment' => 10000,
                'time_frame_days' => 90,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 250.00,
                'reward_details' => '$250 quarterly elite bonus',
                'is_active' => true,
                'is_recurring' => true,
                'max_claims_per_user' => 999,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Annual Top Performer',
                'description' => 'Annual reward for maintaining 50+ active referrals throughout the year',
                'type' => 'loyalty',
                'min_active_referrals' => 50,
                'min_team_investment' => 50000,
                'time_frame_days' => 365,
                'reward_type' => 'fixed_amount',
                'reward_amount' => 2000.00,
                'reward_details' => '$2,000 annual top performer bonus + exclusive perks',
                'is_active' => true,
                'is_recurring' => true,
                'max_claims_per_user' => 999,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($bonuses as $bonus) {
            ReferralBonus::create($bonus);
        }

        $this->command->info('Referral bonuses seeded successfully!');
    }
}

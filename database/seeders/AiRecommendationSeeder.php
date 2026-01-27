<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\InvestmentPlan;
use App\Models\AiRecommendation;
use App\Models\Transaction;
use Carbon\Carbon;

class AiRecommendationSeeder extends Seeder
{

    /**
     * @return void
     */
    public function run(): void
    {
        $users = User::where('role', 'user')
            ->where('status', 'active')
            ->limit(20)
            ->get();

        if ($users->isEmpty()) {
            $this->command->warn('No active users found. Please run UsersSeeder first.');
            return;
        }

        $plans = InvestmentPlan::where('status', 'active')->get();
        if ($plans->isEmpty()) {
            $this->command->warn('No active investment plans found. Please run InvestmentPlansSeeder first.');
            return;
        }

        $recommendationsCreated = 0;

        foreach ($users as $user) {
            $recommendationCount = rand(0, 3);
            if ($recommendationCount === 0) continue;
            if ($recommendationCount >= 1) {
                $plan = $plans->random();
                $confidenceScore = rand(60, 95);

                AiRecommendation::create([
                    'user_id' => $user->id,
                    'type' => 'investment_plan',
                    'title' => "Recommended: {$plan->name}",
                    'description' => "Based on your profile and activity, this {$plan->risk_level}-risk plan offers {$plan->interest_rate}% returns over {$plan->duration_days} days. This matches your investment goals and risk tolerance.",
                    'recommendation_data' => [
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'suggested_amount' => $this->calculateSuggestedAmount($user, $plan),
                        'expected_roi' => $plan->interest_rate,
                        'duration_days' => $plan->duration_days,
                        'risk_level' => $plan->risk_level,
                        'min_investment' => $plan->min_amount,
                        'max_investment' => $plan->max_amount,
                        'reason' => $this->generateRecommendationReason($user, $plan),
                    ],
                    'confidence_score' => $confidenceScore,
                    'priority' => $this->determinePriority($confidenceScore),
                    'status' => $this->getRandomStatus(),
                    'is_read' => rand(0, 1) === 1,
                    'created_at' => Carbon::now()->subDays(rand(0, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 15)),
                ]);

                $recommendationsCreated++;
            }

            if ($recommendationCount >= 2) {
                $plan = $plans->random();
                $confidenceScore = rand(55, 85);

                AiRecommendation::create([
                    'user_id' => $user->id,
                    'type' => 'investment_plan',
                    'title' => "Alternative Option: {$plan->name}",
                    'description' => "Consider diversifying with this {$plan->risk_level}-risk plan. It provides {$plan->interest_rate}% returns and complements your current portfolio strategy.",
                    'recommendation_data' => [
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'suggested_amount' => $this->calculateSuggestedAmount($user, $plan),
                        'expected_roi' => $plan->interest_rate,
                        'duration_days' => $plan->duration_days,
                        'risk_level' => $plan->risk_level,
                        'min_investment' => $plan->min_amount,
                        'max_investment' => $plan->max_amount,
                        'reason' => 'Portfolio diversification opportunity',
                    ],
                    'confidence_score' => $confidenceScore,
                    'priority' => $this->determinePriority($confidenceScore),
                    'status' => $this->getRandomStatus(),
                    'is_read' => rand(0, 1) === 1,
                    'created_at' => Carbon::now()->subDays(rand(0, 25)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 12)),
                ]);

                $recommendationsCreated++;
            }

            if ($recommendationCount >= 3 && $user->balance > 100) {
                $recentEarnings = rand(50, 500);
                $recommendedAmount = min(
                    $user->balance * 0.40,
                    $recentEarnings * 2
                );
                $confidenceScore = rand(65, 90);

                AiRecommendation::create([
                    'user_id' => $user->id,
                    'type' => 'reinvestment',
                    'title' => 'Smart Reinvestment Opportunity',
                    'description' => "Your recent earnings show strong portfolio growth. Reinvesting $" . number_format($recommendedAmount, 2) . " could accelerate your returns and compound your profits faster.",
                    'recommendation_data' => [
                        'available_amount' => $recommendedAmount,
                        'recent_earnings' => $recentEarnings,
                        'current_balance' => $user->balance,
                        'suggested_percentage' => 40,
                        'projected_monthly_return' => $recommendedAmount * 0.15,
                        'compounding_benefit' => 'High',
                        'reason' => 'Strong recent earnings performance',
                    ],
                    'confidence_score' => $confidenceScore,
                    'priority' => $this->determinePriority($confidenceScore),
                    'status' => $this->getRandomStatus(),
                    'is_read' => rand(0, 1) === 1,
                    'created_at' => Carbon::now()->subDays(rand(0, 20)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 10)),
                ]);

                $recommendationsCreated++;
            }
        }

        $this->createHighPriorityRecommendations($users, $plans);

        $this->command->info("AI Recommendations seeder completed successfully!");
        $this->command->info("Created {$recommendationsCreated} AI recommendations");
    }

    /**
     * @param User $user
     * @param InvestmentPlan $plan
     * @return float
     */
    private function calculateSuggestedAmount(User $user, InvestmentPlan $plan): float
    {
        $riskPercentages = [
            'conservative' => 0.15,
            'moderate' => 0.25,
            'aggressive' => 0.40,
        ];

        $riskTolerance = $user->risk_tolerance ?? 'moderate';
        $percentage = $riskPercentages[$riskTolerance] ?? 0.25;

        $suggested = $user->balance * $percentage;

        return max($plan->min_amount, min($plan->max_amount, $suggested));
    }

    /**
     * @param User $user
     * @param InvestmentPlan $plan
     * @return string
     */
    private function generateRecommendationReason(User $user, InvestmentPlan $plan): string
    {
        $reasons = [
            "Matches your {$user->risk_tolerance} risk profile",
            "Strong historical performance with {$plan->success_rate}% success rate",
            "Optimal duration for your investment timeline",
            "Recommended by our AI based on similar user success",
            "Featured plan with proven track record",
            "Best fit for your current portfolio balance",
        ];

        return $reasons[array_rand($reasons)];
    }

    /**
     * @param float $confidenceScore
     * @return string
     */
    private function determinePriority(float $confidenceScore): string
    {
        if ($confidenceScore >= 80) return 'high';
        if ($confidenceScore >= 65) return 'medium';
        return 'low';
    }

    /**
     * @return string
     */
    private function getRandomStatus(): string
    {
        $statuses = [
            'active' => 60,
            'dismissed' => 25,
            'applied' => 15,
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($random <= $cumulative) {
                return $status;
            }
        }

        return 'active';
    }


    /**
     * @param $users
     * @param $plans
     * @return void
     */
    private function createHighPriorityRecommendations($users, $plans): void
    {
        $highPriorityCount = min(5, $users->count());

        for ($i = 0; $i < $highPriorityCount; $i++) {
            $user = $users->random();
            $plan = $plans->random();

            AiRecommendation::create([
                'user_id' => $user->id,
                'type' => 'investment_plan',
                'title' => "🔥 Limited Time: {$plan->name}",
                'description' => "URGENT: This high-performing plan matches your profile perfectly. With {$plan->interest_rate}% returns and limited slots available, act fast to secure your spot!",
                'recommendation_data' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'suggested_amount' => $this->calculateSuggestedAmount($user, $plan),
                    'expected_roi' => $plan->interest_rate,
                    'duration_days' => $plan->duration_days,
                    'risk_level' => $plan->risk_level,
                    'urgency' => 'high',
                    'slots_remaining' => rand(5, 20),
                    'reason' => 'Premium recommendation with limited availability',
                ],
                'confidence_score' => rand(85, 95),
                'priority' => 'high',
                'status' => 'active',
                'is_read' => false,
                'created_at' => Carbon::now()->subHours(rand(1, 48)),
                'updated_at' => Carbon::now()->subHours(rand(1, 24)),
            ]);
        }
    }
}

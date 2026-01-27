<?php

namespace App\Services;

use App\Models\User;
use App\Models\InvestmentPlan;
use App\Models\AiRecommendation;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AiRecommendationService
{

    /**
     * @param User $user
     * @param string $type
     * @return int
     */
    public function generateRecommendationsForUser(User $user, string $type = 'all'): int
    {
        try {
            $generatedCount = 0;
            $maxRecommendations = Setting::get('ai_max_recommendations_per_user', 3);
            $cooldownHours = Setting::get('ai_recommendation_cooldown_hours', 24);

            $recentRecommendation = AiRecommendation::where('user_id', $user->id)
                ->where('created_at', '>=', Carbon::now()->subHours($cooldownHours))
                ->exists();

            if ($recentRecommendation) {
                return 0;
            }

            $activeCount = AiRecommendation::where('user_id', $user->id)
                ->where('status', 'active')
                ->count();

            if ($activeCount >= $maxRecommendations) {
                return 0;
            }

            if ($type === 'all' || $type === 'investment_plan') {
                $generatedCount += $this->generateInvestmentPlanRecommendations($user);
            }

            if ($type === 'all' || $type === 'reinvestment') {
                $generatedCount += $this->generateReinvestmentRecommendations($user);
            }

            return $generatedCount;

        } catch (\Exception $e) {
            Log::error('AI Recommendation Generation Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }


    /**
     * @param string $type
     * @return int
     */
    public function generateRecommendationsForAllUsers(string $type = 'all'): int
    {
        $totalGenerated = 0;

        User::where('status', 'active')
            ->chunk(50, function ($users) use ($type, &$totalGenerated) {
                foreach ($users as $user) {
                    try {
                        $totalGenerated += $this->generateRecommendationsForUser($user, $type);
                    } catch (\Exception $e) {
                        Log::warning('Failed to generate recommendations', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

        return $totalGenerated;
    }


    /**
     * @param User $user
     * @return int
     */
    protected function generateInvestmentPlanRecommendations(User $user): int
    {
        $confidenceThreshold = Setting::get('ai_confidence_threshold');
        $suitablePlans = $this->findSuitableInvestmentPlans($user);
        $generatedCount = 0;

        foreach ($suitablePlans as $planData) {
            $plan = $planData['plan'];
            $confidence = $planData['confidence'];

            if ($confidence >= $confidenceThreshold) {
                $recommendation = AiRecommendation::create([
                    'user_id' => $user->id,
                    'type' => 'investment_plan',
                    'title' => "Recommended: {$plan->name}",
                    'description' => "Based on your profile, this {$plan->risk_level}-risk plan offers {$plan->interest_rate}% returns over {$plan->duration_days} days.",
                    'recommendation_data' => [
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'suggested_amount' => $this->calculateSuggestedAmount($user, $plan),
                        'expected_roi' => $plan->interest_rate,
                    ],
                    'confidence_score' => $confidence,
                    'priority' => $confidence >= Setting::get('ai_high_priority_threshold') ? 'high' : 'medium',
                    'status' => 'active'
                ]);

                if ($recommendation) {
                    $generatedCount++;
                }
            }

            if ($generatedCount >= Setting::get('ai_max_plans_per_recommendation')) break;
        }

        return $generatedCount;
    }


    /**
     * @param User $user
     * @return int
     */
    protected function generateReinvestmentRecommendations(User $user): int
    {
        $minBalance = Setting::get('ai_min_reinvestment_balance');
        $minEarnings = Setting::get('ai_min_recent_earnings');
        $confidenceThreshold = Setting::get('ai_confidence_threshold');

        $recentEarnings = Transaction::where('user_id', $user->id)
            ->where('type', 'interest')
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subDays(Setting::get('ai_data_lookback_days')))
            ->sum('amount');

        if ($user->wallet->balance < $minBalance && $recentEarnings < $minEarnings) {
            return 0;
        }

        $confidence = $this->calculateReinvestmentConfidence($user, $recentEarnings);

        if ($confidence >= $confidenceThreshold) {
            $recommendedAmount = min(
                $user->wallet->balance * Setting::get('ai_max_balance_percentage'),
                $recentEarnings * Setting::get('ai_earnings_multiplier')
            );

            $recommendation = AiRecommendation::create([
                'user_id' => $user->id,
                'type' => 'reinvestment',
                'title' => 'Smart Reinvestment Opportunity',
                'description' => "Reinvesting " . Setting::get('currency_symbol') . number_format($recommendedAmount, 2) . " could accelerate your portfolio growth.",
                'recommendation_data' => [
                    'available_amount' => $recommendedAmount,
                    'recent_earnings' => $recentEarnings,
                ],
                'confidence_score' => $confidence,
                'priority' => $confidence >= Setting::get('ai_high_priority_threshold') ? 'high' : 'medium',
                'status' => 'active'
            ]);

            return $recommendation ? 1 : 0;
        }

        return 0;
    }


    /**
     * @param User $user
     * @return array
     */
    protected function findSuitableInvestmentPlans(User $user): array
    {

        $plans = InvestmentPlan::where('status', 'active')
            ->where('min_amount', '<=', $user->wallet->balance)
            ->get();



        $suitablePlans = [];

        foreach ($plans as $plan) {
            $confidence = $this->calculatePlanConfidence($user, $plan);

            if ($confidence > Setting::get('ai_min_plan_confidence')) {
                $suitablePlans[] = [
                    'plan' => $plan,
                    'confidence' => $confidence
                ];
            }
        }

        usort($suitablePlans, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_slice($suitablePlans, 0, Setting::get('ai_max_plans_per_recommendation'));
    }


    /**
     * @param User $user
     * @param InvestmentPlan $plan
     * @return float
     */
    protected function calculatePlanConfidence(User $user, InvestmentPlan $plan): float
    {
        $confidence = Setting::get('ai_base_confidence');

        // Risk match bonus
        $riskMatch = $this->calculateRiskMatch($plan->risk_level, $user->risk_tolerance ?? 'moderate');
        $confidence += ($riskMatch / 100) * Setting::get('ai_risk_match_weight');

        // Affordability bonus
        if ($user->wallet->balance >= $plan->min_amount * Setting::get('ai_affordability_multiplier')) {
            $confidence += Setting::get('ai_affordability_bonus');
        }

        // Plan performance bonus
        if (($plan->success_rate ?? 0) > Setting::get('ai_success_rate_threshold')) {
            $confidence += Setting::get('ai_performance_bonus');
        }

        // Featured plan bonus
        if ($plan->featured) {
            $confidence += Setting::get('ai_featured_bonus');
        }

        return round(min(95, max(10, $confidence)), 1);
    }


    /**
     * @param string $planRisk
     * @param string $userTolerance
     * @return float
     */
    protected function calculateRiskMatch(string $planRisk, string $userTolerance): float
    {
        $matches = [
            'conservative' => [
                'low' => Setting::get('ai_conservative_low_match'),
                'medium' => Setting::get('ai_conservative_medium_match'),
                'high' => Setting::get('ai_conservative_high_match')
            ],
            'moderate' => [
                'low' => Setting::get('ai_moderate_low_match'),
                'medium' => Setting::get('ai_moderate_medium_match'),
                'high' => Setting::get('ai_moderate_high_match')
            ],
            'aggressive' => [
                'low' => Setting::get('ai_aggressive_low_match'),
                'medium' => Setting::get('ai_aggressive_medium_match'),
                'high' => Setting::get('ai_aggressive_high_match')
            ],
        ];

        return $matches[$userTolerance][$planRisk] ?? Setting::get('ai_default_risk_match');
    }


    /**
     * @param User $user
     * @param InvestmentPlan $plan
     * @return float
     */
    protected function calculateSuggestedAmount(User $user, InvestmentPlan $plan): float
    {
        $percentages = [
            'conservative' => Setting::get('ai_conservative_percentage'),
            'moderate' => Setting::get('ai_moderate_percentage'),
            'aggressive' => Setting::get('ai_aggressive_percentage'),
        ];

        $percentage = $percentages[$user->risk_tolerance ?? 'moderate'];
        $suggested = $user->wallet->balance * $percentage;

        return max($plan->min_amount, min($plan->max_amount, $suggested));
    }


    /**
     * @param User $user
     * @param float $recentEarnings
     * @return float
     */
    protected function calculateReinvestmentConfidence(User $user, float $recentEarnings): float
    {
        $confidence = Setting::get('ai_reinvestment_base_confidence');

        if ($recentEarnings > Setting::get('ai_earnings_threshold')) {
            $confidence += Setting::get('ai_earnings_bonus');
        }

        if ($user->wallet->balance > Setting::get('ai_balance_threshold')) {
            $confidence += Setting::get('ai_balance_bonus');
        }

        return min(95, $confidence);
    }
}

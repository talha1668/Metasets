<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AiRecommendation;
use App\Services\AiRecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AiRecommendationController extends Controller
{
    public function __construct(
        protected readonly AiRecommendationService $aiService
    ) {}

    /**
     * @return Response
     */
    public function index(): Response
    {
        try {
            $user = Auth::user();

            $recommendations = AiRecommendation::where('user_id', $user->id)
                ->with(['user:id,name,email'])
                ->orderBy('priority', 'desc')
                ->orderBy('confidence_score', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $stats = [
                'total' => AiRecommendation::where('user_id', $user->id)->count(),
                'active' => AiRecommendation::where('user_id', $user->id)->where('status', 'active')->count(),
                'unread' => AiRecommendation::where('user_id', $user->id)->where('is_read', false)->count(),
                'applied' => AiRecommendation::where('user_id', $user->id)->where('status', 'applied')->count(),
            ];

            return Inertia::render('User/AiRecommendations/Index', [
                'recommendations' => [
                    'data' => $recommendations->items(),
                    'meta' => [
                        'total' => $recommendations->total(),
                        'current_page' => $recommendations->currentPage(),
                        'per_page' => $recommendations->perPage(),
                        'last_page' => $recommendations->lastPage(),
                        'from' => $recommendations->firstItem(),
                        'to' => $recommendations->lastItem(),
                        'links' => $recommendations->linkCollection()->toArray(),
                    ],
                ],
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('AI Recommendations Index Error: ' . $e->getMessage());

            return Inertia::render('User/AiRecommendations/Index', [
                'recommendations' => [
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                        'current_page' => 1,
                        'per_page' => 10,
                        'last_page' => 1,
                        'from' => null,
                        'to' => null,
                        'links' => [],
                    ],
                ],
                'stats' => [
                    'total' => 0,
                    'active' => 0,
                    'unread' => 0,
                    'applied' => 0,
                ],
            ]);
        }
    }

    /**
     * @param AiRecommendation $recommendation
     * @return Response|RedirectResponse
     */
    public function show(AiRecommendation $recommendation): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($recommendation->user_id !== $user->id) {
            return redirect()->route('user.ai-recommendations.index')
                ->with('error', 'Unauthorized access.');
        }

        if (!$recommendation->is_read) {
            $recommendation->markAsRead();
        }

        $investmentPlan = null;
        if ($recommendation->isInvestmentPlan() && isset($recommendation->recommendation_data['plan_id'])) {
            $investmentPlan = \App\Models\InvestmentPlan::find($recommendation->recommendation_data['plan_id']);
        }

        return Inertia::render('User/AiRecommendations/Show', [
            'recommendation' => $recommendation,
            'investmentPlan' => $investmentPlan,
        ]);
    }

    /**
     * @param AiRecommendation $recommendation
     * @return RedirectResponse
     */
    public function markAsRead(AiRecommendation $recommendation): RedirectResponse
    {
        $user = Auth::user();

        if ($recommendation->user_id !== $user->id) {
            return back()->with('error', 'Unauthorized access.');
        }

        $recommendation->markAsRead();

        return back()->with('success', 'Recommendation marked as read.');
    }

    /**
     * @param AiRecommendation $recommendation
     * @return RedirectResponse
     */
    public function dismiss(AiRecommendation $recommendation): RedirectResponse
    {
        $user = Auth::user();

        if ($recommendation->user_id !== $user->id) {
            return back()->with('error', 'Unauthorized access.');
        }

        $recommendation->dismiss();

        return back()->with('success', 'Recommendation dismissed successfully.');
    }

    /**
     * @param AiRecommendation $recommendation
     * @return RedirectResponse
     */
    public function apply(AiRecommendation $recommendation): RedirectResponse
    {
        $user = Auth::user();

        if ($recommendation->user_id !== $user->id) {
            return back()->with('error', 'Unauthorized access.');
        }

        if (!$recommendation->isInvestmentPlan()) {
            return back()->with('error', 'Only investment plan recommendations can be applied.');
        }

        $recommendation->apply();

        return redirect()->route('user.investments.index', [
            'plan_id' => $recommendation->recommendation_data['plan_id'] ?? null,
            'suggested_amount' => $recommendation->recommendation_data['suggested_amount'] ?? null,
        ])->with('success', 'Redirected to investment page. The recommended plan is pre-selected.');
    }

    /**
     * @return RedirectResponse
     */
    public function generate(): RedirectResponse
    {
        $user = Auth::user();

        try {
            $generated = $this->aiService->generateRecommendationsForUser($user, 'all');

            if ($generated > 0) {
                return back()->with('success', "Generated {$generated} new AI recommendation(s) for you.");
            }

            return back()->with('info', 'No new recommendations available at this time. Please check back later.');

        } catch (\Exception $e) {
            Log::error('AI Recommendations Generation Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate recommendations. Please try again later.');
        }
    }
}

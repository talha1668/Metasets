<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRecommendation;
use App\Models\User;
use App\Models\Setting;
use App\Services\AiRecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AiRecommendationController extends Controller
{
    public function __construct(protected readonly AiRecommendationService $aiService){}


    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function index(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:5|max:100',
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|in:investment_plan,reinvestment',
            'priority' => 'nullable|in:low,medium,high',
            'status' => 'nullable|in:active,dismissed,applied',
            'user_id' => 'nullable|integer|exists:users,id',
            'sort_field' => 'nullable|in:title,type,priority,status,confidence_score,created_at',
            'sort_direction' => 'nullable|in:asc,desc',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $type = $request->get('type');
            $priority = $request->get('priority');
            $status = $request->get('status');
            $userId = $request->get('user_id');
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = AiRecommendation::with(['user:id,email,name']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->Where('email', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($type) $query->where('type', $type);
            if ($priority) $query->where('priority', $priority);
            if ($status) $query->where('status', $status);
            if ($userId) $query->where('user_id', $userId);
            if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
            if ($dateTo) $query->whereDate('created_at', '<=', $dateTo);

            $recommendations = $query->orderBy($sortField, $sortDirection)->paginate($perPage);
            $stats = $this->getAiStats($dateFrom, $dateTo);

            return Inertia::render('Admin/AI/Recommendations/Index', [
                'recommendations' => $recommendations->items(),
                'meta' => [
                    'total' => $recommendations->total(),
                    'current_page' => $recommendations->currentPage(),
                    'per_page' => $recommendations->perPage(),
                    'last_page' => $recommendations->lastPage(),
                ],
                'stats' => $stats,
                'filters' => $request->only([
                    'search', 'type', 'priority', 'status', 'user_id',
                    'sort_field', 'sort_direction', 'date_from', 'date_to'
                ]),
                'filter_options' => [
                    'types' => [
                        ['value' => 'investment_plan', 'label' => 'Investment Plan'],
                        ['value' => 'reinvestment', 'label' => 'Reinvestment'],
                    ],
                    'priorities' => [
                        ['value' => 'low', 'label' => 'Low'],
                        ['value' => 'medium', 'label' => 'Medium'],
                        ['value' => 'high', 'label' => 'High'],
                    ],
                    'statuses' => [
                        ['value' => 'active', 'label' => 'Active'],
                        ['value' => 'dismissed', 'label' => 'Dismissed'],
                        ['value' => 'applied', 'label' => 'Applied'],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to load AI recommendations. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function generate(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:users,id',
            'type' => 'required|in:investment_plan,reinvestment,all',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $userId = $request->get('user_id');
            $type = $request->get('type');

            if ($userId) {
                $user = User::findOrFail($userId);
                $generated = $this->aiService->generateRecommendationsForUser($user, $type);
                $message = "Generated {$generated} recommendation(s) for user {$user->email}";
            } else {
                $generated = $this->aiService->generateRecommendationsForAllUsers($type);
                $message = "Generated {$generated} recommendation(s) for all users";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to generate AI recommendations. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'recommendation_ids' => 'required|array|min:1',
            'recommendation_ids.*' => 'integer|exists:ai_recommendations,id',
            'action' => 'required|in:activate,dismiss,mark_applied,delete',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $recommendationIds = $request->get('recommendation_ids');
            $action = $request->get('action');

            DB::beginTransaction();

            switch ($action) {
                case 'activate':
                    AiRecommendation::whereIn('id', $recommendationIds)
                        ->update(['status' => 'active']);
                    $message = 'Recommendations activated successfully';
                    break;

                case 'dismiss':
                    AiRecommendation::whereIn('id', $recommendationIds)
                        ->update(['status' => 'dismissed']);
                    $message = 'Recommendations dismissed successfully';
                    break;

                case 'mark_applied':
                    AiRecommendation::whereIn('id', $recommendationIds)
                        ->update(['status' => 'applied']);
                    $message = 'Recommendations marked as applied successfully';
                    break;

                case 'delete':
                    AiRecommendation::whereIn('id', $recommendationIds)->delete();
                    $message = 'Recommendations deleted successfully';
                    break;
            }

            DB::commit();
            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to update recommendations. Please try again.']);
        }
    }

    /**
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    private function getAiStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = AiRecommendation::query();
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('created_at', '<=', $dateTo);

        $total = $query->count();
        $active = (clone $query)->where('status', 'active')->count();
        $applied = (clone $query)->where('status', 'applied')->count();
        $dismissed = (clone $query)->where('status', 'dismissed')->count();
        $highPriority = (clone $query)->where('priority', 'high')->count();
        $avgConfidence = $query->avg('confidence_score') ?? 0;
        $effectivenessRate = $total > 0 ? ($applied / $total) * 100 : 0;

        return [
            'total_recommendations' => $total,
            'active_recommendations' => $active,
            'applied_recommendations' => $applied,
            'dismissed_recommendations' => $dismissed,
            'high_priority_count' => $highPriority,
            'average_confidence' => round($avgConfidence, 2),
            'effectiveness_rate' => round($effectivenessRate, 2),
            'total_users_with_recommendations' => AiRecommendation::distinct('user_id')->count(),
        ];
    }
}

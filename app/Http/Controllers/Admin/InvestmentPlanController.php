<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentPlanController extends Controller
{
    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function index(Request $request): Response | RedirectResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:5|max:100',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,coming_soon',
            'risk_level' => 'nullable|in:low,medium,high',
            'sort_field' => 'nullable|in:name,min_amount,max_amount,interest_rate,duration_days,status,sort_order,created_at',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $status = $request->get('status');
            $riskLevel = $request->get('risk_level');
            $sortField = $request->get('sort_field', 'sort_order');
            $sortDirection = $request->get('sort_direction', 'asc');

            $query = InvestmentPlan::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($riskLevel) {
                $query->where('risk_level', $riskLevel);
            }

            $plans = $query->orderBy($sortField, $sortDirection)
                ->paginate($perPage);

            $stats = [
                'total_plans' => InvestmentPlan::count(),
                'active_plans' => InvestmentPlan::where('status', 'active')->count(),
                'inactive_plans' => InvestmentPlan::where('status', 'inactive')->count(),
                'coming_soon_plans' => InvestmentPlan::where('status', 'coming_soon')->count(),
            ];

            return Inertia::render('Admin/InvestmentPlans/Index', [
                'plans' => $plans->items(),
                'meta' => [
                    'total' => $plans->total(),
                    'current_page' => $plans->currentPage(),
                    'per_page' => $plans->perPage(),
                    'last_page' => $plans->lastPage(),
                ],
                'stats' => $stats,
                'filters' => $request->only(['search', 'status', 'risk_level', 'sort_field', 'sort_direction']),
                'status_options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                    ['value' => 'coming_soon', 'label' => 'Coming Soon'],
                ],
                'risk_levels' => [
                    ['value' => 'low', 'label' => 'Low'],
                    ['value' => 'medium', 'label' => 'Medium'],
                    ['value' => 'high', 'label' => 'High'],
                ],
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to load investment plans. Please try again.']);
        }
    }

    /**
     * @return Response|RedirectResponse
     */
    public function create(): Response | RedirectResponse
    {
        try {
            return Inertia::render('Admin/InvestmentPlans/Form', [
                'status_options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                    ['value' => 'coming_soon', 'label' => 'Coming Soon'],
                ],
                'interest_types' => [
                    ['value' => 'fixed', 'label' => 'Fixed'],
                    ['value' => 'compound', 'label' => 'Compound'],
                ],
                'return_types' => [
                    ['value' => 'hourly', 'label' => 'Hourly'],
                    ['value' => 'daily', 'label' => 'Daily'],
                    ['value' => 'weekly', 'label' => 'Weekly'],
                    ['value' => 'monthly', 'label' => 'Monthly'],
                ],
                'principal_return_options' => [
                    ['value' => 'yes', 'label' => 'Yes'],
                    ['value' => 'no', 'label' => 'No'],
                    ['value' => 'after_maturity', 'label' => 'After Maturity'],
                ],
                'risk_levels' => [
                    ['value' => 'low', 'label' => 'Low'],
                    ['value' => 'medium', 'label' => 'Medium'],
                    ['value' => 'high', 'label' => 'High'],
                ],
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to load create form. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:investment_plans,name',
            'description' => 'required|string',
            'features' => 'nullable|string',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|gt:min_amount',
            'interest_rate' => 'required|numeric|min:0|max:9999.9999',
            'interest_type' => 'required|in:fixed,compound',
            'return_type' => 'required|in:hourly,daily,weekly,monthly',
            'duration_days' => 'required|integer|min:1',
            'principal_return' => 'required|in:yes,no,after_maturity',
            'auto_reinvest_available' => 'nullable|boolean',
            'auto_reinvest_rate' => 'nullable|numeric|min:0|max:100',
            'partial_withdrawal_allowed' => 'nullable|boolean',
            'early_withdrawal_fee' => 'nullable|numeric|min:0|max:100',
            'max_investors' => 'nullable|integer|min:0',
            'total_cap' => 'nullable|numeric|min:0',
            'risk_level' => 'required|in:low,medium,high',
            'ai_recommendation_score' => 'nullable|string',
            'success_rate' => 'nullable|numeric|min:0|max:100',
            'color_scheme' => 'nullable|string|max:7',
            'badges' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'featured' => 'nullable|boolean',
            'status' => 'required|in:active,inactive,coming_soon',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['slug'] = Str::slug($data['name']);
            $data['auto_reinvest_available'] = $request->boolean('auto_reinvest_available');
            $data['partial_withdrawal_allowed'] = $request->boolean('partial_withdrawal_allowed');
            $data['featured'] = $request->boolean('featured');

            if ($request->features) {
                $features = json_decode($request->features, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withErrors(['features' => 'Invalid JSON format for features'])->withInput();
                }
                $data['features'] = $features;
            }

            if ($request->ai_recommendation_score) {
                $aiScore = json_decode($request->ai_recommendation_score, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withErrors(['ai_recommendation_score' => 'Invalid JSON format for AI recommendation score'])->withInput();
                }
                $data['ai_recommendation_score'] = $aiScore;
            }

            if ($request->badges) {
                $badges = json_decode($request->badges, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withErrors(['badges' => 'Invalid JSON format for badges'])->withInput();
                }
                $data['badges'] = $badges;
            }

            $data['auto_reinvest_rate'] = $data['auto_reinvest_rate'] ?? 0;
            $data['early_withdrawal_fee'] = $data['early_withdrawal_fee'] ?? 0;
            $data['max_investors'] = $data['max_investors'] ?? 0;
            $data['total_cap'] = $data['total_cap'] ?? 0;
            $data['success_rate'] = $data['success_rate'] ?? 0;
            $data['color_scheme'] = $data['color_scheme'] ?? '#3b82f6';
            $data['sort_order'] = $data['sort_order'] ?? 0;

            InvestmentPlan::create($data);
            return redirect()->back()->with('success', 'Investment plan created successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create investment plan. Please try again.']);
        }
    }

    /**
     * @param InvestmentPlan $investmentPlan
     * @return Response|RedirectResponse
     */
    public function edit(InvestmentPlan $investmentPlan): Response | RedirectResponse
    {
        try {
            return Inertia::render('Admin/InvestmentPlans/Form', [
                'plan' => $investmentPlan,
                'status_options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                    ['value' => 'coming_soon', 'label' => 'Coming Soon'],
                ],
                'interest_types' => [
                    ['value' => 'fixed', 'label' => 'Fixed'],
                    ['value' => 'compound', 'label' => 'Compound'],
                ],
                'return_types' => [
                    ['value' => 'hourly', 'label' => 'Hourly'],
                    ['value' => 'daily', 'label' => 'Daily'],
                    ['value' => 'weekly', 'label' => 'Weekly'],
                    ['value' => 'monthly', 'label' => 'Monthly'],
                ],
                'principal_return_options' => [
                    ['value' => 'yes', 'label' => 'Yes'],
                    ['value' => 'no', 'label' => 'No'],
                    ['value' => 'after_maturity', 'label' => 'After Maturity'],
                ],
                'risk_levels' => [
                    ['value' => 'low', 'label' => 'Low'],
                    ['value' => 'medium', 'label' => 'Medium'],
                    ['value' => 'high', 'label' => 'High'],
                ],
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to load edit form. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @param InvestmentPlan $investmentPlan
     * @return RedirectResponse
     */
    public function update(Request $request, InvestmentPlan $investmentPlan): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:investment_plans,name,' . $investmentPlan->id,
            'description' => 'required|string',
            'features' => 'nullable|string',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|gt:min_amount',
            'interest_rate' => 'required|numeric|min:0|max:9999.9999',
            'interest_type' => 'required|in:fixed,compound',
            'return_type' => 'required|in:hourly,daily,weekly,monthly',
            'duration_days' => 'required|integer|min:1',
            'principal_return' => 'required|in:yes,no,after_maturity',
            'auto_reinvest_available' => 'nullable|boolean',
            'auto_reinvest_rate' => 'nullable|numeric|min:0|max:100',
            'partial_withdrawal_allowed' => 'nullable|boolean',
            'early_withdrawal_fee' => 'nullable|numeric|min:0|max:100',
            'max_investors' => 'nullable|integer|min:0',
            'total_cap' => 'nullable|numeric|min:0',
            'risk_level' => 'required|in:low,medium,high',
            'ai_recommendation_score' => 'nullable|string',
            'success_rate' => 'nullable|numeric|min:0|max:100',
            'color_scheme' => 'nullable|string|max:7',
            'badges' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'featured' => 'nullable|boolean',
            'status' => 'required|in:active,inactive,coming_soon',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['slug'] = Str::slug($data['name']);
            $data['auto_reinvest_available'] = $request->boolean('auto_reinvest_available');
            $data['partial_withdrawal_allowed'] = $request->boolean('partial_withdrawal_allowed');
            $data['featured'] = $request->boolean('featured');

            if ($request->features) {
                $features = json_decode($request->features, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withErrors(['features' => 'Invalid JSON format for features'])->withInput();
                }
                $data['features'] = $features;
            } else {
                $data['features'] = null;
            }

            if ($request->ai_recommendation_score) {
                $aiScore = json_decode($request->ai_recommendation_score, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withErrors(['ai_recommendation_score' => 'Invalid JSON format for AI recommendation score'])->withInput();
                }
                $data['ai_recommendation_score'] = $aiScore;
            } else {
                $data['ai_recommendation_score'] = null;
            }

            if ($request->badges) {
                $badges = json_decode($request->badges, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withErrors(['badges' => 'Invalid JSON format for badges'])->withInput();
                }
                $data['badges'] = $badges;
            } else {
                $data['badges'] = null;
            }

            $data['auto_reinvest_rate'] = $data['auto_reinvest_rate'] ?? 0;
            $data['early_withdrawal_fee'] = $data['early_withdrawal_fee'] ?? 0;
            $data['max_investors'] = $data['max_investors'] ?? 0;
            $data['total_cap'] = $data['total_cap'] ?? 0;
            $data['success_rate'] = $data['success_rate'] ?? 0;
            $data['color_scheme'] = $data['color_scheme'] ?? '#3b82f6';
            $data['sort_order'] = $data['sort_order'] ?? 0;

            $investmentPlan->update($data);
            return redirect('/admin/investment-plans')->with('success', 'Investment plan updated successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update investment plan. Please try again.']);
        }
    }
}

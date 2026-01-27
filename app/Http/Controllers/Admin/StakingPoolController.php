<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StakingPool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StakingPoolController extends Controller
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
            'status' => 'nullable|in:active,inactive',
            'sort_field' => 'nullable|in:name,token_symbol,min_stake_amount,max_stake_amount,apy_rate,lock_days,status,created_at',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $status = $request->get('status');
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query = StakingPool::query();
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('token_symbol', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            $pools = $query->orderBy($sortField, $sortDirection)->paginate($perPage);
            $stats = [
                'total_pools' => StakingPool::count(),
                'active_pools' => StakingPool::where('status', 'active')->count(),
                'inactive_pools' => StakingPool::where('status', 'inactive')->count(),
                'total_staked' => StakingPool::sum('current_staked'),
            ];

            return Inertia::render('Admin/StakingPools/Index', [
                'pools' => $pools->items(),
                'meta' => [
                    'total' => $pools->total(),
                    'current_page' => $pools->currentPage(),
                    'per_page' => $pools->perPage(),
                    'last_page' => $pools->lastPage(),
                ],
                'stats' => $stats,
                'filters' => $request->only(['search', 'status', 'sort_field', 'sort_direction']),
                'status_options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to load staking pools. Please try again.']);
        }
    }

    /**
     * @return Response|RedirectResponse
     */
    public function create(): Response | RedirectResponse
    {
        try {
            return Inertia::render('Admin/StakingPools/Form', [
                'status_options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
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
            'name' => 'required|string|max:255|unique:staking_pools,name',
            'token_symbol' => 'required|string|max:10',
            'description' => 'required|string',
            'min_stake_amount' => 'required|numeric|min:0',
            'max_stake_amount' => 'nullable|numeric|gt:min_stake_amount',
            'apy_rate' => 'required|numeric|min:0|max:9999.9999',
            'lock_days' => 'required|integer|min:1',
            'total_pool_size' => 'nullable|numeric|min:0',
            'auto_compound' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['auto_compound'] = $request->boolean('auto_compound');
            $data['total_pool_size'] = $data['total_pool_size'] ?? 0;
            $data['current_staked'] = 0;
            StakingPool::create($data);
            return redirect()->back()->with('success', 'Staking pool created successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create staking pool. Please try again.']);
        }
    }

    /**
     * @param StakingPool $stakingPool
     * @return Response|RedirectResponse
     */
    public function edit(StakingPool $stakingPool): Response | RedirectResponse
    {
        try {
            return Inertia::render('Admin/StakingPools/Form', [
                'pool' => $stakingPool,
                'status_options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to load edit form. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @param StakingPool $stakingPool
     * @return RedirectResponse
     */
    public function update(Request $request, StakingPool $stakingPool): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:staking_pools,name,' . $stakingPool->id,
            'token_symbol' => 'required|string|max:10',
            'description' => 'required|string',
            'min_stake_amount' => 'required|numeric|min:0',
            'max_stake_amount' => 'nullable|numeric|gt:min_stake_amount',
            'apy_rate' => 'required|numeric|min:0|max:9999.9999',
            'lock_days' => 'required|integer|min:1',
            'total_pool_size' => 'nullable|numeric|min:0',
            'auto_compound' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['auto_compound'] = $request->boolean('auto_compound');
            $data['total_pool_size'] = $data['total_pool_size'] ?? 0;
            $stakingPool->update($data);
            return redirect('/admin/staking-pools')->with('success', 'Staking pool updated successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update staking pool. Please try again.']);
        }
    }

    /**
     * @param StakingPool $stakingPool
     * @return RedirectResponse
     */
    public function destroy(StakingPool $stakingPool): RedirectResponse
    {
        try {
            $stakingPool->delete();
            return back()->with('success', 'Staking pool deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete staking pool. Please try again.']);
        }
    }
}

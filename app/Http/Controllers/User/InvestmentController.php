<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestmentRequest;
use App\Http\Requests\StakingRequest;
use App\Models\AiRecommendation;
use App\Services\InvestmentService;
use App\Services\StakingService;
use App\Models\InvestmentPlan;
use App\Models\StakingPool;
use App\Models\UserInvestment;
use App\Models\UserStake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentController extends Controller
{
    /**
     * @param InvestmentService $investmentService
     * @param StakingService $stakingService
     */
    public function __construct(protected InvestmentService $investmentService, protected StakingService $stakingService) {}

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        try {
            $user = Auth::user();
            $preSelectedPlanId = $request->query('plan_id');
            $suggestedAmount = $request->query('suggested_amount');
            $investmentPlans = $this->investmentService->getActivePlans();

            $stakingPools = $this->stakingService->getActivePools();

            $investmentLogs = UserInvestment::with('plan')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10)
                ->through(function ($investment) {
                    return [
                        'id' => $investment->id,
                        'investment_id' => $investment->investment_id,
                        'plan_name' => $investment->plan->name,
                        'amount' => (float) $investment->amount,
                        'expected_return' => (float) $investment->expected_return,
                        'earned_amount' => (float) $investment->earned_amount,
                        'status' => $investment->status,
                        'started_at' => $investment->started_at?->toISOString(),
                        'next_return_at' => $investment->next_return_at?->toISOString(),
                        'maturity_date' => $investment->maturity_date?->toISOString(),
                        'returns_paid' => $investment->returns_paid,
                        'total_returns_expected' => $investment->total_returns_expected,
                        'progress_percentage' => $this->investmentService->calculateProgressPercentage($investment),
                    ];
                });

            $stakingLogs = UserStake::with('pool')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10)
                ->through(function ($stake) {
                    return [
                        'id' => $stake->id,
                        'stake_id' => $stake->stake_id,
                        'pool_name' => $stake->pool->name,
                        'stake_amount' => (float) $stake->stake_amount,
                        'current_balance' => (float) $stake->current_balance,
                        'total_rewards' => (float) $stake->total_rewards,
                        'status' => $stake->status,
                        'apy_rate' => $stake->apy_rate,
                        'staked_at' => $stake->staked_at?->toISOString(),
                        'unlock_date' => $stake->unlock_date?->toISOString(),
                        'auto_compound' => (bool) $stake->auto_compound,
                    ];
                });


            $aiRecommendations = AiRecommendation::where('user_id', $user->id)
                ->where('status', 'active')
                ->orderBy('priority', 'desc')
                ->orderBy('confidence_score', 'desc')
                ->limit(3)
                ->get();

            $headerStats = $this->investmentService->getHeaderStatistics($user);
            $preSelectedPlan = null;
            if ($preSelectedPlanId) {
                $preSelectedPlan = $investmentPlans->firstWhere('id', $preSelectedPlanId);
            }

            return Inertia::render('User/Investment/Index', [
                'investmentPlans' => $investmentPlans,
                'stakingPools' => $stakingPools,
                'investmentLogs' => $investmentLogs,
                'stakingLogs' => $stakingLogs,
                'headerStats' => $headerStats,
                'aiRecommendations' => $aiRecommendations,
                'preSelectedPlan' => $preSelectedPlan,
                'suggestedAmount' => $suggestedAmount,
            ]);

        } catch (\Exception $e) {
            Log::error('Investment index page error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('User/Investment/Index', [
                'error' => __('Unable to load investment data. Please try again.')
            ]);
        }
    }

    /**
     * @param InvestmentRequest $request
     * @return RedirectResponse
     */
    public function invest(InvestmentRequest $request): RedirectResponse
    {
        try {
            $user = Auth::user();
            $plan = InvestmentPlan::findOrFail($request->plan_id);

            if ($plan->status !== 'active') {
                return back()->withErrors(['error' => __('This investment plan is not currently available.')]);
            }

            if ($plan->starts_at && now()->lt($plan->starts_at)) {
                return back()->withErrors(['error' => __('This plan is not yet available. Starts at: :date', [
                    'date' => $plan->starts_at->format('Y-m-d H:i')
                ])]);
            }

            if ($plan->ends_at && now()->gt($plan->ends_at)) {
                return back()->withErrors(['error' => __('This plan has ended.')]);
            }

            $investment = $this->investmentService->createInvestment(
                $user,
                $plan,
                $request->amount
            );

            return back()->with('success', __('Investment successful! Your investment ID: :id', [
                'id' => $investment->investment_id
            ]));

        } catch (\App\Exceptions\InsufficientBalanceException $e) {
            Log::warning('Insufficient balance for investment', [
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'balance' => Auth::user()->wallet->balance ?? 0
            ]);
            return back()->withErrors(['error' => __('Insufficient wallet balance. Please deposit funds first.')]);

        } catch (\App\Exceptions\PlanCapacityException $e) {
            Log::info('Plan capacity reached', [
                'plan_id' => $request->plan_id,
                'user_id' => Auth::id()
            ]);
            return back()->withErrors(['error' => __('This investment plan has reached maximum capacity. Please try another plan.')]);

        } catch (\Exception $e) {
            Log::error('Investment creation failed', [
                'user_id' => Auth::id(),
                'plan_id' => $request->plan_id,
                'amount' => $request->amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => __('Investment failed: :message', [
                'message' => config('app.debug') ? $e->getMessage() : __('Please try again or contact support.')
            ])]);
        }
    }

    /**
     * @param StakingRequest $request
     * @return RedirectResponse
     */
    public function stake(StakingRequest $request): RedirectResponse
    {

        try {
            $user = Auth::user();
            $pool = StakingPool::findOrFail($request->pool_id);

            if ($pool->status !== 'active') {
                return back()->with('error', __('This staking pool is not currently available.'));
            }

            $stake = $this->stakingService->createStake(
                $user,
                $pool,
                $request->amount,
                $request->boolean('auto_compound', false)
            );

            return back()->with('success', __('Staking successful! Your stake ID: :id', [
                'id' => $stake->stake_id
            ]));

        } catch (\App\Exceptions\InsufficientBalanceException $e) {
            Log::warning('Insufficient balance for staking', [
                'user_id' => Auth::id(),
                'amount' => $request->amount
            ]);
            return back()->with('error', __('Insufficient wallet balance. Please deposit funds first.'));

        } catch (\App\Exceptions\PoolCapacityException $e) {
            Log::info('Pool capacity reached', [
                'pool_id' => $request->pool_id,
                'user_id' => Auth::id()
            ]);
            return back()->with('error', __('This staking pool has reached maximum capacity.'));

        } catch (\Exception $e) {
            Log::error('Staking creation failed', [
                'user_id' => Auth::id(),
                'pool_id' => $request->pool_id,
                'amount' => $request->amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', __('Staking failed: :message', [
                'message' => config('app.debug') ? $e->getMessage() : __('Please try again or contact support.')
            ]));
        }
    }

    /**
     * @param int $stakeId
     * @return RedirectResponse
     */
    public function unstake(int $stakeId): RedirectResponse
    {
        try {
            $user = Auth::user();
            $stake = UserStake::where('user_id', $user->id)
                ->where('id', $stakeId)
                ->where('status', 'active')
                ->firstOrFail();

            $this->stakingService->unstake($stake);

            return back()->with('success', __('Unstaking successful! :amount has been returned to your wallet.', [
                'amount' => config('app.currency_symbol', '$') . number_format($stake->current_balance, 2)
            ]));

        } catch (\App\Exceptions\StakeStillLockedException $e) {
            return back()->with('error', __('Stake is still locked. Unlock date: :date', [
                'date' => $e->getUnlockDate()->format('Y-m-d H:i')
            ]));

        } catch (\Exception $e) {
            Log::error('Unstaking failed', [
                'user_id' => Auth::id(),
                'stake_id' => $stakeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', __('Unstaking failed. Please try again or contact support.'));
        }
    }

    /**
     * @param int $stakeId
     * @return RedirectResponse
     */
    public function claimRewards(int $stakeId): RedirectResponse
    {
        try {
            $user = Auth::user();

            $stake = UserStake::where('user_id', $user->id)
                ->where('id', $stakeId)
                ->where('status', 'active')
                ->firstOrFail();

            if ($stake->total_rewards <= 0) {
                return back()->with('warning', __('No rewards available to claim at this time.'));
            }

            $rewardAmount = $this->stakingService->claimRewards($stake);

            return back()->with('success', __('Rewards claimed successfully! :amount has been added to your wallet.', [
                'amount' => config('app.currency_symbol', '$') . number_format($rewardAmount, 2)
            ]));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Stake not found for reward claim', [
                'user_id' => Auth::id(),
                'stake_id' => $stakeId
            ]);
            return back()->with('error', __('Stake not found.'));

        } catch (\Exception $e) {
            Log::error('Reward claim failed', [
                'user_id' => Auth::id(),
                'stake_id' => $stakeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', __('Failed to claim rewards. Please try again or contact support.'));
        }
    }

    /**
     * @param string $investmentId
     * @return Response|RedirectResponse
     */
    public function show(string $investmentId): Response | RedirectResponse
    {
        try {
            $user = Auth::user();

            $investment = UserInvestment::with(['plan', 'transactions'])
                ->where('user_id', $user->id)
                ->where('investment_id', $investmentId)
                ->firstOrFail();

            return Inertia::render('User/Investment/Show', [
                'investment' => $investment,
                'returnSchedule' => $this->investmentService->getReturnSchedule($investment),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('user.investments.index')
                ->with('error', __('Investment not found.'));
        }
    }

    /**
     * @param string $stakeId
     * @return Response|RedirectResponse
     */
    public function showStake(string $stakeId): Response | RedirectResponse
    {
        try {
            $user = Auth::user();
            $stake = UserStake::with(['pool', 'rewards'])
                ->where('user_id', $user->id)
                ->where('stake_id', $stakeId)
                ->firstOrFail();

            return Inertia::render('User/Investment/ShowStake', [
                'stake' => $stake,
                'rewardHistory' => $this->stakingService->getRewardHistory($stake),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('user.investments.index')
                ->with('error', __('Stake not found.'));
        }
    }
}

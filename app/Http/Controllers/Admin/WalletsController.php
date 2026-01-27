<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Wallet\Status;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WalletsController extends Controller
{

    public function __construct(protected readonly WalletService $walletService){

    }

    /**
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function index(Request $request): Response | RedirectResponse
    {

        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:inactive,active,locked',
            'sort_field' => 'nullable|in:name,balance,status,created_at',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|in:10,25,50,100'
        ]);

        try {
            $search = $request->get('search');
            $status = $request->get('status');
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $walletsQuery = Wallet::with(['user'])
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($userQuery) use ($search) {
                                $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($status, function ($query, $status) {
                    $query->where('status', $status);
                });

            $allowedSortFields = ['name', 'balance', 'status', 'created_at'];
            if (in_array($sortField, $allowedSortFields)) {
                $walletsQuery->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
            } else {
                $walletsQuery->orderBy('created_at', 'desc');
            }

            $perPage = (int) $request->get('per_page', 20);
            $wallets = $walletsQuery->paginate($perPage)->appends($request->all());
            $walletsData = $wallets->through(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'address' => $wallet->address,
                    'balance' => (float) $wallet->balance,
                    'bonus_balance' => (float) $wallet->bonus_balance,
                    'total_invested' => (float) $wallet->total_invested,
                    'total_withdrawn' => (float) $wallet->total_withdrawn,
                    'total_earnings' => (float) $wallet->total_earnings,
                    'total_referral_bonus' => (float) $wallet->total_referral_bonus,
                    'status' => $wallet->status,
                    'last_activity' => $wallet->last_activity,
                    'created_at' => $wallet->created_at,
                    'user' => $wallet->user ? [
                        'id' => $wallet->user->id,
                        'name' => $wallet->user->name,
                        'email' => $wallet->user->email,
                    ] : null,
                ];
            });

            $stats = [
                'total_wallets' => Wallet::count(),
                'active_wallets' => Wallet::where('status', 'active')->count(),
                'locked_wallets' => Wallet::where('status', 'locked')->count(),
                'inactive_wallets' => Wallet::where('status', 'inactive')->count(),
            ];

            return Inertia::render('Admin/Wallets/Index', [
                'wallets' => $walletsData,
                'meta' => [
                    'total' => $walletsData->total(),
                    'current_page' => $walletsData->currentPage(),
                    'per_page' => $walletsData->perPage(),
                    'last_page' => $walletsData->lastPage(),
                ],
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'sort_field' => $sortField,
                    'sort_direction' => $sortDirection,
                ],
                'stats' => $stats,
                'currentUser' => auth()->user() ? [
                    'id' => auth()->user()->id,
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                    'role' => auth()->user()->role ?? 'user',
                ] : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading wallets', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' =>  'Unable to load wallets. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @param Wallet $wallet
     * @return RedirectResponse
     */
    public function updateStatus(Request $request, Wallet $wallet): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['inactive', 'active', 'locked'])]
        ]);

        try {
            $wallet->update(['status' => $validated['status']]);
            return redirect()->back()->with('success', 'Wallet status updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating wallet status', [
                'wallet_id' => $wallet->id,
                'status' => $validated['status'],
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to update wallet status. Please try again.']);
        }
    }

    /**
     * @param Request $request
     * @param Wallet $wallet
     * @return RedirectResponse
     */
    public function adjustBalance(Request $request, Wallet $wallet): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in(['add', 'subtract'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'reason' => ['nullable', 'string', 'max:255']
        ]);

        try {
            DB::transaction(function () use ($wallet, $validated) {
                $currentBalance = $wallet->balance;
                $amount = $validated['amount'];
                $action = $validated['action'];

                if ($action === 'add') {
                    $newBalance = $currentBalance + $amount;
                } else {
                    $newBalance = $currentBalance - $amount;
                    if ($newBalance < 0) {
                        throw new \Exception('Insufficient balance for this operation!');
                    }
                }

                $wallet->update([
                    'balance' => $newBalance,
                    'last_activity' => now()
                ]);

                Transaction::create([
                    'transaction_id' => Str::random(),
                    'user_id' => $wallet->user_id,
                    'type' => $action === 'add' ? 'deposit' : 'withdrawal',
                    'amount' => $amount,
                    'post_balance' => $newBalance,
                    'status' => 'completed',
                    'details' => $validated['reason'] ?? "Admin {$action} balance adjustment"
                ]);
            });

            return redirect()->back()->with('success', ucfirst($validated['action']) . ' balance operation completed successfully!');

        } catch (\Exception $e) {
            Log::error('Error adjusting wallet balance', [
                'wallet_id' => $wallet->id,
                'action' => $validated['action'],
                'amount' => $validated['amount'],
                'error' => $e->getMessage()
            ]);

            if ($e->getMessage() === 'Insufficient balance for this operation!') {
                return redirect()->back()->withErrors(['error' =>  'Insufficient balance for this operation!']);
            }

            return redirect()->back()->withErrors(['error' => 'Failed to adjust wallet balance. Please try again.']);
        }
    }
}

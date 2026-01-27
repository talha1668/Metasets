<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\GlobalMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $role = $request->get('role');
        $status = $request->get('status');
        $kycStatus = $request->get('kyc_status');
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $query = User::where('role', '!=', 'admin');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('uid', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($kycStatus) {
            $query->where('kyc_status', $kycStatus);
        }

        $allowedSortFields = ['name', 'email', 'role', 'status', 'kyc_status', 'created_at', 'last_login_at'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        $perPage = (int) $request->get('per_page', 20);
        $users = $query->paginate($perPage)->appends($request->all());
        $transformedUsers = $users->through(function ($user) use ($request) {
            return (new UserResource($user))->toArray($request);
        });

        $statsQuery = User::where('role', '!=', 'admin');
        if ($search) {
            $statsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('uid', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }
        if ($role) {
            $statsQuery->where('role', $role);
        }
        if ($status) {
            $statsQuery->where('status', $status);
        }
        if ($kycStatus) {
            $statsQuery->where('kyc_status', $kycStatus);
        }

        $stats = [
            'totalUsers' => $statsQuery->count(),
            'activeUsers' => (clone $statsQuery)->where('status', 'active')->count(),
            'emailVerifiedUsers' => (clone $statsQuery)->whereNotNull('email_verified_at')->count(),
            'pendingUsers' => (clone $statsQuery)->where('status', 'pending')->count(),
            'suspendedUsers' => (clone $statsQuery)->where('status', 'suspended')->count(),
            'bannedUsers' => (clone $statsQuery)->where('status', 'banned')->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $transformedUsers->items(),
            'meta' => [
                'total' => $transformedUsers->total(),
                'current_page' => $transformedUsers->currentPage(),
                'per_page' => $transformedUsers->perPage(),
                'last_page' => $transformedUsers->lastPage(),
            ],
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'status' => $status,
                'kyc_status' => $kycStatus,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
            'currentUser' => auth()->user() ? [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'role' => auth()->user()->role ?? 'user',
            ] : null,
        ]);
    }


    /**
     * Update user status
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'status' => [
                    'required',
                    'string',
                    Rule::in(['active', 'suspended', 'pending', 'banned'])
                ]
            ]);

            $newStatus = $validated['status'];
            $oldStatus = $user->status;

            if ($oldStatus === 'pending' && $newStatus === 'active' && is_null($user->email_verified_at)) {
                $user->update([
                    'email_verified_at' => now(),
                    'status' => $newStatus
                ]);
            } else {
                $user->update(['status' => $newStatus]);
            }

            Log::info('User status updated', [
                'user_id' => $user->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', 'User status updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update user status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    /**
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function updateKycStatus(Request $request, User $user): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'kyc_status' => [
                    'required',
                    'string',
                    Rule::in(['pending', 'reviewing', 'approved', 'rejected'])
                ]
            ]);

            $newKycStatus = $validated['kyc_status'];
            $oldKycStatus = $user->kyc_status;

            $user->update(['kyc_status' => $newKycStatus]);

            Log::info('User KYC status updated', [
                'user_id' => $user->id,
                'old_kyc_status' => $oldKycStatus,
                'new_kyc_status' => $newKycStatus,
                'updated_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', 'User KYC status updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update user KYC status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    /**
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function sendMail(Request $request, User $user): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'content' => 'required|string'
            ]);

            if (!$user->email) {
                return redirect()->back()->with('error', 'User does not have an email address.');
            }

            Mail::send(new GlobalMail($user, $validated['subject'], $validated['content']));
            Log::info('Mail sent to user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'subject' => $validated['subject'],
                'sent_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', "Mail sent successfully to {$user->email}!");

        } catch (\Exception $e) {
            Log::error('Failed to send mail to user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to send mail. Please try again.');
        }
    }


    /**
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function loginAs(Request $request, User $user): RedirectResponse
    {
        try {
            if ($user->status === 'suspended' || $user->status === 'banned') {
                return redirect()->back()->with('error', 'Cannot login as suspended or banned user.');
            }

            if ($user->role === 'admin') {
                return redirect()->back()->with('error', 'Cannot login as another admin user.');
            }

            session()->put('impersonate_admin_id', auth()->id());
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            auth()->login($user);

            $user->update(['last_login_at' => now()]);
            return redirect()->route('user.dashboard')->with('success', "Successfully logged in as {$user->name}");

        } catch (\Exception $e) {
            Log::error('Failed to login as user', [
                'admin_id' => auth()->id(),
                'target_user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to login as user. Please try again.');
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'users' => [],
                'message' => 'Query too short'
            ]);
        }

        try {
            $users = User::where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->orWhere('uid', 'LIKE', "%{$query}%");
            })
                ->where('status', 'active')
                ->where('role', 'user')
                ->select('id', 'uid', 'name', 'email')
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'uid' => $user->uid,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                });

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'users' => [],
                'message' => 'Search failed'
            ], 500);
        }
    }
}

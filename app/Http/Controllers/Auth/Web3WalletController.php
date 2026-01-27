<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\EmailTemplateService;
use App\Services\EmailVerificationService;
use App\Services\LoginHistoryService;
use App\Services\SettingsService;
use Elliptic\EC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use kornrunner\Keccak;

class Web3WalletController extends Controller
{
    public function __construct(
        protected readonly LoginHistoryService $loginHistoryService,
    ){

    }
    const SUPPORTED_WALLETS = [
        'metamask' => 'MetaMask',
        'walletconnect' => 'WalletConnect',
    ];

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string|regex:/^0x[a-fA-F0-9]{40}$/',
            'wallet_type' => 'required|string|in:' . implode(',', array_keys(self::SUPPORTED_WALLETS))
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'authenticated' => false
            ]);
        }

        $walletAddress = strtolower($request->wallet_address);
        if (Auth::check()) {
            $user = Auth::user();
            if (strtolower($user->wallet_address) === $walletAddress) {
                return response()->json([
                    'success' => true,
                    'authenticated' => true,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'wallet_address' => $user->wallet_address,
                    ]
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'authenticated' => false
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getNonce(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string|regex:/^0x[a-fA-F0-9]{40}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid wallet address'
            ], 400);
        }

        $walletAddress = strtolower($request->wallet_address);
        $nonce = 'Sign this message to authenticate with your wallet: ' . Str::random(32);
        Cache::put("wallet_nonce:{$walletAddress}", $nonce, now()->addMinutes(5));

        return response()->json([
            'success' => true,
            'nonce' => $nonce,
            'message' => 'Please sign this message with your wallet'
        ]);
    }

    /**
     * @param string $walletAddress
     * @param string $message
     * @param string $signature
     * @return bool
     */
    private function verifySignature(string $walletAddress, string $message, string $signature): bool
    {
        try {
            // Normalize wallet address to lowercase
            $walletAddress = strtolower($walletAddress);

            // Create the message hash with Ethereum Signed Message prefix (EIP-191)
            $messageHash = Keccak::hash(
                sprintf("\x19Ethereum Signed Message:\n%s%s", strlen($message), $message),
                256
            );

            $recoveredAddress = $this->recoverAddress($messageHash, $signature);
            $recoveredAddress = strtolower($recoveredAddress);

            $isValid = $recoveredAddress === $walletAddress;

            if (!$isValid) {
                Log::warning('Signature verification failed - address mismatch', [
                    'expected' => $walletAddress,
                    'recovered' => $recoveredAddress,
                    'message_length' => strlen($message),
                    'signature_length' => strlen($signature)
                ]);
            }

            return $isValid;

        } catch (\Exception $e) {
            Log::error('Signature verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'wallet' => $walletAddress,
                'message_length' => strlen($message ?? ''),
                'signature_length' => strlen($signature ?? '')
            ]);
            return false;
        }
    }

    /**
     * @param string $hash
     * @param string $signature
     * @return string
     * @throws \Exception
     */
    private function recoverAddress(string $hash, string $signature): string
    {
        $signature = str_starts_with($signature, '0x') ? substr($signature, 2) : $signature;
        if (strlen($signature) !== 130) {
            throw new \Exception('Invalid signature length: ' . strlen($signature) . ' (expected 130)');
        }

        $r = substr($signature, 0, 64);
        $s = substr($signature, 64, 64);
        $v = hexdec(substr($signature, 128, 2));

        // Normalize v value (should be 27 or 28 for personal_sign)
        if ($v < 27) {
            $v += 27;
        }

        $ec = new EC('secp256k1');

        // Calculate recovery ID from v value
        $recoveryId = $v - 27;

        // Try the calculated recovery ID first, then try the other (0 or 1)
        $recoveryIds = [$recoveryId];
        if ($recoveryId === 0) {
            $recoveryIds[] = 1;
        } elseif ($recoveryId === 1) {
            $recoveryIds[] = 0;
        }

        foreach ($recoveryIds as $rid) {
            try {
        $publicKey = $ec->recoverPubKey($hash, [
            'r' => $r,
            's' => $s
                ], $rid);

        $publicKeyString = $publicKey->encode('hex');
                // Remove '04' prefix if present (uncompressed public key indicator)
                if (str_starts_with($publicKeyString, '04')) {
        $publicKeyString = substr($publicKeyString, 2);
                }

        $addressHash = Keccak::hash(hex2bin($publicKeyString), 256);
                $recoveredAddress = '0x' . substr($addressHash, -40);

                return $recoveredAddress;
            } catch (\Exception $e) {
                // Try next recovery ID
                if ($rid === end($recoveryIds)) {
                    Log::error('Failed to recover address with all recovery IDs', [
                        'error' => $e->getMessage(),
                        'v' => $v,
                        'recovery_ids_tried' => $recoveryIds
                    ]);
                    throw new \Exception('Failed to recover address: ' . $e->getMessage());
                }
            }
        }

        throw new \Exception('Unable to recover address from signature');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function directLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string|regex:/^0x[a-fA-F0-9]{40}$/',
            'signature' => 'required|string|min:132',
            'wallet_type' => 'required|string|in:' . implode(',', array_keys(self::SUPPORTED_WALLETS)),
            'referral_code' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            $this->loginHistoryService->logAttempt($request->wallet_address ?? 'unknown', false, $request);

            return response()->json([
                'success' => false,
                'message' => 'Invalid input data'
            ], 400);
        }

        $walletAddress = strtolower($request->wallet_address);
        $signature = $request->signature;
        $walletType = $request->wallet_type;
        $referralCode = $request->referral_code;
        $nonce = Cache::get("wallet_nonce:{$walletAddress}");

        if (!$nonce) {
            $this->loginHistoryService->logAttempt($walletAddress, false, $request);

            return response()->json([
                'success' => false,
                'message' => 'Nonce expired or not found. Please request a new nonce.'
            ], 401);
        }

        if (!$this->verifySignature($walletAddress, $nonce, $signature)) {
            $this->loginHistoryService->logAttempt($walletAddress, false, $request);
            Cache::forget("wallet_nonce:{$walletAddress}");

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature. Signature verification failed.'
            ], 401);
        }

        Cache::forget("wallet_nonce:{$walletAddress}");
        DB::beginTransaction();
        try {
            $existingUser = User::where('wallet_address', $walletAddress)->first();
            $isNewUser = !$existingUser;

            $referredBy = null;
            $referrer = null;

            if ($isNewUser && $referralCode) {
                $referrer = User::where('referral_code', $referralCode)->first();

                if ($referrer) {
                    $referredBy = $referrer->id;

                    Log::info('Referral code applied', [
                        'referral_code' => $referralCode,
                        'referrer_id' => $referrer->id,
                        'referrer_name' => $referrer->name,
                        'wallet_address' => $walletAddress
                    ]);
                }
            }

            $user = User::firstOrCreate(
                ['wallet_address' => $walletAddress],
                [
                    'uid' => 'UID' . strtoupper(Str::random(8)),
                    'name' => self::SUPPORTED_WALLETS[$walletType] . ' User',
                    'email' => 'wallet_' . substr($walletAddress, 2, 8) . '@noreply.local',
                    'password' => bcrypt(Str::random(32)),
                    'wallet_type' => $walletType,
                    'email_verified_at' => now(),
                    'role' => 'user',
                    'status' => 'active',
                    'referred_by' => $referredBy,
                    'referral_code' => strtoupper(Str::random(10)),
                ]
            );

            if ($isNewUser) {
                EmailTemplateService::sendTemplateEmail('welcome', $user, [
                    'user_name' => e($user->name),
                ]);

                if ($referrer) {
                    UserReferral::create([
                        'referrer_id' => $referrer->id,
                        'referred_id' => $user->id,
                        'level' => 1,
                        'status' => 'active',
                        'total_commission_earned' => 0,
                        'total_referral_investment' => 0,
                        'generation_count' => 0
                    ]);

                    $referrer->increment('total_referrals');
                    $referrer->increment('active_referrals');

                    Log::info('User referral relationship created', [
                        'referrer_id' => $referrer->id,
                        'referred_id' => $user->id,
                        'referral_code' => $referralCode
                    ]);
                }

                Log::info('New user created via Web3', [
                    'user_id' => $user->id,
                    'wallet_address' => $walletAddress,
                    'referred_by' => $referredBy,
                    'referrer_code' => $referralCode
                ]);
            }

            $user->update([
                'last_login_at' => now(),
                'wallet_type' => $walletType
            ]);

            // Create or update wallet with blockchain address
            $wallet = $user->wallet()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => 'Main Wallet',
                    'address' => $walletAddress,
                    'balance' => 0,
                    'bonus_balance' => 0,
                    'total_invested' => 0,
                    'total_withdrawn' => 0,
                    'total_earnings' => 0,
                    'total_referral_bonus' => 0,
                    'status' => 'active',
                    'last_activity' => now()
                ]
            );

            // Update wallet address to blockchain address if it's different
            // (in case UserObserver created wallet with generated address)
            if ($wallet->address !== $walletAddress) {
                $wallet->update([
                    'address' => $walletAddress,
                    'last_activity' => now()
                ]);
            } else {
                $wallet->update(['last_activity' => now()]);
            }

            DB::commit();
            Auth::login($user, true);
            $request->session()->regenerate();

            Log::info('User logged in via Web3', [
                'user_id' => $user->id,
                'wallet_address' => $walletAddress,
                'wallet_type' => $walletType,
                'is_new_user' => $isNewUser,
                'referred_by' => $referredBy,
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);

            $this->loginHistoryService->logAttempt($user->email, true, $request);

            // Generate one-time auth token for cross-browser session transfer
            $authToken = Str::random(64);
            DB::table('auth_tokens')->insert([
                'user_id' => $user->id,
                'token' => $authToken,
                'expires_at' => now()->addMinutes(5), // Token valid for 5 minutes
                'used' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'is_new_user' => $isNewUser,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'wallet_address' => $user->wallet_address,
                    'wallet_type' => $user->wallet_type,
                    'referral_code' => $user->referral_code,
                ],
                'auth_token' => $authToken, // Include token for cross-browser transfer
                'redirect_url' => route('user.dashboard')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            $emailIdentifier = 'wallet_' . substr($walletAddress, 2, 8) . '@noreply.local';
            $this->loginHistoryService->logAttempt($emailIdentifier, false, $request);

            Log::error('Error in direct login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'wallet_address' => $walletAddress,
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ], 500);
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function emailLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            $this->loginHistoryService->logAttempt($request->email ?? 'unknown', false, $request);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!Auth::attempt($request->only('email', 'password'), true)) {
            $this->loginHistoryService->logAttempt($request->email, false, $request);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        $request->session()->regenerate();
        Log::info('User logged in via email', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip()
        ]);

        $this->loginHistoryService->logAttempt($user->email, true, $request);
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'referral_code' => $user->referral_code,
            ],
            'redirect_url' => route('user.dashboard')
        ]);
    }

    /**
     * Exchange auth token for session (for cross-browser authentication)
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function exchangeToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64',
        ]);

        if ($validator->fails()) {
            return redirect()->route('login')->with('error', 'Invalid token format.');
        }

        $token = $request->input('token');

        // Find and validate token
        $authToken = DB::table('auth_tokens')
            ->where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$authToken) {
            return redirect()->route('login')->with('error', 'Invalid or expired token. Please try connecting again.');
        }

        // Get user
        $user = User::find($authToken->user_id);
        if (!$user) {
            DB::table('auth_tokens')->where('token', $token)->update(['used' => true]);
            return redirect()->route('login')->with('error', 'User not found.');
        }

        // Mark token as used
        DB::table('auth_tokens')->where('token', $token)->update(['used' => true]);

        // Log user in
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('last_activity', time());
        $request->session()->put('user_ip', $request->ip());
        $request->session()->put('user_agent_hash', hash('sha256', $request->userAgent() ?? ''));

        $user->update(['last_login_at' => now()]);

        Log::info('User authenticated via token exchange', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // If AJAX request, return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
                'redirect_url' => route('user.dashboard')
            ]);
        }

        // Otherwise redirect
        return redirect()->route('user.dashboard')->with('success', 'Successfully authenticated!');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function emailRegister(Request $request): JsonResponse
    {
        $request->merge(array_map(function($value) {
            return is_string($value) ? strip_tags(trim($value)) : $value;
        }, $request->all()));

        if (!SettingsService::isRegistrationEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration is currently disabled.'
            ], 403);
        }

        $minPasswordLength = Setting::get('password_min_length', 8);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                "min:{$minPasswordLength}",
                'max:255',
                'confirmed'
            ],
            'referral_code' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        DB::beginTransaction();
        try {
            $email = strtolower(trim($request->email));
            $referredBy = null;
            $referrer = null;
            if ($request->referral_code) {
                $referrer = User::where('referral_code', $request->referral_code)->first();
                if ($referrer) {
                    $referredBy = $referrer->id;
                    Log::info('Referral code applied during registration', [
                        'referral_code' => $request->referral_code,
                        'referrer_id' => $referrer->id,
                        'email' => $email
                    ]);
                }
            }

            $user = User::create([
                'uid' => 'UID' . strtoupper(Str::random(8)),
                'name' => explode('@', $email)[0],
                'email' => $email,
                'password' => Hash::make($request->password),
                'role' => 'user',
                'status' => 'pending',
                'email_verified_at' => EmailVerificationService::isVerificationRequired() ? null : now(),
                'referred_by' => $referredBy,
                'referral_code' => strtoupper(Str::random(10)),
            ]);

            Log::info('User registered via email', [
                'user_id' => $user->id,
                'email' => $email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if (EmailVerificationService::isVerificationRequired()) {
                EmailVerificationService::sendVerificationEmail($user);
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful! Please check your email and verify your account before logging in.',
                    'requires_verification' => true,
                    'redirect_url' => route('home')
                ]);
            } else {
                $user->update([
                    'status' => 'active',
                    'email_verified_at' => now()
                ]);
            }

            if ($referrer) {
                UserReferral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $user->id,
                    'level' => 1,
                    'status' => 'active',
                    'total_commission_earned' => 0,
                    'total_referral_investment' => 0,
                    'generation_count' => 0
                ]);

                $referrer->increment('total_referrals');
                $referrer->increment('active_referrals');

                Log::info('Referral relationship created', [
                    'referrer_id' => $referrer->id,
                    'referred_id' => $user->id,
                    'referral_code' => $request->referral_code
                ]);
            }

            if (!EmailVerificationService::isVerificationRequired()) {
                EmailTemplateService::sendTemplateEmail('welcome', $user, [
                    'user_name' => e($user->name),
                ]);
            }

            $user->update(['last_login_at' => now()]);
            DB::commit();

            Auth::login($user, true);
            $request->session()->regenerate();

            $this->loginHistoryService->logAttempt($user->email, true, $request);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'requires_verification' => false,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'referral_code' => $user->referral_code,
                ],
                'redirect_url' => route('user.dashboard')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in email registration', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.'
            ], 500);
        }
    }

    /**
     * @param $signature
     * @return bool
     */
    private function isValidSignature($signature): bool
    {
        return (
            is_string($signature) &&
            strlen($signature) >= 130 &&
            str_starts_with($signature, '0x') &&
            ctype_xdigit(substr($signature, 2))
        );
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        try {
            $userId = Auth::id();
            $walletAddress = Auth::user()?->wallet_address;

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info('User logged out', [
                'user_id' => $userId,
                'wallet_address' => $walletAddress
            ]);

            return redirect()->route('home')->with('success', 'Logged out successfully');

        } catch (\Exception $e) {
            Log::error('Error during logout', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Logout failed');
        }
    }
}

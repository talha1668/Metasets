<?php

namespace App\Http\Controllers\User;

use App\Concerns\UploadedFile;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\WithdrawalGateway;
use App\Services\EmailTemplateService;
use App\Services\ReferralService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Random\RandomException;

class FinanceController extends Controller
{
    use UploadedFile;

    private const MAX_FILE_SIZE = 5120;
    private const ALLOWED_FILE_TYPES = ['jpeg', 'png', 'jpg', 'gif', 'pdf'];

    public function __construct(protected readonly ReferralService $referralService)
    {
    }


    /**
     * @return Response
     */
    public function index(): Response
    {
        try {
            $user = Auth::user();
            $key = 'finance-index:' . $user->id . ':' . request()->ip();

            if (RateLimiter::tooManyAttempts($key, 50)) {
                $seconds = RateLimiter::availableIn($key);
                return $this->emptyFinanceResponse("Too many requests. Please try again in {$seconds} seconds.");
            }

            $paymentGateways = PaymentGateway::where('status', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($gateway) => $this->sanitizeGateway($gateway));

            $withdrawalGateways = WithdrawalGateway::where('status', true)
                ->orderBy('name')
                ->get()
                ->map(fn($gateway) => $this->sanitizeGateway($gateway));

            $deposits = Deposit::with('paymentGateway')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $deposits->getCollection()->transform(fn($deposit) => $this->sanitizeTransaction($deposit, 'deposit'));
            $withdrawals = Withdrawal::with('withdrawalGateway')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $withdrawals->getCollection()->transform(fn($withdrawal) => $this->sanitizeTransaction($withdrawal, 'withdrawal'));
            $wallet = $user->wallet;
            RateLimiter::hit($key, 60);

            return Inertia::render('User/Finance/Index', [
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'paymentGateways' => $paymentGateways,
                'withdrawalGateways' => $withdrawalGateways,
                'walletBalance' => $wallet->balance ?? 0,
            ]);

        } catch (Exception $e) {
            Log::error('Finance Index Error: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            return $this->emptyFinanceResponse('Unable to load finance page. Please try again.');
        }
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeDeposit(Request $request): RedirectResponse
    {
        try {
            $user = Auth::user();
            $key = 'create-deposit:' . $user->id;

            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                return redirect()->back()->withErrors(['error' => "Too many deposit attempts. Please try again in {$seconds} seconds."]);
            }

            $validated = $request->validate([
                'payment_gateway_id' => 'required|integer|min:1|exists:payment_gateways,id',
                'amount' => 'required|numeric|min:0.01|max:999999999',
                'payment_details' => 'sometimes|array|max:20',
                'payment_method_id' => 'sometimes|string|max:255',
                'selected_currency' => 'sometimes|string|max:10',
            ], [
                'payment_gateway_id.required' => 'Please select a payment gateway.',
                'payment_gateway_id.exists' => 'Selected payment gateway is invalid.',
                'amount.required' => 'Please enter a deposit amount.',
                'amount.numeric' => 'Deposit amount must be a valid number.',
                'amount.min' => 'Deposit amount must be greater than 0.',
                'amount.max' => 'Deposit amount is too large.',
            ]);

            $gateway = PaymentGateway::where('id', $validated['payment_gateway_id'])
                ->where('status', true)
                ->first();

            if (!$gateway) {
                RateLimiter::hit($key, 300);
                throw ValidationException::withMessages(['payment_gateway_id' => ['Selected payment gateway is not available.']]);
            }

            $requestedAmount = round((float) $validated['amount'], 2);
            $this->validateAmount($requestedAmount, $gateway->min_amount, $gateway->max_amount, $gateway->currency);

            $wallet = $user->wallet;
            if (!$wallet) {
                RateLimiter::hit($key, 300);
                throw ValidationException::withMessages(['error' => ['Wallet not found. Please contact support.']]);
            }

            $charge = $this->calculateCharge($requestedAmount, $gateway);
            $finalAmount = round($requestedAmount + $charge, 2);
            $depositAmount = $this->convertToSiteCurrency($requestedAmount, $gateway);

            $processedDetails = [];
            if ($gateway->type === 'manual') {
                $processedDetails = $this->validateAndProcessFields(
                    $validated['payment_details'] ?? [],
                    $gateway,
                    'payment_details'
                );
            }

            DB::beginTransaction();

            try {
                $trx = $this->generateUniqueTrx('DP', Deposit::class);
                $deposit = Deposit::create([
                    'user_id' => $user->id,
                    'payment_gateway_id' => $gateway->id,
                    'trx' => $trx,
                    'amount' => $requestedAmount,
                    'charge' => $charge,
                    'final_amount' => $finalAmount,
                    'deposit_amount' => $depositAmount,
                    'currency' => strtoupper($gateway->currency),
                    'status' => 'pending',
                    'payment_details' => $processedDetails,
                    'conversion_rate' => $gateway->rate,
                ]);

                if ($gateway->type === 'automatic') {
                    $result = $this->processAutomaticPayment($deposit, $gateway, $request);

                    if ($result['success']) {
                        if ($gateway->slug === 'nowpayments') {
                            DB::commit();
                            RateLimiter::clear($key);

                            return redirect()->back()->with('success', 'Payment request created! Please send ' .
                                ($deposit->payment_details['pay_amount'] ?? '') . ' ' .
                                strtoupper($deposit->payment_details['pay_currency'] ?? '') .
                                ' to complete your deposit. Transaction ID: ' . e($trx));
                        }

                        $deposit->update([
                            'status' => 'approved',
                            'transaction_id' => substr($result['transaction_id'], 0, 255),
                            'approved_at' => now()
                        ]);

                        $this->addBalanceToWallet($user, $deposit);

                        DB::commit();
                        RateLimiter::clear($key);

                        return redirect()->back()->with('success', 'Deposit successful! Balance added to your account. Transaction ID: ' . e($trx));

                    } else {
                        $deposit->update([
                            'status' => 'rejected',
                            'admin_response' => substr($result['message'], 0, 500),
                            'rejected_at' => now()
                        ]);

                        DB::commit();
                        RateLimiter::hit($key, 300);
                        return redirect()->back()->withErrors(['payment' => e($result['message'])]);
                    }
                } else {
                    DB::commit();
                    RateLimiter::clear($key);
                    return redirect()->back()->with('success', 'Deposit request submitted successfully! Transaction ID: ' . e($trx));
                }

            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Deposit Store Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['payment_details', 'payment_method_id'])
            ]);

            RateLimiter::hit($key ?? 'create-deposit:' . Auth::id(), 300);
            return redirect()->back()
                ->withErrors(['error' => 'Unable to process your deposit request. Please try again or contact support.'])
                ->withInput();
        }
    }


    /**
     * @param $deposit
     * @param $gateway
     * @param $request
     * @return array
     */
    private function processAutomaticPayment($deposit, $gateway, $request): array
    {
        try {
            return match ($gateway->slug) {
                'nowpayments' => $this->processNowPaymentsPayment($deposit, $gateway, $request),
                default => ['success' => false, 'message' => 'Payment gateway not supported'],
            };
        } catch (Exception $e) {
            Log::error('Automatic Payment Error: ' . $e->getMessage(), [
                'deposit_id' => $deposit->id,
                'gateway_slug' => $gateway->slug
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeWithdrawal(Request $request): RedirectResponse
    {
        try {
            $user = Auth::user();
            $key = 'withdrawal-create:' . $user->id . ':' . request()->ip();

            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                return redirect()->back()->withErrors(['error' => "Too many withdrawal attempts. Please try again in {$seconds} seconds."]);
            }

            if ($user->kyc_status !== 'approved') {
                RateLimiter::hit($key, 300);
                $kycMessage = match($user->kyc_status) {
                    'pending' => 'Please complete your KYC verification before making withdrawals.',
                    'reviewing' => 'Your KYC verification is under review. Withdrawals will be available once approved.',
                    'rejected' => 'Your KYC verification was rejected. Please resubmit your documents.',
                    default => 'KYC verification is required for withdrawals.'
                };
                return redirect()->back()->withErrors(['error' => $kycMessage]);
            }

            $validated = $request->validate([
                'withdrawal_gateway_id' => 'required|integer|min:1|exists:withdrawal_gateways,id',
                'amount' => 'required|numeric|min:0.01|max:999999999',
                'withdrawal_details' => 'required|array|max:20',
            ], [
                'withdrawal_gateway_id.required' => 'Please select a withdrawal gateway.',
                'withdrawal_gateway_id.exists' => 'Selected withdrawal gateway is invalid.',
                'amount.required' => 'Please enter a withdrawal amount.',
                'amount.numeric' => 'Withdrawal amount must be a valid number.',
                'amount.min' => 'Withdrawal amount must be greater than 0.',
                'amount.max' => 'Withdrawal amount is too large.',
                'withdrawal_details.required' => 'Please provide withdrawal details.',
            ]);

            $gateway = WithdrawalGateway::where('id', $validated['withdrawal_gateway_id'])
                ->where('status', true)
                ->first();

            if (!$gateway) {
                RateLimiter::hit($key, 300);
                throw ValidationException::withMessages(['withdrawal_gateway_id' => ['Selected withdrawal gateway is not available.']]);
            }

            $requestedAmount = round((float) $validated['amount'], 2);
            $this->validateAmount($requestedAmount, $gateway->min_amount, $gateway->max_amount, $gateway->currency);

            $wallet = $user->wallet;
            if (!$wallet) {
                RateLimiter::hit($key, 300);
                throw ValidationException::withMessages(['error' => ['Wallet not found. Please contact support.']]);
            }

            $charge = $this->calculateCharge($requestedAmount, $gateway);
            $finalAmount = round($requestedAmount - $charge, 2);
            $convertFinalAmount = $this->convertToGatewayCurrency($requestedAmount, $gateway);
            $withdrawalAmount = $this->convertToGatewayCurrency($finalAmount, $gateway);

            if ($convertFinalAmount > $wallet->balance) {
                RateLimiter::hit($key, 300);
                throw ValidationException::withMessages(['amount' => ['Insufficient balance for this withdrawal including fees.']]);
            }

            if ($finalAmount <= 0) {
                RateLimiter::hit($key, 300);
                throw ValidationException::withMessages(['amount' => ['Amount too small after deducting fees.']]);
            }

            $processedDetails = $this->validateAndProcessFields($validated['withdrawal_details'], $gateway, 'withdrawal_details');
            DB::beginTransaction();

            try {
                $trx = $this->generateUniqueTrx('WD', Withdrawal::class);
                $withdrawal = Withdrawal::create([
                    'user_id' => $user->id,
                    'withdrawal_gateway_id' => $gateway->id,
                    'trx' => $trx,
                    'amount' => $requestedAmount,
                    'charge' => $charge,
                    'final_amount' => $finalAmount,
                    'withdrawal_amount' => $withdrawalAmount,
                    'currency' => strtoupper($gateway->currency),
                    'status' => 'pending',
                    'user_data' => $processedDetails,
                    'conversion_rate' => $gateway->rate,
                ]);

                $this->deductFromWallet($user, $withdrawal);
                DB::commit();
                RateLimiter::clear($key);
                try {
                    EmailTemplateService::sendTemplateEmail('withdrawal_request', $user, [
                        'user_name' => e($user->name),
                        'amount' => round($requestedAmount, 2),
                        'trx' => e($trx),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send withdrawal request email', [
                        'user_id' => $user->id,
                        'withdrawal_id' => $withdrawal->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return redirect()->back()->with('success', 'Withdrawal request submitted successfully! Transaction ID: ' . e($trx));

            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Withdrawal Store Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['withdrawal_details'])
            ]);

            RateLimiter::hit($key ?? 'withdrawal-create:' . Auth::id(), 300);
            return redirect()->back()
                ->withErrors(['error' => 'Unable to process your withdrawal request. Please try again or contact support.'])
                ->withInput();
        }
    }


    /**
     * @param Withdrawal $withdrawal
     * @return RedirectResponse
     */
    public function cancelWithdrawal(Withdrawal $withdrawal): RedirectResponse
    {
        try {
            $user = Auth::user();
            $key = 'withdrawal-cancel:' . $user->id . ':' . request()->ip();

            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableIn($key);
                return redirect()->back()->withErrors(['error' => "Too many cancellation attempts. Please try again in {$seconds} seconds."]);
            }

            if ($withdrawal->user_id !== $user->id) {
                RateLimiter::hit($key, 300);
                abort(403, 'Unauthorized access to withdrawal');
            }

            if ($withdrawal->status !== 'pending') {
                RateLimiter::hit($key, 300);
                throw ValidationException::withMessages(['error' => ['Only pending withdrawals can be cancelled.']]);
            }

            DB::beginTransaction();
            try {
                $withdrawal->update([
                    'status' => 'rejected',
                    'admin_response' => 'Cancelled by user',
                    'rejected_at' => now(),
                    'rejected_by' => $user->id,
                ]);

                $this->refundToWallet($withdrawal->user, $withdrawal);
                DB::commit();
                RateLimiter::clear($key);

                return redirect()->back()->with('success', 'Withdrawal cancelled successfully. Amount refunded to your wallet.');

            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (Exception $e) {


            Log::error('Withdrawal Cancellation Error: ' . $e->getMessage(), [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => Auth::id()
            ]);

            RateLimiter::hit($key ?? 'withdrawal-cancel:' . Auth::id(), 300);
            return redirect()->back()
                ->withErrors(['error' => 'Unable to cancel withdrawal. Please try again or contact support.']);
        }
    }


    /**
     * @param array $details
     * @param $gateway
     * @param string $fieldPrefix
     * @return array
     * @throws ValidationException
     */
    private function validateAndProcessFields(array $details, $gateway, string $fieldPrefix): array
    {
        if (!$gateway->parameters || !is_array($gateway->parameters)) {
            return [];
        }

        if (count($gateway->parameters) > 20) {
            throw new Exception('Too many gateway parameters');
        }

        $rules = [];
        $messages = [];
        $processedDetails = [];

        foreach ($gateway->parameters as $param) {
            if (!isset($param['field_name']) || !is_string($param['field_name'])) {
                continue;
            }

            $fieldName = strip_tags($param['field_name']);
            if (strlen($fieldName) > 50) {
                continue;
            }

            $ruleKey = "{$fieldPrefix}.{$fieldName}";
            $fieldLabel = e($param['field_label'] ?? $fieldName);

            $this->buildValidationRules($param, $ruleKey, $fieldLabel, $rules, $messages);
        }

        if (!empty($rules)) {
            request()->validate($rules, $messages);
        }

        foreach ($gateway->parameters as $param) {
            if (!isset($param['field_name'])) {
                continue;
            }

            $fieldName = strip_tags($param['field_name']);
            $value = $details[$fieldName] ?? null;

            if ($param['field_type'] === 'file' && request()->hasFile("{$fieldPrefix}.{$fieldName}")) {
                try {
                    $file = request()->file("{$fieldPrefix}.{$fieldName}");
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, self::ALLOWED_FILE_TYPES)) {
                        throw new Exception('Invalid file type');
                    }

                    $processedDetails[$fieldName] = $this->move($file);
                } catch (Exception $e) {
                    Log::error('File upload error: ' . $e->getMessage());
                    throw ValidationException::withMessages([
                        "{$fieldPrefix}.{$fieldName}" => 'Failed to upload file. Please try again.'
                    ]);
                }
            } elseif ($value !== null && $value !== '') {
                if (is_string($value)) {
                    $processedDetails[$fieldName] = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
                } elseif (is_numeric($value)) {
                    $processedDetails[$fieldName] = (float) $value;
                } else {
                    $processedDetails[$fieldName] = $value;
                }
            }
        }

        return $processedDetails;
    }


    /**
     * @param array $param
     * @param string $ruleKey
     * @param string $fieldLabel
     * @param array $rules
     * @param array $messages
     * @return void
     */
    private function buildValidationRules(array $param, string $ruleKey, string $fieldLabel, array &$rules, array &$messages): void
    {
        $required = isset($param['field_required']) && $param['field_required'];
        $baseRequired = $required ? 'required|' : 'nullable|';

        switch ($param['field_type'] ?? 'text') {
            case 'email':
                $rules[$ruleKey] = $baseRequired . 'email|max:255';
                $messages["{$ruleKey}.email"] = "Please enter a valid email address.";
                break;

            case 'number':
                $rules[$ruleKey] = $baseRequired . 'numeric|min:0|max:999999999';
                $messages["{$ruleKey}.numeric"] = "The {$fieldLabel} must be a number.";
                break;

            case 'select':
                if (isset($param['field_options']) && is_array($param['field_options']) && count($param['field_options']) <= 50) {
                    $options = array_map('strip_tags', array_keys($param['field_options']));
                    $rules[$ruleKey] = $baseRequired . 'in:' . implode(',', $options);
                    $messages["{$ruleKey}.in"] = "Please select a valid {$fieldLabel}.";
                }
                break;

            case 'file':
                $rules[$ruleKey] = $baseRequired . 'file|mimes:' . implode(',', self::ALLOWED_FILE_TYPES) . '|max:' . self::MAX_FILE_SIZE;
                $messages["{$ruleKey}.mimes"] = "File must be an image or PDF.";
                $messages["{$ruleKey}.max"] = "File size must not exceed 5MB.";
                break;

            case 'date':
            case 'datetime-local':
                $rules[$ruleKey] = $baseRequired . 'date|after:1900-01-01|before:2100-01-01';
                $messages["{$ruleKey}.date"] = "Please enter a valid date.";
                break;

            case 'tel':
                $rules[$ruleKey] = $baseRequired . 'string|max:20|regex:/^[\+\-\(\)\s\d]+$/';
                $messages["{$ruleKey}.regex"] = "Please enter a valid phone number.";
                break;

            case 'text':
            default:
                $rules[$ruleKey] = $baseRequired . 'string|max:255';
                $messages["{$ruleKey}.string"] = "The {$fieldLabel} must be text.";
                $messages["{$ruleKey}.max"] = "The {$fieldLabel} must not exceed 255 characters.";
                break;
        }

        if ($required) {
            $messages["{$ruleKey}.required"] = "The {$fieldLabel} field is required.";
        }
    }


    /**
     * @param float $amount
     * @param float $minAmount
     * @param float $maxAmount
     * @param string $currency
     * @return void
     * @throws ValidationException
     */
    private function validateAmount(float $amount, float $minAmount, float $maxAmount, string $currency): void
    {
        if ($amount < $minAmount) {
            throw ValidationException::withMessages([
                'amount' => ["Minimum amount is " . e($currency) . " {$minAmount}"]
            ]);
        }

        if ($amount > $maxAmount) {
            throw ValidationException::withMessages([
                'amount' => ["Maximum amount is " . e($currency) . " {$maxAmount}"]
            ]);
        }
    }


    /**
     * @param float $amount
     * @param $gateway
     * @return float
     * @throws Exception
     */
    private function calculateCharge(float $amount, $gateway): float
    {
        try {
            $fixedCharge = (float) ($gateway->fixed_charge ?? 0);
            $percentCharge = (float) ($gateway->percent_charge ?? 0);

            if ($fixedCharge < 0 || $percentCharge < 0 || $percentCharge > 100) {
                throw new Exception('Invalid gateway charge configuration');
            }

            $charge = $fixedCharge + ($amount * $percentCharge / 100);
            return round($charge, 2);
        } catch (Exception $e) {
            Log::error('Charge calculation error: ' . $e->getMessage());
            throw new Exception('Unable to calculate charges');
        }
    }


    /**
     * @param float $amount
     * @param $gateway
     * @return float
     * @throws Exception
     */
    private function convertToSiteCurrency(float $amount, $gateway): float
    {
        try {
            if (!is_numeric($amount) || $amount < 0) {
                throw new Exception('Invalid amount for conversion');
            }

            $rate = (float) $gateway->rate;
            if ($rate <= 0) {
                throw new Exception('Invalid gateway conversion rate');
            }

            $convertedAmount = $amount * $rate;
            return round($convertedAmount, 2);
        } catch (Exception $e) {
            Log::error('Currency conversion error: ' . $e->getMessage());
            throw new Exception('Unable to convert currency');
        }
    }


    /**
     * @param float $amount
     * @param $gateway
     * @return float
     * @throws Exception
     */
    private function convertToGatewayCurrency(float $amount, $gateway): float
    {
        return $this->convertToSiteCurrency($amount, $gateway);
    }


    /**
     * @param string $prefix
     * @param string $model
     * @return string
     * @throws RandomException
     */
    private function generateUniqueTrx(string $prefix, string $model): string
    {
        $trx = $prefix . strtoupper(bin2hex(random_bytes(5)));
        $attempts = 0;

        while ($model::where('trx', $trx)->exists() && $attempts < 10) {
            $trx = $prefix . strtoupper(bin2hex(random_bytes(5)));
            $attempts++;
        }

        if ($attempts >= 10) {
            throw new Exception('Unable to generate unique transaction ID');
        }

        return $trx;
    }


    /**
     * Process NowPayments payment
     *
     * @param $deposit
     * @param $gateway
     * @param $request
     * @return array
     */
    private function processNowPaymentsPayment($deposit, $gateway, $request): array
    {
        $credentials = $gateway->credentials;

        if (!isset($credentials['api_key']) || !is_string($credentials['api_key'])) {
            return ['success' => false, 'message' => 'NowPayments configuration error'];
        }

        try {
            $selectedCurrency = $request->input('selected_currency', 'btc');
            $amount = round($deposit->final_amount, 2);

            if ($amount < 1) {
                return ['success' => false, 'message' => 'Amount too small for processing'];
            }

            Log::info('NowPayments Payment Details', [
                'final_amount' => $amount,
                'currency' => $selectedCurrency,
                'deposit_id' => $deposit->id
            ]);

            $response = Http::withHeaders([
                'x-api-key' => $credentials['api_key'],
                'Content-Type' => 'application/json',
            ])->post('https://api.nowpayments.io/v1/payment', [
                'price_amount' => $amount,
                'price_currency' => strtolower($gateway->currency),
                'pay_currency' => strtolower($selectedCurrency),
                'ipn_callback_url' => route('nowpayments.callback'),
                'order_id' => $deposit->trx,
                'order_description' => "Deposit #{$deposit->trx}",
                'success_url' => route('user.deposits.success', ['trx' => $deposit->trx]),
                'cancel_url' => route('user.finance.index'),
            ]);

            if (!$response->successful()) {
                Log::error('NowPayments API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'deposit_id' => $deposit->id
                ]);

                return [
                    'success' => false,
                    'message' => 'Payment gateway error: ' . ($response->json('message') ?? 'Unknown error')
                ];
            }

            $paymentData = $response->json();

            Log::info('NowPayments Payment Created', [
                'deposit_id' => $deposit->id,
                'deposit_trx' => $deposit->trx,
                'payment_id' => $paymentData['payment_id'] ?? null,
                'pay_address' => $paymentData['pay_address'] ?? null,
                'pay_amount' => $paymentData['pay_amount'] ?? null,
                'pay_currency' => $paymentData['pay_currency'] ?? null,
                'payment_status' => $paymentData['payment_status'] ?? null,
                'invoice_url' => $paymentData['invoice_url'] ?? null,
            ]);

            $deposit->update([
                'transaction_id' => $paymentData['payment_id'] ?? null,
                'payment_details' => array_merge($deposit->payment_details ?? [], [
                    'nowpayments_id' => $paymentData['payment_id'] ?? null,
                    'pay_address' => $paymentData['pay_address'] ?? null,
                    'pay_amount' => $paymentData['pay_amount'] ?? null,
                    'pay_currency' => $paymentData['pay_currency'] ?? null,
                    'payment_status' => $paymentData['payment_status'] ?? 'waiting',
                ])
            ]);

            return [
                'success' => true,
                'transaction_id' => $paymentData['payment_id'] ?? 'NP-' . $deposit->trx,
                'payment_url' => $paymentData['invoice_url'] ?? null,
                'requires_redirect' => true
            ];

        } catch (Exception $e) {
            Log::error('NowPayments Error: ' . $e->getMessage(), [
                'deposit_id' => $deposit->id
            ]);
            return [
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }


    /**
     * @param string $trx
     * @return RedirectResponse
     */
    public function success(string $trx): RedirectResponse
    {
        try {
            $deposit = Deposit::where('trx', $trx)
                ->where('user_id', Auth::id())
                ->first();

            if (!$deposit) {
                return redirect()->route('user.wallet.index')
                    ->withErrors(['error' => 'Deposit not found']);
            }

            if ($deposit->paymentGateway && $deposit->paymentGateway->slug === 'nowpayments') {
                $this->checkNowPaymentsStatus($deposit);
            }

            return redirect()->route('user.deposits.details', ['trx' => $trx])
                ->with('success', 'Payment initiated! Please wait for confirmation.');

        } catch (Exception $e) {
            Log::error('Deposit success page error: ' . $e->getMessage());
            return redirect()->route('user.wallet.index')
                ->with('info', 'Payment initiated! Please check your deposit status.');
        }
    }


    /**
     * @param Deposit $deposit
     * @return void
     */
    private function checkNowPaymentsStatus(Deposit $deposit): void
    {
        try {
            $gateway = $deposit->paymentGateway;
            $credentials = $gateway->credentials;

            if (!isset($credentials['api_key'])) {
                return;
            }

            $paymentId = $deposit->payment_details['nowpayments_id'] ?? $deposit->transaction_id;
            if (!$paymentId) {
                return;
            }

            $response = Http::withHeaders([
                'x-api-key' => $credentials['api_key'],
            ])->get("https://api.nowpayments.io/v1/payment/{$paymentId}");

            if ($response->successful()) {
                $paymentData = $response->json();
                $status = $paymentData['payment_status'] ?? 'waiting';

                $paymentDetails = $deposit->payment_details;
                $paymentDetails['payment_status'] = $status;
                $paymentDetails['updated_at'] = now()->toDateTimeString();

                $deposit->update([
                    'payment_details' => $paymentDetails
                ]);

                if (in_array($status, ['finished', 'confirmed'])) {
                    $this->approveNowPaymentsDeposit($deposit);
                }
            }

        } catch (Exception $e) {
            Log::error('NowPayments status check error: ' . $e->getMessage(), [
                'deposit_id' => $deposit->id
            ]);
        }
    }

    /**
     * Approve NowPayments deposit and add balance
     *
     * @param Deposit $deposit
     * @return void
     */
    private function approveNowPaymentsDeposit(Deposit $deposit): void
    {
        try {
            if ($deposit->status === 'approved') {
                return;
            }

            DB::beginTransaction();

            $deposit->update([
                'status' => 'approved',
                'approved_at' => now()
            ]);

            $user = $deposit->user;
            $this->addBalanceToWallet($user, $deposit);
            DB::commit();

            Log::info('NowPayments deposit approved', [
                'deposit_id' => $deposit->id,
                'user_id' => $user->id,
                'amount' => $deposit->deposit_amount
            ]);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('NowPayments deposit approval error: ' . $e->getMessage(), [
                'deposit_id' => $deposit->id
            ]);
        }
    }


    /**
     * @param $user
     * @param $deposit
     * @return void
     * @throws Exception
     */
    private function addBalanceToWallet($user, $deposit): void
    {
        try {
            $wallet = $user->wallet;
            if (!$wallet) {
                throw new Exception('Wallet not found');
            }

            $addAmount = (float) $deposit->deposit_amount;
            if ($addAmount <= 0) {
                throw new Exception('Invalid deposit amount');
            }

            $previousBalance = (float) $wallet->balance;
            $newBalance = round($previousBalance + $addAmount, 2);

            $wallet->update([
                'balance' => $newBalance,
                'last_activity' => now()
            ]);

            Transaction::create([
                'transaction_id' => $deposit->trx,
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $addAmount,
                'post_balance' => $newBalance,
                'status' => 'completed',
                'details' => "Deposit via " . e($deposit->paymentGateway->name) . " - " . round($deposit->amount, 2) . " " . e($deposit->currency)
            ]);

            try {
                EmailTemplateService::sendTemplateEmail('deposit_confirmation', $user, [
                    'user_name' => e($user->name),
                    'amount' => round($deposit->deposit_amount, 2),
                    'transaction_id' => e($deposit->trx),
                ]);

                $this->referralService->processReferral($user->id, $addAmount);
            } catch (Exception $e) {
                Log::warning('Failed to send deposit confirmation email', [
                    'user_id' => $user->id,
                    'deposit_id' => $deposit->id,
                    'error' => $e->getMessage()
                ]);
            }
        } catch (Exception $e) {
            Log::error('Wallet addition error: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * @param $user
     * @param $withdrawal
     * @return void
     * @throws Exception
     */
    private function deductFromWallet($user, $withdrawal): void
    {
        try {
            $wallet = $user->wallet;
            if (!$wallet) {
                throw new Exception('Wallet not found');
            }

            $deductionAmount = (float) $withdrawal->withdrawal_amount;
            if ($deductionAmount <= 0) {
                throw new Exception('Invalid deduction amount');
            }

            $previousBalance = (float) $wallet->balance;
            $newBalance = round($previousBalance - $deductionAmount, 2);

            if ($newBalance < 0) {
                throw new Exception('Insufficient wallet balance');
            }

            $wallet->update([
                'balance' => $newBalance,
                'last_activity' => now()
            ]);

            Transaction::create([
                'transaction_id' => Str::random(16),
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'amount' => $deductionAmount,
                'post_balance' => $newBalance,
                'status' => 'completed',
                'details' => "Withdrawal request via " . e($withdrawal->withdrawalGateway->name) . " - " . round($withdrawal->final_amount, 2) . " " . e($withdrawal->currency)
            ]);

            try {
                EmailTemplateService::sendTemplateEmail('withdrawal_request', $user, [
                    'user_name' => e($user->name),
                    'amount' => round($withdrawal->withdrawal_amount, 2),
                    'transaction_id' => e($withdrawal->trx),
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to send withdrawal request email', [
                    'user_id' => $user->id,
                    'withdrawal_id' => $withdrawal->id,
                    'error' => $e->getMessage()
                ]);
            }
        } catch (Exception $e) {
            Log::error('Wallet deduction error: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * @param $user
     * @param $withdrawal
     * @return void
     * @throws Exception
     */
    private function refundToWallet($user, $withdrawal): void
    {
        try {
            $wallet = $user->wallet;

            if (!$wallet) {
                throw new Exception('Wallet not found');
            }

            $refundAmount = (float) $withdrawal->withdrawal_amount;
            if ($refundAmount <= 0) {
                throw new Exception('Invalid refund amount');
            }

            $previousBalance = (float) $wallet->balance;
            $newBalance = round($previousBalance + $refundAmount, 2);

            $wallet->update([
                'balance' => $newBalance,
                'last_activity' => now()
            ]);

            Transaction::create([
                'transaction_id' => Str::random(16),
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $refundAmount,
                'post_balance' => $newBalance,
                'status' => 'completed',
                'details' => "Refund for cancelled withdrawal " . e($withdrawal->trx) . " - " . round($withdrawal->amount, 2) . " " . e($withdrawal->currency)
            ]);

            try {
                EmailTemplateService::sendTemplateEmail('withdrawal_cancelled', $user, [
                    'user_name' => e($user->name),
                    'amount' => round($withdrawal->withdrawal_amount, 2),
                    'transaction_id' => e($withdrawal->trx),
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to send withdrawal cancellation email', [
                    'user_id' => $user->id,
                    'withdrawal_id' => $withdrawal->id,
                    'error' => $e->getMessage()
                ]);
            }
        } catch (Exception $e) {
            Log::error('Wallet refund error: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * @param $gateway
     * @return object
     */
    private function sanitizeGateway($gateway): object
    {
        $gateway->name = e($gateway->name);
        $gateway->description = e($gateway->description ?? '');
        $gateway->currency = e($gateway->currency ?? 'USD');
        $gateway->min_amount = (float) $gateway->min_amount;
        $gateway->max_amount = (float) $gateway->max_amount;
        $gateway->fixed_charge = (float) $gateway->fixed_charge;
        $gateway->percent_charge = (float) $gateway->percent_charge;
        $gateway->credentials = (array) $gateway->credentials;
        $gateway->rate = (float) ($gateway->rate ?? 1);
        return $gateway;
    }


    /**
     * @param $transaction
     * @param string $type
     * @return object
     */
    private function sanitizeTransaction($transaction, string $type): object
    {
        $transaction->trx = e($transaction->trx);
        $transaction->currency = e($transaction->currency);
        $transaction->status = e($transaction->status ?? 'pending');

        if ($type === 'deposit' && $transaction->paymentGateway) {
            $transaction->paymentGateway->name = e($transaction->paymentGateway->name);
        } elseif ($type === 'withdrawal' && $transaction->withdrawalGateway) {
            $transaction->withdrawalGateway->name = e($transaction->withdrawalGateway->name);
        }

        return $transaction;
    }

    /**
     * @param string $message
     * @return Response
     */
    private function emptyFinanceResponse(string $message): Response
    {
        return Inertia::render('User/Finance/Index', [
            'deposits' => ['data' => [], 'total' => 0],
            'withdrawals' => ['data' => [], 'total' => 0],
            'paymentGateways' => [],
            'withdrawalGateways' => [],
            'walletBalance' => 0
        ])->with('error', $message);
    }
}

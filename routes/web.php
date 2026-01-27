<?php

use App\Http\Controllers\Auth\Web3WalletController;
use App\Http\Controllers\Payment\NowPaymentsWebhookController;
use App\Http\Controllers\User\AiRecommendationController;
use App\Http\Controllers\User\EarningsController;
use App\Http\Controllers\User\FinanceController;
use App\Http\Controllers\User\InvestmentController;
use App\Http\Controllers\User\ReferralController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\SettingsController;
use App\Http\Controllers\User\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['security.headers'])->group(function () {
    Route::middleware(['web'])->group(function () {
        Route::post('language/change', [LanguageController::class, 'changeLanguage'])->name('language.change');
        Route::get('api/languages', [LanguageController::class, 'getLanguages'])->name('api.languages');
        Route::get('api/languages/{code}/translations', [LanguageController::class, 'getTranslations'])->name('api.translations');
    });

    Route::get('/', [FrontendController::class, 'index'])->name('home');
    Route::get('/privacy-policy', [FrontendController::class, 'privacy']);
    Route::get('/terms', [FrontendController::class, 'terms']);
    Route::get('/cookies', [FrontendController::class, 'cookies']);
    Route::get('/default-image/{type?}/{width?}/{height?}', function($type = 'default', $width = 400, $height = 400) {
        return App\Services\DefaultImageService::generate($type, $width, $height);
    })->name('default.image');

    Route::post('/webhook/nowpayments', [NowPaymentsWebhookController::class, 'handleCallback'])->name('nowpayments.callback');
    Route::middleware(['guest', 'throttle:10,1', 'xss'])->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::get('/reset-password', [AuthController::class, 'showLogin'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/auth/email/login', [Web3WalletController::class, 'emailLogin'])->name('auth.web3.emailLogin');
    });

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware(['throttle:6,1'])->name('verification.send');

    Route::middleware(['auth', 'xss'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    Route::prefix('auth/web3')->group(function () {
        Route::post('/check-session', [Web3WalletController::class, 'checkSession']);
        Route::post('/nonce', [Web3WalletController::class, 'getNonce']);
        Route::post('/direct-login', [Web3WalletController::class, 'directLogin']);
        Route::post('/logout', [Web3WalletController::class, 'logout'])->name('web3.logout');
        Route::post('/email-login', [Web3WalletController::class, 'emailLogin'])->middleware('throttle:5,1');
        Route::post('/email-register', [Web3WalletController::class, 'emailRegister'])->middleware('throttle:5,1');
        Route::get('/exchange-token', [Web3WalletController::class, 'exchangeToken'])->name('web3.exchangeToken');
    });

    Route::middleware(['2fa', 'role:user', 'throttle:300,1', 'xss'])->prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings');

        Route::get('/investments', [InvestmentController::class, 'index'])->name('investments.index');
        Route::post('/invest', [InvestmentController::class, 'invest'])->name('investments.invest');
        Route::get('/investments/{investmentId}', [InvestmentController::class, 'show'])->name('investments.show');

        Route::post('/stake', [InvestmentController::class, 'stake'])->name('stakes.create');
        Route::post('/unstake/{stakeId}', [InvestmentController::class, 'unstake'])->name('stakes.unstake');
        Route::post('/claim-rewards/{stakeId}', [InvestmentController::class, 'claimRewards'])->name('stakes.claim');
        Route::get('/stakes/{stakeId}', [InvestmentController::class, 'showStake'])->name('stakes.show');

        Route::get('/referral', [ReferralController::class, 'index'])->name('referral');
        Route::get('/referral-network', [ReferralController::class, 'network'])->name('referral.network');
        Route::get('/referral/statistics', [ReferralController::class, 'statistics'])->name('referral.statistics');
        Route::get('/referral-list', [ReferralController::class, 'referrals'])->name('referral.list');
        Route::get('/referral-commissions', [ReferralController::class, 'commissions'])->name('referral.commissions');
        Route::get('/referral-network/level-breakdown', [ReferralController::class, 'levelBreakdown'])->name('referral.level-breakdown');
        Route::get('/referral-level', [ReferralController::class, 'referralLevelBreakdown'])->name('referral.level');

        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('/deposits/success/{trx}', [FinanceController::class, 'success'])->name('deposits.success');
        Route::post('/wallet/deposit', [FinanceController::class, 'storeDeposit'])->name('wallet.deposit.store');
        Route::post('/wallet/withdraw', [FinanceController::class, 'storeWithdrawal'])->name('wallet.withdraw.store');
        Route::patch('/wallet/withdraw/{withdrawal}/cancel', [FinanceController::class, 'cancelWithdrawal'])->name('wallet.withdraw.cancel');

        Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
            Route::get('/', [SupportTicketController::class, 'index'])->name('index');
            Route::get('/create', [SupportTicketController::class, 'create'])->name('create');
            Route::post('/', [SupportTicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [SupportTicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
            Route::get('/attachments/{attachment}/download', [SupportTicketController::class, 'downloadAttachment'])->name('attachments.download');
        });

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::delete('/settings/avatar', [SettingsController::class, 'removeAvatar'])->name('settings.avatar.remove');
        Route::post('/settings/kyc', [SettingsController::class, 'submitKyc'])->name('settings.kyc.submit');

        Route::prefix('ai-recommendations')->name('ai-recommendations.')->group(function () {
            Route::get('/', [AiRecommendationController::class, 'index'])->name('index');
            Route::get('/{recommendation}', [AiRecommendationController::class, 'show'])->name('show');
            Route::post('/generate', [AiRecommendationController::class, 'generate'])->name('generate');
            Route::post('/{recommendation}/mark-as-read', [AiRecommendationController::class, 'markAsRead'])->name('mark-as-read');
            Route::post('/{recommendation}/dismiss', [AiRecommendationController::class, 'dismiss'])->name('dismiss');
            Route::post('/{recommendation}/apply', [AiRecommendationController::class, 'apply'])->name('apply');
        });
    });
});

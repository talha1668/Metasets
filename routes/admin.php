<?php

use App\Http\Controllers\Admin\AdminSecurityController;
use App\Http\Controllers\Admin\AiRecommendationController;
use App\Http\Controllers\Admin\InvestmentLogController;
use App\Http\Controllers\Admin\InvestmentPlanController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StakingLogController;
use App\Http\Controllers\Admin\StakingPoolController;
use App\Http\Controllers\Admin\StakingRewardController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\SystemToolsController;
use App\Http\Controllers\Admin\KycVerificationController;
use App\Http\Controllers\Admin\LoginAttemptController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\SystemUpdateController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\WithdrawalGatewayController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\WalletsController;
use Illuminate\Support\Facades\Route;

Route::get('schedule/run', function () {
    Illuminate\Support\Facades\Artisan::call('schedule:run');
});

Route::middleware(['auth','2fa','role:admin', 'throttle:200,1', 'xss', 'security.headers', 'demo'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [DashboardController::class, 'profile']);
    Route::post('/profile/update', [DashboardController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [DashboardController::class, 'updatePassword'])->name('profile.password');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('/investments', [ReportController::class, 'investments'])->name('investments');
        Route::get('/staking', [ReportController::class, 'staking'])->name('staking');
        Route::get('/user-activity', [ReportController::class, 'userActivity'])->name('user-activity');
        Route::get('/commissions', [ReportController::class, 'commissions'])->name('commissions');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
        Route::get('/dashboard-stats', [ReportController::class, 'dashboardStats'])->name('dashboard-stats');
    });

    Route::get('/investment-plans', [InvestmentPlanController::class, 'index'])->name('investment-plans.index');
    Route::get('/investment-plans/create', [InvestmentPlanController::class, 'create'])->name('investment-plans.create');
    Route::post('/investment-plans', [InvestmentPlanController::class, 'store'])->name('investment-plans.store');
    Route::get('/investment-plans/{investmentPlan}/edit', [InvestmentPlanController::class, 'edit'])->name('investment-plans.edit');
    Route::put('/investment-plans/{investmentPlan}', [InvestmentPlanController::class, 'update'])->name('investment-plans.update');

    Route::get('/investment-logs', [InvestmentLogController::class, 'index'])->name('investment-logs.index');
    Route::get('/investment-logs/{investment}', [InvestmentLogController::class, 'show'])->name('investment-logs.show');
    Route::post('/investment-logs/{investment}/approve', [InvestmentLogController::class, 'approve'])->name('investment-logs.approve');
    Route::post('/investment-logs/{investment}/complete', [InvestmentLogController::class, 'complete'])->name('investment-logs.complete');
    Route::post('/investment-logs/{investment}/cancel', [InvestmentLogController::class, 'cancel'])->name('investment-logs.cancel');
    Route::get('/investment-logs/statistics', [InvestmentLogController::class, 'statistics'])->name('investment-logs.statistics');

    Route::resource('staking-pools', StakingPoolController::class);
    Route::get('/stakes', [StakingLogController::class, 'index'])->name('stakes.index');
    Route::get('/stakes/{stake}', [StakingLogController::class, 'show'])->name('stakes.show');
    Route::post('/stakes/{stake}/cancel', [StakingLogController::class, 'cancel'])->name('stakes.cancel');
    Route::post('/stakes/{stake}/complete', [StakingLogController::class, 'complete'])->name('stakes.complete');
    Route::post('/stakes/{stake}/add-rewards', [StakingLogController::class, 'addRewards'])->name('stakes.add-rewards');
    Route::get('/stakes/statistics/data', [StakingLogController::class, 'statistics'])->name('stakes.statistics');
    Route::get('/stakes/export/csv', [StakingLogController::class, 'export'])->name('stakes.export');

    Route::get('/staking-rewards', [StakingRewardController::class, 'index'])->name('staking-rewards.index');
    Route::post('/staking-rewards/process', [StakingRewardController::class, 'processRewards'])->name('staking-rewards.process');
    Route::post('/staking-rewards/bulk-process', [StakingRewardController::class, 'bulkProcess'])->name('staking-rewards.bulk-process');
    Route::delete('/staking-rewards/{reward}', [StakingRewardController::class, 'destroy'])->name('staking-rewards.destroy');
    Route::get('/staking-rewards/statistics/data', [StakingRewardController::class, 'statistics'])->name('staking-rewards.statistics');

    Route::get('/referrals/overview', [ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/settings', [ReferralController::class, 'settings'])->name('referrals.settings');
    Route::post('/referrals/settings', [ReferralController::class, 'storeSetting'])->name('referrals.settings.store');
    Route::put('/referrals/settings/{setting}', [ReferralController::class, 'updateSetting'])->name('referrals.settings.update');
    Route::delete('/referrals/settings/{setting}', [ReferralController::class, 'destroySetting'])->name('referrals.settings.destroy');
    Route::get('/referrals/bonuses', [ReferralController::class, 'bonuses'])->name('referrals.bonuses');
    Route::post('/referrals/bonuses', [ReferralController::class, 'storeBonus'])->name('referrals.bonuses.store');
    Route::put('/referrals/bonuses/{bonus}', [ReferralController::class, 'updateBonus'])->name('referrals.bonuses.update');
    Route::delete('/referrals/bonuses/{bonus}', [ReferralController::class, 'destroyBonus'])->name('referrals.bonuses.destroy');
    Route::get('/referrals/commissions', [ReferralController::class, 'commissions'])->name('admin.referrals.commissions');
    Route::get('/referrals/network', [ReferralController::class, 'network'])->name('admin.referrals.network');

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/recommendations', [AiRecommendationController::class, 'index'])->name('recommendations.index');
        Route::post('/recommendations/generate', [AiRecommendationController::class, 'generate'])->name('recommendations.generate');
        Route::post('/recommendations/bulk-update', [AiRecommendationController::class, 'bulkUpdate'])->name('recommendations.bulk-update');
        Route::post('/recommendations/analysis', [AiRecommendationController::class, 'runAnalysis'])->name('recommendations.analysis');
        Route::get('/analytics', [AiRecommendationController::class, 'analytics'])->name('analytics.index');
    });

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');
    Route::put('/users/{user}/kyc-status', [UserController::class, 'updateKycStatus'])->name('users.update-kyc-status');
    Route::post('/users/{user}/send-mail', [UserController::class, 'sendMail'])->name('users.send-mail');
    Route::post('/users/{user}/login-as', [UserController::class, 'loginAs'])->name('users.login-as');
    Route::get('/users/search', [UserController::class, 'search'])->name('admin.users.search');


    Route::get('/wallets', [WalletsController::class, 'index'])->name('wallets.index');
    Route::put('/wallets/{wallet}/status', [WalletsController::class, 'updateStatus'])->name('wallets.update-status');
    Route::put('/wallets/{wallet}/adjust-balance', [WalletsController::class, 'adjustBalance'])->name('wallets.adjust-balance');

    Route::get('/login-attempts', [LoginAttemptController::class, 'index'])->name('login-attempts.index');
    Route::post('/login-attempts/cleanup', [LoginAttemptController::class, 'clearOld'])->name('login-attempts.cleanup');

    Route::get('/kyc-verifications', [KycVerificationController::class, 'index'])->name('kyc-verifications.index');
    Route::put('/kyc-verifications/{kycVerification}/status', [KycVerificationController::class, 'updateStatus'])->name('kyc-verifications.update-status');
    Route::get('/kyc-verifications/{kycVerification}/download/{type}', [KycVerificationController::class, 'downloadDocument'])->name('kyc-verifications.download');
    Route::get('/kyc-verifications/{kycVerification}/download-all', [KycVerificationController::class, 'downloadAllDocuments'])->name('kyc-verifications.download-all');

    Route::resource('payment-gateways', PaymentGatewayController::class);
    Route::get('/deposits', [DepositController::class, 'index'])->name('deposits.index');
    Route::patch('/deposits/{deposit}/approve', [DepositController::class, 'approve'])->name('deposits.approve');
    Route::patch('/deposits/{deposit}/reject', [DepositController::class, 'reject'])->name('deposits.reject');
    Route::post('/deposits/bulk-action', [DepositController::class, 'bulkAction'])->name('deposits.bulk-action');
    Route::get('/deposits/{deposit}/view/{fieldName}', [DepositController::class, 'viewFile'])->name('deposits.view');

    Route::resource('withdrawal-gateways', WithdrawalGatewayController::class);
    Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::patch('withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::patch('withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::post('withdrawals/bulk-action', [WithdrawalController::class, 'bulkAction'])->name('withdrawals.bulk-action');

    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
    });

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
    Route::patch('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email.update');
    Route::patch('/settings/sms', [SettingsController::class, 'updateSms'])->name('settings.sms.update');
    Route::patch('/settings/security', [SettingsController::class, 'updateSecurity'])->name('settings.security.update');
    Route::patch('/settings/seo', [SettingsController::class, 'updateSeo'])->name('settings.seo.update');
    Route::patch('/settings/ai', [SettingsController::class, 'updateAi'])->name('settings.ai.update');
    Route::patch('/settings/email-template/{emailTemplate}', [SettingsController::class, 'updateEmailTemplate'])->name('settings.email-template.update');
    Route::patch('/settings/sms-template/{smsTemplate}', [SettingsController::class, 'updateSmsTemplate'])->name('settings.sms-template.update');
    Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
    Route::post('/settings/test-sms', [SettingsController::class, 'testSms'])->name('settings.test-sms');

    Route::get('/security/2fa', [AdminSecurityController::class, 'twoFactorIndex'])->name('security.2fa');
    Route::post('/security/2fa/enable', [AdminSecurityController::class, 'enableTwoFactor'])->name('security.2fa.enable');
    Route::post('/security/2fa/disable', [AdminSecurityController::class, 'disableTwoFactor'])->name('security.2fa.disable');
    Route::post('/security/2fa/verify', [AdminSecurityController::class, 'verifyTwoFactor'])->name('security.2fa.verify');

    Route::get('/cache-clear', [SystemToolsController::class, 'cacheClear'])->name('cache-clear');
    Route::post('/cache-clear', [SystemToolsController::class, 'executeCacheClear'])->name('cache-clear.execute');
    Route::get('/system-info', [SystemToolsController::class, 'systemInfo'])->name('system-info');
    Route::get('/system/cron', [SystemToolsController::class, 'cron'])->name('cron');

    Route::get('/languages', [LanguageController::class, 'index'])->name('languages.index');
    Route::post('/languages', [LanguageController::class, 'store'])->name('languages.store');
    Route::put('/languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
    Route::delete('/languages/{language}', [LanguageController::class, 'destroy'])->name('languages.destroy');
    Route::put('/languages/{language}/set-default', [LanguageController::class, 'setDefault'])->name('languages.set-default');

    Route::get('/languages/{language}/translations', [LanguageController::class, 'translations']);
    Route::put('/languages/{language}/translations/{translation}', [LanguageController::class, 'updateTranslation']);
    Route::post('/languages/{language}/translations', [LanguageController::class, 'storeTranslation']);
    Route::delete('/languages/{language}/translations/{translation}', [LanguageController::class, 'deleteTranslation']);

    Route::get('/support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('/support-tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::patch('/support-tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('support-tickets.update-status');
    Route::patch('/support-tickets/{ticket}/priority', [SupportTicketController::class, 'updatePriority'])->name('support-tickets.update-priority');
    Route::patch('/support-tickets/{ticket}/assign', [SupportTicketController::class, 'assign'])->name('support-tickets.assign');
    Route::post('/support-tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support-tickets.reply');
    Route::delete('/support-tickets/{ticket}', [SupportTicketController::class, 'destroy'])->name('support-tickets.destroy');
    Route::patch('/support-tickets/bulk-status', [SupportTicketController::class, 'bulkUpdateStatus'])->name('support-tickets.bulk-status');
    Route::get('/support-tickets/attachments/{attachment}/download', [SupportTicketController::class, 'downloadAttachment'])->name('support-tickets.download-attachment');

    Route::get('/system/update', [SystemUpdateController::class, 'index'])->name('system.update');
    Route::post('/system/migrate', [SystemUpdateController::class, 'systemMigrate'])->name('system.migrate');
});

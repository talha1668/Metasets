<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoMiddleware
{
    /**
     * Routes that should be blocked in demo mode
     */
    protected array $blockedRoutes = [
        // Profile Updates
        'admin.profile.update',
        'admin.profile.password',

        // Investment Plans
        'admin.investment-plans.store',
        'admin.investment-plans.update',

        // Investment Logs
        'admin.investment-logs.approve',
        'admin.investment-logs.complete',
        'admin.investment-logs.cancel',

        // Staking Pools
        'admin.staking-pools.store',
        'admin.staking-pools.update',
        'admin.staking-pools.destroy',

        // Stakes
        'admin.stakes.cancel',
        'admin.stakes.complete',
        'admin.stakes.add-rewards',

        // Staking Rewards
        'admin.staking-rewards.process',
        'admin.staking-rewards.bulk-process',
        'admin.staking-rewards.destroy',

        // Referrals
        'admin.referrals.settings.store',
        'admin.referrals.settings.update',
        'admin.referrals.settings.destroy',
        'admin.referrals.bonuses.store',
        'admin.referrals.bonuses.update',
        'admin.referrals.bonuses.destroy',

        // AI Recommendations
        'admin.ai.recommendations.generate',
        'admin.ai.recommendations.bulk-update',
        'admin.ai.recommendations.analysis',

        // Users
        'admin.users.update-status',
        'admin.users.update-kyc-status',
        'admin.users.send-mail',
        // Wallets
        'admin.wallets.update-status',
        'admin.wallets.adjust-balance',

        // Login Attempts
        'admin.login-attempts.cleanup',

        // KYC Verifications
        'admin.kyc-verifications.update-status',

        // Payment Gateways
        'admin.payment-gateways.store',
        'admin.payment-gateways.update',
        'admin.payment-gateways.destroy',

        // Withdrawal Gateways
        'admin.withdrawal-gateways.*',

        // Withdrawals
        'admin.withdrawals.approve',
        'admin.withdrawals.reject',
        'admin.withdrawals.bulk-action',

        // Settings (All setting updates)
        'admin.settings.general.update',
        'admin.settings.email.update',
        'admin.settings.sms.update',
        'admin.settings.security.update',
        'admin.settings.seo.update',
        'admin.settings.ai.update',
        'admin.settings.email-template.update',
        'admin.settings.sms-template.update',
        'admin.settings.test-email',
        'admin.settings.test-sms',

        // Security (2FA)
        'admin.security.2fa.enable',
        'admin.security.2fa.disable',
        'admin.security.2fa.verify',

        // System Tools
        'admin.cache-clear.execute',

        // Languages & Translations
        'admin.languages.store',
        'admin.languages.update',
        'admin.languages.destroy',
        'admin.languages.set-default',

        // Support Tickets
        'admin.support-tickets.update-status',
        'admin.support-tickets.update-priority',
        'admin.support-tickets.assign',
        'admin.support-tickets.reply',
        'admin.support-tickets.destroy',
        'admin.support-tickets.bulk-status',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (env('APP_DEMO') == 'demo') {
            $isModifyingMethod = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);

            if ($isModifyingMethod) {
                $currentRoute = $request->route()->getName();

                foreach ($this->blockedRoutes as $pattern) {
                    if ($this->matchesPattern($currentRoute, $pattern)) {
                        if ($request->expectsJson()) {
                            return response()->json([
                                'error' => 'This is a demo version. You cannot change anything.',
                                'message' => 'Modifications are disabled in demo mode.'
                            ], 403);
                        }

                        return back()->withErrors([
                            'error' => 'This is a demo version. You cannot change anything.'
                        ]);
                    }
                }
            }
        }

        return $next($request);
    }


    /**
     * @param string $routeName
     * @param string $pattern
     * @return bool
     */
    protected function matchesPattern(string $routeName, string $pattern): bool
    {
        $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/';
        return preg_match($regex, $routeName) === 1;
    }
}

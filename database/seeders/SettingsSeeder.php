<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\EmailTemplate;
use App\Models\SmsTemplate;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $generalSettings = [
            ['key' => 'site_name', 'value' => 'MetaStake ', 'type' => 'text', 'group' => 'general', 'label' => 'Site Name', 'description' => 'The name of your platform'],
            ['key' => 'site_logo', 'value' => '', 'type' => 'file', 'group' => 'general', 'label' => 'Site Logo', 'description' => 'Upload site logo image'],
            ['key' => 'site_favicon', 'value' => '', 'type' => 'file', 'group' => 'general', 'label' => 'Site Favicon', 'description' => 'Upload site favicon (16x16 or 32x32 pixels)'],
            ['key' => 'primary_color', 'value' => '#86efac', 'type' => 'color', 'group' => 'general', 'label' => 'Primary Theme Color', 'description' => 'Primary color for the theme'],
            ['key' => 'user_registration', 'value' => '1', 'type' => 'boolean', 'group' => 'general', 'label' => 'User Registration', 'description' => 'Allow new user registration'],
            ['key' => 'default_currency', 'value' => 'USD', 'type' => 'text', 'group' => 'general', 'label' => 'Default Currency', 'description' => 'Default platform currency code (e.g., USD, EUR, GBP)'],
            ['key' => 'currency_symbol', 'value' => '$', 'type' => 'text', 'group' => 'general', 'label' => 'Currency Symbol', 'description' => 'Currency symbol for display (e.g., $, €, £)'],
            ['key' => 'default_timezone', 'value' => 'America/New_York', 'type' => 'text', 'group' => 'general', 'label' => 'Default Timezone', 'description' => 'Default platform timezone (e.g., America/New_York, Europe/London)'],
            ['key' => 'tawk_property_id', 'value' => '', 'type' => 'text', 'group' => 'general', 'label' => 'Tawk Property ID', 'description' => 'Tawk.to Property ID'],
            ['key' => 'tawk_widget_id', 'value' => '', 'type' => 'text', 'group' => 'general', 'label' => 'Tawk Widget ID', 'description' => 'Tawk.to Widget ID'],
            ['key' => 'kyc_required', 'value' => '0', 'type' => 'boolean', 'group' => 'security', 'label' => 'KYC Required', 'description' => 'Require KYC verification for withdrawals'],
            ['key' => 'referral_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'general', 'label' => 'Enable Referral System', 'description' => 'Enable or disable referral system'],
            ['key' => 'auto_approve_investments', 'value' => '1', 'type' => 'boolean', 'group' => 'general', 'label' => 'Auto Approve Investments', 'description' => 'Automatically approve new investments'],
            [
                'key' => 'referral_system_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Referral System Enabled',
                'description' => 'Enable or disable the referral system',
            ],
            [
                'key' => 'referral_commission_auto_approve',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Referral Commission Auto Approve',
                'description' => 'Automatically approve referral commissions',
            ],
        ];

        $emailSettings = [
            ['key' => 'mail_driver', 'value' => 'smtp', 'type' => 'text', 'group' => 'email', 'label' => 'Mail Driver', 'description' => 'Email service provider'],
            ['key' => 'mail_host', 'value' => 'sandbox.smtp.mailtrap.io', 'type' => 'text', 'group' => 'email', 'label' => 'Mail Host', 'description' => 'SMTP server host'],
            ['key' => 'mail_port', 'value' => '2525', 'type' => 'text', 'group' => 'email', 'label' => 'Mail Port', 'description' => 'SMTP server port'],
            ['key' => 'mail_username', 'value' => '47a51809ba0204', 'type' => 'text', 'group' => 'email', 'label' => 'Mail Username', 'description' => 'SMTP username'],
            ['key' => 'mail_password', 'value' => 'adf6a0e880d2a7', 'type' => 'password', 'group' => 'email', 'label' => 'Mail Password', 'description' => 'SMTP password'],
            ['key' => 'mail_encryption', 'value' => 'ssl', 'type' => 'text', 'group' => 'email', 'label' => 'Mail Encryption', 'description' => 'Email encryption method'],
            ['key' => 'mail_from_address', 'value' => 'support@apprizo.xyz', 'type' => 'text', 'group' => 'email', 'label' => 'From Email', 'description' => 'Default sender email'],
            ['key' => 'mail_from_name', 'value' => 'TokenHive', 'type' => 'text', 'group' => 'email', 'label' => 'From Name', 'description' => 'Default sender name'],
        ];

        $smsSettings = [
            ['key' => 'sms_driver', 'value' => 'twilio', 'type' => 'text', 'group' => 'sms', 'label' => 'SMS Driver', 'description' => 'SMS service provider'],
            ['key' => 'twilio_sid', 'value' => '', 'type' => 'text', 'group' => 'sms', 'label' => 'API Key', 'description' => 'SMS service API key'],
            ['key' => 'twilio_token', 'value' => '', 'type' => 'password', 'group' => 'sms', 'label' => 'API Secret', 'description' => 'SMS service API secret'],
            ['key' => 'sms_from', 'value' => '+1234567890', 'type' => 'text', 'group' => 'sms', 'label' => 'From Number', 'description' => 'Default sender phone number'],
        ];

        $securitySettings = [
            ['key' => 'login_attempts', 'value' => '5', 'type' => 'text', 'group' => 'security', 'label' => 'Max Login Attempts', 'description' => 'Maximum failed login attempts'],
            ['key' => 'session_timeout', 'value' => '60', 'type' => 'text', 'group' => 'security', 'label' => 'Session Timeout', 'description' => 'Session timeout in minutes'],
            ['key' => 'password_min_length', 'value' => '8', 'type' => 'text', 'group' => 'security', 'label' => 'Min Password Length', 'description' => 'Minimum password length'],
            ['key' => 'require_email_verification', 'value' => '1', 'type' => 'boolean', 'group' => 'security', 'label' => 'Email Verification', 'description' => 'Require email verification for new users'],
        ];

        $seoSettings = [
            [
                'key' => 'meta_title',
                'value' => 'MetaStake - Web3 DeFi Investment & Staking Platform',
                'type' => 'text',
                'group' => 'seo',
                'label' => 'Meta Title',
                'description' => 'SEO meta title for homepage'
            ],
            [
                'key' => 'meta_description',
                'value' => 'MetaStake – Advanced Web3 DeFi platform featuring staking pools, investment opportunities, referral rewards, and secure multi-level earning system.',
                'type' => 'textarea',
                'group' => 'seo',
                'label' => 'Meta Description',
                'description' => 'SEO meta description for homepage'
            ],
            [
                'key' => 'meta_keywords',
                'value' => 'DeFi staking, Web3 investment, crypto staking pools, blockchain rewards, referral earnings, passive income, decentralized finance',
                'type' => 'textarea',
                'group' => 'seo',
                'label' => 'Meta Keywords',
                'description' => 'SEO meta keywords (comma separated)'
            ],
            [
                'key' => 'og_title',
                'value' => 'MetaStake - Professional DeFi Staking & Investment Platform',
                'type' => 'text',
                'group' => 'seo',
                'label' => 'Open Graph Title',
                'description' => 'Social media title'
            ],
            [
                'key' => 'og_description',
                'value' => 'Earn passive income with MetaStake – featuring flexible staking pools, Web3 wallet integration, multi-level referral rewards, and secure investment opportunities.',
                'type' => 'textarea',
                'group' => 'seo',
                'label' => 'Open Graph Description',
                'description' => 'Social media description'
            ],
            [
                'key' => 'og_image',
                'value' => '',
                'type' => 'file',
                'group' => 'seo',
                'label' => 'Open Graph Image',
                'description' => 'Social media sharing image (recommended 1200x630 pixels)'
            ],
            [
                'key' => 'google_analytics',
                'value' => '',
                'type' => 'text',
                'group' => 'seo',
                'label' => 'Google Analytics ID',
                'description' => 'Google Analytics tracking ID (GA4)'
            ],
        ];

        $aiSettings = [
            // Generation Control
            ['key' => 'ai_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'ai', 'label' => 'Enable AI Recommendations', 'description' => 'Enable/disable AI recommendation system'],
            ['key' => 'ai_max_recommendations_per_user', 'value' => '3', 'type' => 'number', 'group' => 'ai', 'label' => 'Max Recommendations Per User', 'description' => 'Maximum active recommendations each user can have'],
            ['key' => 'ai_recommendation_cooldown_hours', 'value' => '24', 'type' => 'number', 'group' => 'ai', 'label' => 'Recommendation Cooldown (Hours)', 'description' => 'Hours to wait before generating new recommendations for same user'],
            ['key' => 'ai_max_plans_per_recommendation', 'value' => '3', 'type' => 'number', 'group' => 'ai', 'label' => 'Max Plans Per Recommendation', 'description' => 'Maximum investment plans to recommend at once'],

            // Confidence & Thresholds
            ['key' => 'ai_confidence_threshold', 'value' => '60', 'type' => 'number', 'group' => 'ai', 'label' => 'Confidence Threshold', 'description' => 'Minimum confidence score to create recommendation (0-100)'],
            ['key' => 'ai_high_priority_threshold', 'value' => '80', 'type' => 'number', 'group' => 'ai', 'label' => 'High Priority Threshold', 'description' => 'Confidence score to mark recommendation as high priority (0-100)'],
            ['key' => 'ai_min_plan_confidence', 'value' => '50', 'type' => 'number', 'group' => 'ai', 'label' => 'Min Plan Confidence', 'description' => 'Minimum confidence score for a plan to be considered (0-100)'],
            ['key' => 'ai_base_confidence', 'value' => '50', 'type' => 'number', 'group' => 'ai', 'label' => 'Base Confidence Score', 'description' => 'Starting confidence score before adjustments (0-100)'],

            // Scoring Weights & Bonuses
            ['key' => 'ai_risk_match_weight', 'value' => '30', 'type' => 'number', 'group' => 'ai', 'label' => 'Risk Match Weight', 'description' => 'Weight of risk tolerance matching in confidence calculation'],
            ['key' => 'ai_affordability_multiplier', 'value' => '2', 'type' => 'number', 'group' => 'ai', 'label' => 'Affordability Multiplier', 'description' => 'User balance must be X times min investment for affordability bonus'],
            ['key' => 'ai_affordability_bonus', 'value' => '10', 'type' => 'number', 'group' => 'ai', 'label' => 'Affordability Bonus', 'description' => 'Confidence bonus when user can afford investment'],
            ['key' => 'ai_performance_bonus', 'value' => '15', 'type' => 'number', 'group' => 'ai', 'label' => 'Performance Bonus', 'description' => 'Confidence bonus for high-performing plans'],
            ['key' => 'ai_featured_bonus', 'value' => '5', 'type' => 'number', 'group' => 'ai', 'label' => 'Featured Plan Bonus', 'description' => 'Confidence bonus for featured investment plans'],
            ['key' => 'ai_success_rate_threshold', 'value' => '75', 'type' => 'number', 'group' => 'ai', 'label' => 'Success Rate Threshold', 'description' => 'Plan success rate required for performance bonus'],

            // Risk Matching - Conservative User
            ['key' => 'ai_conservative_low_match', 'value' => '100', 'type' => 'number', 'group' => 'ai', 'label' => 'Conservative + Low Risk Match', 'description' => 'Match percentage: Conservative user with low-risk plan'],
            ['key' => 'ai_conservative_medium_match', 'value' => '60', 'type' => 'number', 'group' => 'ai', 'label' => 'Conservative + Medium Risk Match', 'description' => 'Match percentage: Conservative user with medium-risk plan'],
            ['key' => 'ai_conservative_high_match', 'value' => '20', 'type' => 'number', 'group' => 'ai', 'label' => 'Conservative + High Risk Match', 'description' => 'Match percentage: Conservative user with high-risk plan'],

            // Risk Matching - Moderate User
            ['key' => 'ai_moderate_low_match', 'value' => '70', 'type' => 'number', 'group' => 'ai', 'label' => 'Moderate + Low Risk Match', 'description' => 'Match percentage: Moderate user with low-risk plan'],
            ['key' => 'ai_moderate_medium_match', 'value' => '100', 'type' => 'number', 'group' => 'ai', 'label' => 'Moderate + Medium Risk Match', 'description' => 'Match percentage: Moderate user with medium-risk plan'],
            ['key' => 'ai_moderate_high_match', 'value' => '70', 'type' => 'number', 'group' => 'ai', 'label' => 'Moderate + High Risk Match', 'description' => 'Match percentage: Moderate user with high-risk plan'],

            // Risk Matching - Aggressive User
            ['key' => 'ai_aggressive_low_match', 'value' => '30', 'type' => 'number', 'group' => 'ai', 'label' => 'Aggressive + Low Risk Match', 'description' => 'Match percentage: Aggressive user with low-risk plan'],
            ['key' => 'ai_aggressive_medium_match', 'value' => '70', 'type' => 'number', 'group' => 'ai', 'label' => 'Aggressive + Medium Risk Match', 'description' => 'Match percentage: Aggressive user with medium-risk plan'],
            ['key' => 'ai_aggressive_high_match', 'value' => '100', 'type' => 'number', 'group' => 'ai', 'label' => 'Aggressive + High Risk Match', 'description' => 'Match percentage: Aggressive user with high-risk plan'],
            ['key' => 'ai_default_risk_match', 'value' => '50', 'type' => 'number', 'group' => 'ai', 'label' => 'Default Risk Match', 'description' => 'Fallback match percentage if risk tolerance unknown'],

            // Investment Amount Calculation
            ['key' => 'ai_conservative_percentage', 'value' => '0.15', 'type' => 'number', 'group' => 'ai', 'label' => 'Conservative Investment %', 'description' => 'Suggested investment percentage of balance for conservative users (0-1)'],
            ['key' => 'ai_moderate_percentage', 'value' => '0.25', 'type' => 'number', 'group' => 'ai', 'label' => 'Moderate Investment %', 'description' => 'Suggested investment percentage of balance for moderate users (0-1)'],
            ['key' => 'ai_aggressive_percentage', 'value' => '0.40', 'type' => 'number', 'group' => 'ai', 'label' => 'Aggressive Investment %', 'description' => 'Suggested investment percentage of balance for aggressive users (0-1)'],

            // Reinvestment Settings
            ['key' => 'ai_min_reinvestment_balance', 'value' => '100', 'type' => 'number', 'group' => 'ai', 'label' => 'Min Reinvestment Balance', 'description' => 'Minimum balance required for reinvestment recommendations'],
            ['key' => 'ai_min_recent_earnings', 'value' => '50', 'type' => 'number', 'group' => 'ai', 'label' => 'Min Recent Earnings', 'description' => 'Minimum recent earnings required for reinvestment recommendations'],
            ['key' => 'ai_data_lookback_days', 'value' => '30', 'type' => 'number', 'group' => 'ai', 'label' => 'Data Lookback Days', 'description' => 'Number of days to analyze for earning patterns'],
            ['key' => 'ai_max_balance_percentage', 'value' => '0.50', 'type' => 'number', 'group' => 'ai', 'label' => 'Max Reinvestment Balance %', 'description' => 'Maximum percentage of balance to suggest for reinvestment (0-1)'],
            ['key' => 'ai_earnings_multiplier', 'value' => '2', 'type' => 'number', 'group' => 'ai', 'label' => 'Earnings Multiplier', 'description' => 'Multiply recent earnings by this for reinvestment calculation'],
            ['key' => 'ai_reinvestment_base_confidence', 'value' => '55', 'type' => 'number', 'group' => 'ai', 'label' => 'Reinvestment Base Confidence', 'description' => 'Starting confidence for reinvestment recommendations (0-100)'],
            ['key' => 'ai_earnings_threshold', 'value' => '100', 'type' => 'number', 'group' => 'ai', 'label' => 'Earnings Threshold', 'description' => 'Earnings amount required for earnings bonus'],
            ['key' => 'ai_earnings_bonus', 'value' => '15', 'type' => 'number', 'group' => 'ai', 'label' => 'Earnings Bonus', 'description' => 'Confidence bonus when earnings exceed threshold'],
            ['key' => 'ai_balance_threshold', 'value' => '500', 'type' => 'number', 'group' => 'ai', 'label' => 'Balance Threshold', 'description' => 'Balance amount required for balance bonus'],
            ['key' => 'ai_balance_bonus', 'value' => '10', 'type' => 'number', 'group' => 'ai', 'label' => 'Balance Bonus', 'description' => 'Confidence bonus when balance exceeds threshold'],
        ];

        $allSettings = array_merge($generalSettings, $emailSettings, $smsSettings, $seoSettings, $aiSettings,$securitySettings);
        foreach ($allSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $emailTemplates = [
            [
                'slug' => 'welcome',
                'name' => 'Welcome Email',
                'subject' => 'Welcome to {site_name}!',
                'body' => '<h1>Welcome {user_name}!</h1><p>Thank you for joining {site_name}. We are excited to have you on board.</p><p>Your account has been successfully created and you can now start investing.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name'],
                'is_active' => true
            ],
            [
                'slug' => 'email_verification',
                'name' => 'Email Verification',
                'subject' => 'Verify Your Email - {site_name}',
                'body' => '<h1>Email Verification</h1><p>Hi {user_name},</p><p>Please click the link below to verify your email address:</p><p><a href="{verification_link}">Verify Email</a></p><p>This link will expire in 24 hours.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'verification_link'],
                'is_active' => true
            ],
            [
                'slug' => 'password_reset',
                'name' => 'Password Reset',
                'subject' => 'Reset Your Password - {site_name}',
                'body' => '<h1>Password Reset Request</h1><p>Hi {user_name},</p><p>You have requested to reset your password. Click the link below to reset it:</p><p><a href="{reset_link}">Reset Password</a></p><p>If you did not request this, please ignore this email.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'reset_link'],
                'is_active' => true
            ],
            [
                'slug' => 'deposit_confirmation',
                'name' => 'Deposit Confirmation',
                'subject' => 'Deposit Confirmed - {site_name}',
                'body' => '<h1>Deposit Confirmed</h1><p>Hi {user_name},</p><p>Your deposit of {currency}{amount} has been successfully processed.</p><p>Transaction ID: {trx}</p><p>Your wallet balance has been updated.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'amount', 'currency', 'trx'],
                'is_active' => true
            ],
            [
                'slug' => 'withdrawal_request',
                'name' => 'Withdrawal Request',
                'subject' => 'Withdrawal Request Received - {site_name}',
                'body' => '<h1>Withdrawal Request Received</h1><p>Hi {user_name},</p><p>We have received your withdrawal request for {currency}{amount}.</p><p>Reference ID: {trx}</p><p>Your request is being processed and you will receive another notification once it has been completed.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'amount', 'currency', 'trx'],
                'is_active' => true
            ],
            [
                'slug' => 'withdrawal_approved',
                'name' => 'Withdrawal Approved',
                'subject' => 'Withdrawal Approved - {site_name}',
                'body' => '<h1>Withdrawal Approved</h1><p>Hi {user_name},</p><p>Great news! Your withdrawal request of {currency}{amount} has been approved and processed.</p><p>Transaction ID: {trx}</p><p>Please allow 1-3 business days for the funds to appear in your account.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'amount', 'currency', 'trx'],
                'is_active' => true
            ],
            [
                'slug' => 'withdrawal_rejected',
                'name' => 'Withdrawal Rejected',
                'subject' => 'Withdrawal Request Declined - {site_name}',
                'body' => '<h1>Withdrawal Request Declined</h1><p>Hi {user_name},</p><p>Unfortunately, your withdrawal request of {currency}{amount} has been declined.</p><p>Reference ID: {trx}</p><p>Reason: {admin_response}</p><p>Please contact our support team for more information.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'amount', 'currency', 'trx', 'admin_response'],
                'is_active' => true
            ],
            [
                'slug' => 'investment_approved',
                'name' => 'Investment Approved',
                'subject' => 'Investment Activated - {site_name}',
                'body' => '<h1>Investment Successfully Activated!</h1><p>Hi {user_name},</p><p>Your investment has been approved and activated.</p><p><strong>Investment Details:</strong></p><ul><li>Plan: {plan_name}</li><li>Amount: {currency}{amount}</li><li>Investment ID: {investment_id}</li><li>Expected Return: {currency}{expected_return}</li><li>Maturity Date: {maturity_date}</li></ul><p>You will start earning returns according to your plan schedule.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'plan_name', 'amount', 'currency', 'investment_id', 'expected_return', 'maturity_date'],
                'is_active' => true
            ],
            [
                'slug' => 'referral_bonus_earned',
                'name' => 'Referral Bonus Earned',
                'subject' => 'Congratulations! You Earned a Referral Bonus - {site_name}',
                'body' => '<h1>Referral Bonus Earned!</h1><p>Hi {user_name},</p><p>Congratulations! You have earned a referral bonus.</p><p><strong>Bonus Details:</strong></p><ul><li>Bonus Name: {bonus_name}</li><li>Description: {bonus_description}</li><li>Reward Amount: {currency}{reward_amount}</li><li>Bonus Type: {bonus_type}</li><li>Active Referrals: {active_referrals}</li><li>Earned Date: {earned_date}</li></ul><p>The bonus has been credited to your wallet and is ready to use.</p><p>Keep referring and earning more rewards!</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'bonus_name', 'bonus_description', 'reward_amount', 'currency', 'bonus_type', 'active_referrals', 'earned_date'],
                'is_active' => true
            ],
            [
                'slug' => 'referral_commission_earned',
                'name' => 'Referral Commission Earned',
                'subject' => 'Referral Commission Earned - {site_name}',
                'body' => '<h1>Referral Commission Earned!</h1><p>Hi {user_name},</p><p>Congratulations! You have earned a referral commission.</p><p><strong>Commission Details:</strong></p><ul><li>Commission Amount: {currency}{commission_amount}</li><li>Commission Type: {commission_type}</li><li>Referral Level: Level {level}</li><li>From User: {referred_user}</li><li>Base Amount: {currency}{base_amount}</li><li>Commission Rate: {commission_rate}%</li><li>Earned Date: {earned_date}</li></ul><p>The commission has been credited to your wallet and is ready to use.</p><p>Keep referring and earning more commissions!</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'commission_amount', 'currency', 'commission_type', 'level', 'referred_user', 'base_amount', 'commission_rate', 'commission_id', 'earned_date'],
                'is_active' => true
            ],
            [
                'slug' => 'staking_created',
                'name' => 'Staking Created',
                'subject' => 'Staking Activated - {site_name}',
                'body' => '<h1>Staking Successfully Activated!</h1><p>Hi {user_name},</p><p>Your staking has been successfully created and activated.</p><p><strong>Staking Details:</strong></p><ul><li>Pool: {pool_name}</li><li>Stake Amount: {currency}{stake_amount}</li><li>Stake ID: {stake_id}</li><li>APY Rate: {apy_rate}%</li><li>Lock Period: {lock_days} days</li><li>Staked At: {staked_at}</li><li>Unlock At: {unlock_at}</li><li>Auto-Compound: {auto_compound}</li></ul><p>You will start earning rewards according to the APY rate.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'pool_name', 'stake_amount', 'currency', 'stake_id', 'apy_rate', 'lock_days', 'staked_at', 'unlock_at', 'auto_compound'],
                'is_active' => true
            ],
            [
                'slug' => 'investment_completed',
                'name' => 'Investment Completed',
                'subject' => 'Investment Completed Successfully - {site_name}',
                'body' => '<h1>Investment Successfully Completed!</h1><p>Hi {user_name},</p><p>Congratulations! Your investment has been completed and all returns have been paid.</p><p><strong>Investment Details:</strong></p><ul><li>Plan: {plan_name}</li><li>Investment Amount: {currency}{amount}</li><li>Investment ID: {investment_id}</li><li>Total Returns Earned: {currency}{expected_return}</li><li>Maturity Date: {maturity_date}</li></ul><p>All returns and principal (if applicable) have been credited to your wallet.</p><p>Thank you for investing with us!</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'plan_name', 'amount', 'currency', 'investment_id', 'expected_return', 'maturity_date'],
                'is_active' => true
            ],
            [
                'slug' => 'kyc_approved',
                'name' => 'KYC Approved',
                'subject' => 'KYC Verification Approved - {site_name}',
                'body' => '<h1>KYC Verification Approved</h1><p>Hi {user_name},</p><p>Great news! Your KYC verification has been approved.</p><p>You now have full access to all platform features including higher withdrawal limits.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name'],
                'is_active' => true
            ],
            [
                'slug' => 'kyc_rejected',
                'name' => 'KYC Rejected',
                'subject' => 'KYC Verification Declined - {site_name}',
                'body' => '<h1>KYC Verification Declined</h1><p>Hi {user_name},</p><p>Unfortunately, your KYC verification has been declined.</p><p>Reason: {rejection_reason}</p><p>Please resubmit your documents or contact support for assistance.</p><p>Best regards,<br>The {site_name} Team</p>',
                'variables' => ['user_name', 'site_name', 'rejection_reason'],
                'is_active' => true
            ]
        ];

        foreach ($emailTemplates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $smsTemplates = [
            [
                'slug' => 'welcome',
                'name' => 'Welcome SMS',
                'message' => 'Welcome {user_name}! Thank you for joining {site_name}. Your account is ready and you can start investing now.',
                'variables' => ['user_name', 'site_name'],
                'is_active' => true
            ],
            [
                'slug' => 'email_verification',
                'name' => 'Email Verification SMS',
                'message' => 'Hi {user_name}, please verify your email for {site_name}: {verification_link} (Link expires in 24 hours)',
                'variables' => ['user_name', 'site_name', 'verification_link'],
                'is_active' => true
            ],
            [
                'slug' => 'password_reset',
                'name' => 'Password Reset SMS',
                'message' => 'Hi {user_name}, reset your {site_name} password: {reset_link} Ignore if you didn\'t request this.',
                'variables' => ['user_name', 'site_name', 'reset_link'],
                'is_active' => true
            ],
            [
                'slug' => 'deposit_confirmation',
                'name' => 'Deposit Confirmation SMS',
                'message' => 'Hi {user_name}, your {currency}{amount} deposit is confirmed. Transaction ID: {trx}. Wallet updated. - {site_name}',
                'variables' => ['user_name', 'site_name', 'amount', 'currency', 'trx'],
                'is_active' => true
            ],
            [
                'slug' => 'withdrawal_request',
                'name' => 'Withdrawal Request SMS',
                'message' => 'Hi {user_name}, we received your {currency}{amount} withdrawal request. Reference ID: {trx}. Processing in progress. - {site_name}',
                'variables' => ['user_name', 'site_name', 'amount', 'currency', 'trx'],
                'is_active' => true
            ],
            [
                'slug' => 'withdrawal_approved',
                'name' => 'Withdrawal Approved SMS',
                'message' => 'Hi {user_name}, your {currency}{amount} withdrawal is approved. Transaction ID: {trx}. Allow 1-3 business days. - {site_name}',
                'variables' => ['user_name', 'site_name', 'amount', 'currency', 'trx'],
                'is_active' => true
            ],
            [
                'slug' => 'withdrawal_rejected',
                'name' => 'Withdrawal Rejected SMS',
                'message' => 'Hi {user_name}, your {currency}{amount} withdrawal (ID: {trx}) was declined. Contact support for details. - {site_name}',
                'variables' => ['user_name', 'site_name', 'amount', 'currency', 'trx'],
                'is_active' => true
            ],
            [
                'slug' => 'investment_approved',
                'name' => 'Investment Approved SMS',
                'message' => 'Hi {user_name}, your investment is now active! Plan: {plan_name}, Amount: {currency}{amount}, ID: {investment_id}. Start earning now! - {site_name}',
                'variables' => ['user_name', 'site_name', 'plan_name', 'amount', 'currency', 'investment_id'],
                'is_active' => true
            ],
            [
                'slug' => 'investment_completed',
                'name' => 'Investment Completed SMS',
                'message' => 'Hi {user_name}, investment {investment_id} completed! Total earned: {currency}{expected_return}. Thank you for investing! - {site_name}',
                'variables' => ['user_name', 'site_name', 'investment_id', 'expected_return', 'currency'],
                'is_active' => true
            ],
            [
                'slug' => 'staking_created',
                'name' => 'Staking Created SMS',
                'message' => 'Hi {user_name}, your staking is active! Pool: {pool_name}, Amount: {currency}{stake_amount}, APY: {apy_rate}%, ID: {stake_id}. - {site_name}',
                'variables' => ['user_name', 'site_name', 'pool_name', 'stake_amount', 'currency', 'apy_rate', 'stake_id'],
                'is_active' => true
            ],
            [
                'slug' => 'referral_bonus_earned',
                'name' => 'Referral Bonus Earned SMS',
                'message' => 'Hi {user_name}, you earned {currency}{reward_amount} referral bonus! Bonus: {bonus_name}. Check your wallet! - {site_name}',
                'variables' => ['user_name', 'site_name', 'reward_amount', 'currency', 'bonus_name'],
                'is_active' => true
            ],
            [
                'slug' => 'referral_commission_earned',
                'name' => 'Referral Commission Earned SMS',
                'message' => 'Hi {user_name}, you earned {currency}{commission_amount} referral commission (Level {level})! Commission ID: {commission_id}. Check your wallet! - {site_name}',
                'variables' => ['user_name', 'site_name', 'commission_amount', 'currency', 'level', 'commission_id'],
                'is_active' => true
            ],
            [
                'slug' => 'kyc_approved',
                'name' => 'KYC Approved SMS',
                'message' => 'Hi {user_name}, your KYC verification is approved! Full access unlocked. - {site_name}',
                'variables' => ['user_name', 'site_name'],
                'is_active' => true
            ],
            [
                'slug' => 'kyc_rejected',
                'name' => 'KYC Rejected SMS',
                'message' => 'Hi {user_name}, your KYC verification was declined. Please resubmit documents or contact support. - {site_name}',
                'variables' => ['user_name', 'site_name'],
                'is_active' => true
            ]
        ];

        foreach ($smsTemplates as $template) {
            SmsTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('Settings seeder completed successfully!');
        $this->command->info('Created:');
        $this->command->info('- ' . count($allSettings) . ' system settings');
        $this->command->info('- ' . count($emailTemplates) . ' email templates');
        $this->command->info('- ' . count($smsTemplates) . ' SMS templates');
    }
}

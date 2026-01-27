<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\UploadedFile;
use App\Http\Controllers\Controller;
use App\Mail\GlobalMail;
use App\Models\Setting;
use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Twilio\Exceptions\TwilioException;

class SettingsController extends Controller
{
    use UploadedFile;

    public function __construct(protected readonly SmsService $smsService)
    {

    }


    /**
     * @return Response
     */
    /**
     * @return Response
     */
    public function index(): Response
    {
        $logo = Setting::get('site_logo', '');
        $favicon = Setting::get('site_favicon', '');
        $ogImage = Setting::get('og_image', '');

        $emailTemplates = EmailTemplate::all()->map(function($template) {
            return [
                'id' => $template->id,
                'slug' => $template->slug,
                'name' => $template->name,
                'subject' => $template->subject,
                'body' => $template->body,
                'variables' => is_array($template->variables) ? $template->variables : [],
                'is_active' => (bool) $template->is_active,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ];
        });

        $smsTemplates = SmsTemplate::all()->map(function($template) {
            return [
                'id' => $template->id,
                'slug' => $template->slug,
                'name' => $template->name,
                'message' => $template->message,
                'variables' => is_array($template->variables) ? $template->variables : [],
                'is_active' => (bool) $template->is_active,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ];
        });

        return Inertia::render('Admin/Settings/Index', [
            'generalSettings' => Setting::getByGroup('general'),
            'emailSettings' => Setting::getByGroup('email'),
            'smsSettings' => Setting::getByGroup('sms'),
            'securitySettings' => Setting::getByGroup('security'),
            'seoSettings' => Setting::getByGroup('seo'),
            'aiSettings' => Setting::getByGroup('ai'),
            'emailTemplates' => $emailTemplates,
            'smsTemplates' => $smsTemplates,
            'site_logo_url' => $logo ? $this->fullPath($logo) : null,
            'site_favicon_url' => $favicon ? $this->fullPath($favicon) : null,
            'seo_og_image_url' => $ogImage ? $this->fullPath($ogImage) : null,
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $rules = [
            'site_name' => 'required|string|max:255',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'site_favicon' => 'nullable|image|mimes:ico,png,gif|max:1024',
            'primary_color' => 'required|string',
            'default_currency' => 'required|string|max:3',
            'currency_symbol' => 'required|string|max:5',
            'default_timezone' => 'required|string|max:255',
            'tawk_property_id' => 'nullable|string|max:255',
            'tawk_widget_id' => 'nullable|string|max:255',
        ];

        Cache::forget('all_frontend_settings');
        $validated = $request->validate($rules);
        $validated['user_registration'] = $request->boolean('user_registration');
        $validated['site_logo'] = Setting::get('site_logo', '');
        $validated['site_favicon'] = Setting::get('site_favicon', '');

        if ($request->hasFile('site_logo')) {
            $logoFileName = Setting::get('site_logo', '');
            $logoPath = $this->move($request->file('site_logo'), null, $logoFileName);
            $validated['site_logo'] = $logoPath;
        }

        if ($request->hasFile('site_favicon')) {
            $faviconName = Setting::get('site_favicon', '');
            $faviconPath = $this->move($request->file('site_favicon'), null, $faviconName);
            $validated['site_favicon'] = $faviconPath;
        }

        foreach ($validated as $key => $value) {
            Setting::set(
                $key,
                $value,
                is_bool($value) ? 'boolean' : (in_array($key, ['site_logo', 'site_favicon']) ? 'file' : 'text'),
                ucwords(str_replace('_', ' ', $key)),
                'general'
            );
        }

        return back()->with('success', 'General settings updated successfully.');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateEmail(Request $request): RedirectResponse
    {
        $settings = $request->validate([
            'mail_driver' => 'required|in:smtp,mailgun,ses',
            'mail_host' => 'required_if:mail_driver,smtp|string',
            'mail_port' => 'required_if:mail_driver,smtp|integer',
            'mail_username' => 'required_if:mail_driver,smtp|string',
            'mail_password' => 'required_if:mail_driver,smtp|string',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        Cache::forget('all_frontend_settings');
        foreach ($settings as $key => $value) {
            Setting::set(
                $key,
                $value,
                'text',
                ucwords(str_replace('_', ' ', $key)),
                'email'
            );
        }

        return back()->with('success', 'Email settings updated successfully.');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSms(Request $request): RedirectResponse
    {
        $settings = $request->validate([
            'sms_driver' => 'required|in:twilio,nexmo,aws',
            'sms_api_key' => 'required|string',
            'sms_api_secret' => 'required|string',
            'sms_from' => 'required|string',
        ]);

        Cache::forget('all_frontend_settings');
        foreach ($settings as $key => $value) {
            Setting::set(
                $key,
                $value,
                'text',
                ucwords(str_replace('_', ' ', $key)),
                'sms'
            );
        }

        return back()->with('success', 'SMS settings updated successfully.');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSecurity(Request $request): RedirectResponse
    {
        $rules = [
            'login_attempts' => 'required|integer|min:3|max:10',
            'session_timeout' => 'required|integer|min:15|max:1440',
            'password_min_length' => 'required|integer|min:6|max:32',
        ];

        Cache::forget('all_frontend_settings');
        $validated = $request->validate($rules);
        $validated['two_factor_auth'] = $request->boolean('two_factor_auth');
        $validated['require_email_verification'] = $request->boolean('require_email_verification');

        foreach ($validated as $key => $value) {
            Setting::set(
                $key,
                $value,
                is_bool($value) ? 'boolean' : 'text',
                ucwords(str_replace('_', ' ', $key)),
                'security'
            );
        }

        return back()->with('success', 'Security settings updated successfully.');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSeo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string|max:255',
            'meta_keywords' => 'nullable|string',
            'og_title' => 'required|string|max:255',
            'og_description' => 'required|string|max:255',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'twitter_card' => 'required|string|max:255',
            'google_analytics' => 'nullable|string|max:255',
            'google_tag_manager' => 'nullable|string|max:255',
            'facebook_pixel' => 'nullable|string|max:255',
        ]);

        Cache::forget('all_frontend_settings');
        $validated['og_image'] = Setting::get('og_image', '');

        if ($request->hasFile('og_image')) {
            $ogImageName = Setting::get('og_image', '');
            $ogImagePath = $this->move($request->file('og_image'), null, $ogImageName);
            $validated['og_image'] = $ogImagePath;
        }

        foreach ($validated as $key => $value) {
            Setting::set(
                $key,
                $value,
                $key === 'og_image' ? 'file' : 'text',
                ucwords(str_replace('_', ' ', $key)),
                'seo'
            );
        }

        return back()->with('success', 'SEO settings updated successfully.');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateAi(Request $request): RedirectResponse
    {
        $rules = [
            'ai_max_recommendations_per_user' => 'required|integer|min:1|max:10',
            'ai_recommendation_cooldown_hours' => 'required|integer|min:1|max:168',
            'ai_max_plans_per_recommendation' => 'required|integer|min:1|max:10',

            'ai_confidence_threshold' => 'required|integer|min:0|max:100',
            'ai_high_priority_threshold' => 'required|integer|min:0|max:100',
            'ai_min_plan_confidence' => 'required|integer|min:0|max:100',
            'ai_base_confidence' => 'required|integer|min:0|max:100',

            'ai_risk_match_weight' => 'required|numeric|min:0',
            'ai_affordability_multiplier' => 'required|numeric|min:0',
            'ai_affordability_bonus' => 'required|numeric|min:0',
            'ai_performance_bonus' => 'required|numeric|min:0',
            'ai_featured_bonus' => 'required|numeric|min:0',
            'ai_success_rate_threshold' => 'required|integer|min:0|max:100',

            'ai_conservative_low_match' => 'required|integer|min:0|max:100',
            'ai_conservative_medium_match' => 'required|integer|min:0|max:100',
            'ai_conservative_high_match' => 'required|integer|min:0|max:100',

            'ai_moderate_low_match' => 'required|integer|min:0|max:100',
            'ai_moderate_medium_match' => 'required|integer|min:0|max:100',
            'ai_moderate_high_match' => 'required|integer|min:0|max:100',

            'ai_aggressive_low_match' => 'required|integer|min:0|max:100',
            'ai_aggressive_medium_match' => 'required|integer|min:0|max:100',
            'ai_aggressive_high_match' => 'required|integer|min:0|max:100',
            'ai_default_risk_match' => 'required|integer|min:0|max:100',

            'ai_conservative_percentage' => 'required|numeric|min:0|max:1',
            'ai_moderate_percentage' => 'required|numeric|min:0|max:1',
            'ai_aggressive_percentage' => 'required|numeric|min:0|max:1',

            'ai_min_reinvestment_balance' => 'required|numeric|min:0',
            'ai_min_recent_earnings' => 'required|numeric|min:0',
            'ai_data_lookback_days' => 'required|integer|min:1',
            'ai_max_balance_percentage' => 'required|numeric|min:0|max:1',
            'ai_earnings_multiplier' => 'required|numeric|min:0',
            'ai_reinvestment_base_confidence' => 'required|integer|min:0|max:100',
            'ai_earnings_threshold' => 'required|numeric|min:0',
            'ai_earnings_bonus' => 'required|numeric|min:0',
            'ai_balance_threshold' => 'required|numeric|min:0',
            'ai_balance_bonus' => 'required|numeric|min:0',
        ];

        Cache::forget('all_frontend_settings');
        $validated = $request->validate($rules);
        $validated['ai_enabled'] = $request->boolean('ai_enabled');

        foreach ($validated as $key => $value) {
            Setting::set(
                $key,
                $value,
                is_bool($value) ? 'boolean' : 'number',
                ucwords(str_replace('_', ' ', $key)),
                'ai'
            );
        }

        return back()->with('success', 'AI settings updated successfully.');
    }

    /**
     * @param Request $request
     * @param EmailTemplate $emailTemplate
     * @return RedirectResponse
     */
    public function updateEmailTemplate(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $emailTemplate->update($validated);
        return back()->with('success', 'Email template updated successfully.');
    }

    /**
     * @param Request $request
     * @param SmsTemplate $smsTemplate
     * @return RedirectResponse
     */
    public function updateSmsTemplate(Request $request, SmsTemplate $smsTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $smsTemplate->update($validated);
        return back()->with('success', 'SMS template updated successfully.');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function testEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'message' => 'required|max:255',
        ]);

        $user = User::where('role', 'admin')->first();
        Mail::to($request->input('email'))->send(new GlobalMail($user, 'Test Email', $request->input('message')));
        return back()->with('success', 'Test email sent successfully.');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws TwilioException
     */
    public function testSms(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $this->smsService->sendTestSms($request->input('phone'), $request->input('message'));
        return back()->with('success', 'Test SMS sent successfully.');
    }
}

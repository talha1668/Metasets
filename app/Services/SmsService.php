<?php

namespace App\Services;


use App\Models\Setting;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class SmsService
{
    protected Client $twilio;
    protected mixed $from;

    public function __construct()
    {
        $this->twilio = new Client(
            Setting::get('twilio_sid', ''),
            Setting::get('twilio_token', '')
        );
        $this->from = Setting::get('sms_from', '');
    }

    /**
     * @throws TwilioException
     */
    public function sendTemplateSms(string $templateSlug, User $user, array $variables = []): bool
    {
        $smsTemplate = SmsTemplate::where('slug', $templateSlug)->where('is_active', true)->first();
        if (!$smsTemplate) {
            Log::error("Email template not found: {$templateSlug}");
            return false;
        }

        $variables = array_merge([
            'site_name' => Setting::get('site_name', 'MineInvest'),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
            'contact_email' => Setting::get('contact_email', 'support@mineinvest.com'),
        ], $variables);

        $body = self::replaceVariables($smsTemplate->body, $variables);
        $this->twilio->messages->create(
            $user->phone,
            [
                'from' => $this->from,
                'body' => $body
            ]
        );
        return true;
    }

    /**
     * @param string $phoneNumber
     * @param string|null $message
     * @return bool
     * @throws TwilioException
     */
    public function sendTestSms(string $phoneNumber, string $message = null): bool
    {
        try {
            $defaultMessage = $message ?? "This is a test SMS from " . Setting::get('site_name', 'MineInvest') . ". Your SMS service is working correctly!";

            $this->twilio->messages->create(
                $phoneNumber,
                [
                    'from' => $this->from,
                    'body' => $defaultMessage
                ]
            );

            Log::info("Test SMS sent successfully to: {$phoneNumber}");
            return true;

        } catch (TwilioException $e) {
            Log::error("Failed to send test SMS to {$phoneNumber}: " . $e->getMessage());
            throw $e;
        }
    }

    private static function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }
}

<?php

namespace App\Http\Middleware;

use App\Concerns\UploadedFile;
use App\Models\Language;
use App\Models\Setting;
use App\Models\Wallet;
use App\Services\DefaultImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    use UploadedFile;

    private $settings = null;
    private $currentLanguage = null;

    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $this->loadSettings();

        $logo = $this->getSetting('site_logo', '');
        $favicon = $this->getSetting('site_favicon', '');
        $walletData = null;
        if ($user) {
            $wallet = Wallet::where('user_id', $user->id)->first();
            $walletData = [
                'balance' => $wallet ? floatval($wallet->balance) : 0,
                'bonus_balance' => $wallet ? floatval($wallet->bonus_balance) : 0,
                'total_invested' => $wallet ? floatval($wallet->total_invested) : 0,
                'total_earnings' => $wallet ? floatval($wallet->total_earnings) : 0,
            ];
        }

        return array_merge(parent::share($request), [
            'csrf_token' => csrf_token(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'uid' => $user->uid,
                    'role' => $user->role,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'avatar_url' => $user->avatar ? asset('assets/files/'.$user->avatar) : null,
                    'initials' => $this->getUserInitials($user->name),
                    'wallet_address' => $user->wallet_address ?? null,
                    'wallet_type' => $user->wallet_type ?? null,
                    'wallet_connected' => !empty($user->wallet_address),
                ] : null,
            ],
            'wallet' => $walletData,
            'currencySymbol' => $this->getSetting('currency_symbol', '$'),
            'currencyName' => $this->getSetting('default_currency', 'USD'),
            'appName' => $this->getSetting('site_name', 'Experts-Trade'),
            'primaryColor' => $this->getSetting('primary_color', '#1f2937'),
            'logoUrl' => $logo ? $this->fullPath($logo) : DefaultImageService::getImageUrl(null, 'logo', 200, 80),
            'faviconUrl' => $favicon ? $this->fullPath($favicon) : DefaultImageService::getImageUrl(null, 'favicon', 16, 16),
            'languages' => $this->getLanguagesData(),
            'currentLanguage' => $this->getCurrentLanguage($request),
            'translations' => $this->getCurrentTranslations($request),
            'seo' => $this->getSeoData(),
            'tawk' => [
                'property_id' => $this->getSetting('tawk_property_id', ''),
                'widget_id' => $this->getSetting('tawk_widget_id', ''),
            ],
        ]);
    }

    private function loadSettings(): void
    {
        if ($this->settings === null) {
            $this->settings = Cache::remember('all_frontend_settings', 600, function () {
                return Setting::pluck('value', 'key')->toArray();
            });
        }
    }

    private function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    private function getUserInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
        } elseif (count($words) === 1) {
            $firstWord = $words[0];
            if (strlen($firstWord) >= 2) {
                return strtoupper(substr($firstWord, 0, 2));
            } else {
                return strtoupper(str_repeat(substr($firstWord, 0, 1), 2));
            }
        }
        return 'AD';
    }

    private function getSeoData(): array
    {
        $ogImage = $this->getSetting('og_image', '');
        return [
            'meta_title' => $this->getSetting('meta_title', 'Experts-Trade - Professional Web3 Finance Platform'),
            'meta_description' => $this->getSetting('meta_description', 'Professional Web3 finance platform with secure wallet integration, DeFi protocols, and advanced trading strategies.'),
            'meta_keywords' => $this->getSetting('meta_keywords', 'web3, defi, cryptocurrency, wallet, trading, finance'),
            'og_title' => $this->getSetting('og_title', 'Experts-Trade - Professional Web3 Finance'),
            'og_description' => $this->getSetting('og_description', 'Connect your Web3 wallet and access professional DeFi investment strategies.'),
            'og_image_url' => $ogImage ? $this->fullPath($ogImage) : null,
            'google_analytics' => $this->getSetting('google_analytics', ''),
        ];
    }

    private function getLanguagesData(): array
    {
        return Cache::remember('frontend_languages', 3600, function () {
            return Language::where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get(['code', 'name', 'native_name', 'flag', 'is_default', 'is_active'])
                ->map(function ($lang) {
                    return [
                        'code' => $lang->code,
                        'name' => $lang->name,
                        'nativeName' => $lang->native_name,
                        'flag' => $lang->flag,
                        'is_default' => $lang->is_default,
                        'is_active' => $lang->is_active,
                    ];
                })->toArray();
        });
    }

    private function getCurrentLanguage(Request $request): array
    {
        if ($this->currentLanguage === null) {
            $languageCode = session('language', '');
            $language = Language::where('code', $languageCode)
                ->where('is_active', true)
                ->first(['code', 'name', 'native_name', 'flag']);

            if (!$language) {
                $language = Language::where('is_default', true)
                    ->first(['code', 'name', 'native_name', 'flag']);
            }

            $this->currentLanguage = [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'flag' => $language->flag,
            ];
        }
        return $this->currentLanguage;
    }

    private function getCurrentTranslations(Request $request): array
    {
        $currentLanguage = $this->getCurrentLanguage($request);
        $languageCode = $currentLanguage['code'];
        $cacheKey = "translations_{$languageCode}";

        return Cache::remember($cacheKey, 3600, function () use ($languageCode) {
            $language = Language::where('code', $languageCode)->first();
            if (!$language) return [];

            $translations = $language->getTranslationsArray();
            if (empty($translations)) {
                $filePath = resource_path("js/lang/{$languageCode}.json");
                if (file_exists($filePath)) {
                    $jsonContent = file_get_contents($filePath);
                    $fileTranslations = json_decode($jsonContent, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($fileTranslations)) {
                        return $fileTranslations;
                    }
                }
            }
            return $translations;
        });
    }
}

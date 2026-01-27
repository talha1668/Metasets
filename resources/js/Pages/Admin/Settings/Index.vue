<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue"
import { useToast } from "@/composables/useToast.js"

const props = defineProps({
    generalSettings: {type: Object, default: () => ({})},
    emailSettings: {type: Object, default: () => ({})},
    smsSettings: {type: Object, default: () => ({})},
    securitySettings: {type: Object, default: () => ({})},
    seoSettings: {type: Object, default: () => ({})},
    aiSettings: {type: Object, default: () => ({})},
    emailTemplates: {type: Array, default: () => []},
    smsTemplates: {type: Array, default: () => []},
    site_logo_url: {type: String, default: null},
    site_favicon_url: {type: String, default: null},
    seo_og_image_url: {type: String, default: null}
})

const pageProps = usePage()
const {success, error, info} = useToast()
const activeTab = ref('general')

const tabs = [
    {
        key: 'general',
        name: 'General',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>'
    },
    {
        key: 'email',
        name: 'Email',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>'
    },
    {
        key: 'sms',
        name: 'SMS',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>'
    },
    {
        key: 'security',
        name: 'Security',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>'
    },
    {
        key: 'seo',
        name: 'SEO',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>'
    },
    {
        key: 'ai',
        name: 'AI Settings',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>'
    }
]

// Loading states
const loadingStates = reactive({
    general: false,
    email: false,
    sms: false,
    security: false,
    seo: false,
    ai: false,
    template: false,
    smsTemplate: false,
    testEmail: false,
    testSms: false
})

// Error states
const errors = reactive({
    general: {},
    email: {},
    sms: {},
    security: {},
    seo: {},
    ai: {},
    template: {},
    smsTemplate: {}
})

// Modal states
const modals = reactive({
    showEmail: false,
    showSms: false,
    showTestEmail: false,
    showTestSms: false
})

const editingEmailTemplate = ref({})
const editingSmsTemplate = ref({})
const emailTemplates = ref([...props.emailTemplates])
const smsTemplates = ref([...props.smsTemplates])
const flash = ref(pageProps.props.flash)

// Test forms
const testEmailForm = reactive({
    email: '',
    message: 'This is a test email from your application settings.'
})

const testSmsForm = reactive({
    phone: '',
    message: 'This is a test SMS from your application settings.'
})

// Main forms
const generalForm = reactive({
    site_name: props.generalSettings.site_name || 'MetaStak',
    site_logo: null,
    site_favicon: null,
    primary_color: props.generalSettings.primary_color || '#86efac',
    user_registration: props.generalSettings.user_registration !== false,
    default_currency: props.generalSettings.default_currency || 'USD',
    currency_symbol: props.generalSettings.currency_symbol || '$',
    default_timezone: props.generalSettings.default_timezone || 'America/New_York',
    tawk_property_id: props.generalSettings.tawk_property_id || '',
    tawk_widget_id: props.generalSettings.tawk_widget_id || ''
})

const emailForm = reactive({
    mail_driver: props.emailSettings.mail_driver || 'smtp',
    mail_host: props.emailSettings.mail_host || '',
    mail_port: props.emailSettings.mail_port || 587,
    mail_username: props.emailSettings.mail_username || '',
    mail_password: props.emailSettings.mail_password || '',
    mail_encryption: props.emailSettings.mail_encryption || 'tls',
    mail_from_address: props.emailSettings.mail_from_address || '',
    mail_from_name: props.emailSettings.mail_from_name || ''
})

const smsForm = reactive({
    sms_driver: props.smsSettings.sms_driver || 'twilio',
    sms_api_key: props.smsSettings.sms_api_key || '',
    sms_api_secret: props.smsSettings.sms_api_secret || '',
    sms_from: props.smsSettings.sms_from || ''
})

const securityForm = reactive({
    two_factor_auth: Boolean(props.securitySettings.two_factor_auth),
    login_attempts: parseInt(props.securitySettings.login_attempts) || 5,
    session_timeout: parseInt(props.securitySettings.session_timeout) || 60,
    password_min_length: parseInt(props.securitySettings.password_min_length) || 8,
    require_email_verification: Boolean(props.securitySettings.require_email_verification)
})

const seoForm = reactive({
    meta_title: props.seoSettings.meta_title || '',
    meta_description: props.seoSettings.meta_description || '',
    meta_keywords: props.seoSettings.meta_keywords || '',
    og_title: props.seoSettings.og_title || '',
    og_description: props.seoSettings.og_description || '',
    og_image: null,
    twitter_card: props.seoSettings.twitter_card || 'summary_large_image',
    google_analytics: props.seoSettings.google_analytics || '',
    google_tag_manager: props.seoSettings.google_tag_manager || '',
    facebook_pixel: props.seoSettings.facebook_pixel || ''
})

// AI Form with dynamic initialization
const aiForm = reactive(
    Object.keys(props.aiSettings).length > 0
        ? Object.keys(props.aiSettings).reduce((acc, key) => {
            const value = props.aiSettings[key]
            if (key === 'ai_enabled') {
                acc[key] = value !== false
            } else if (key.includes('percentage') || key.includes('multiplier')) {
                acc[key] = parseFloat(value) || 0
            } else {
                acc[key] = parseInt(value) || 0
            }
            return acc
        }, {})
        : {
            ai_enabled: true,
            ai_max_recommendations_per_user: 3,
            ai_recommendation_cooldown_hours: 24,
            ai_max_plans_per_recommendation: 3,
            ai_confidence_threshold: 60,
            ai_high_priority_threshold: 80,
            ai_min_plan_confidence: 50,
            ai_base_confidence: 50,
            ai_risk_match_weight: 30,
            ai_affordability_multiplier: 2,
            ai_affordability_bonus: 10,
            ai_performance_bonus: 15,
            ai_featured_bonus: 5,
            ai_success_rate_threshold: 75,
            ai_conservative_low_match: 100,
            ai_conservative_medium_match: 60,
            ai_conservative_high_match: 20,
            ai_moderate_low_match: 70,
            ai_moderate_medium_match: 100,
            ai_moderate_high_match: 70,
            ai_aggressive_low_match: 30,
            ai_aggressive_medium_match: 70,
            ai_aggressive_high_match: 100,
            ai_default_risk_match: 50,
            ai_conservative_percentage: 0.15,
            ai_moderate_percentage: 0.25,
            ai_aggressive_percentage: 0.40,
            ai_min_reinvestment_balance: 100,
            ai_min_recent_earnings: 50,
            ai_data_lookback_days: 30,
            ai_max_balance_percentage: 0.50,
            ai_earnings_multiplier: 2,
            ai_reinvestment_base_confidence: 55,
            ai_earnings_threshold: 100,
            ai_earnings_bonus: 15,
            ai_balance_threshold: 500,
            ai_balance_bonus: 10
        }
)

// General Settings Configuration
const generalSettingsConfig = [
    {
        title: 'Basic Information',
        fields: [
            {
                key: 'site_name',
                label: 'Site Name',
                type: 'text',
                required: true,
                description: 'The name of your platform'
            },
            {
                key: 'primary_color',
                label: 'Primary Color',
                type: 'color',
                required: true,
                description: 'Primary color for the theme'
            },
            {
                key: 'site_logo',
                label: 'Site Logo',
                type: 'file',
                accept: 'image/*',
                currentFile: props.site_logo_url,
                description: 'Recommended: PNG, JPG, SVG. Max size: 2MB'
            },
            {
                key: 'site_favicon',
                label: 'Site Favicon',
                type: 'file',
                accept: '.ico,.png,.gif',
                currentFile: props.site_favicon_url,
                description: 'Recommended: ICO, PNG. Size: 16x16 or 32x32 pixels. Max size: 1MB'
            }
        ]
    },
    {
        title: 'Currency & Localization',
        fields: [
            {
                key: 'default_currency',
                label: 'Default Currency',
                type: 'text',
                maxlength: 3,
                placeholder: 'USD',
                description: 'Currency code (e.g., USD, EUR, GBP)'
            },
            {
                key: 'currency_symbol',
                label: 'Currency Symbol',
                type: 'text',
                maxlength: 5,
                placeholder: '$',
                description: 'Currency symbol for display'
            },
            {
                key: 'default_timezone',
                label: 'Default Timezone',
                type: 'select',
                options: [
                    {value: 'America/New_York', label: 'America/New_York (EST/EDT)'},
                    {value: 'America/Los_Angeles', label: 'America/Los_Angeles (PST/PDT)'},
                    {value: 'Europe/London', label: 'Europe/London (GMT/BST)'},
                    {value: 'Europe/Paris', label: 'Europe/Paris (CET/CEST)'},
                    {value: 'Asia/Tokyo', label: 'Asia/Tokyo (JST)'},
                    {value: 'Asia/Shanghai', label: 'Asia/Shanghai (CST)'},
                    {value: 'Asia/Dhaka', label: 'Asia/Dhaka (BST)'},
                    {value: 'UTC', label: 'UTC'}
                ],
                description: 'Default platform timezone'
            }
        ]
    },
    {
        title: 'Chat Integration',
        fields: [
            {
                key: 'tawk_property_id',
                label: 'Tawk.to Property ID',
                type: 'text',
                description: 'Tawk.to Property ID for chat widget'
            },
            {
                key: 'tawk_widget_id',
                label: 'Tawk.to Widget ID',
                type: 'text',
                description: 'Tawk.to Widget ID for chat widget'
            }
        ]
    },
    {
        title: 'System Settings',
        fields: [
            {
                key: 'user_registration',
                label: 'Allow User Registration',
                type: 'checkbox',
                description: 'Allow new users to register on the platform'
            }
        ]
    }
]

// Email Settings Configuration
const emailSettingsConfig = [
    {
        title: 'Email Configuration',
        fields: [
            {
                key: 'mail_driver',
                label: 'Mail Driver',
                type: 'select',
                options: [
                    {value: 'smtp', label: 'SMTP'},
                    {value: 'mailgun', label: 'Mailgun'},
                    {value: 'ses', label: 'AWS SES'}
                ]
            },
            {
                key: 'mail_host',
                label: 'SMTP Host',
                type: 'text',
                placeholder: 'smtp.gmail.com',
                showIf: {mail_driver: 'smtp'}
            },
            {key: 'mail_port', label: 'SMTP Port', type: 'number', placeholder: '587', showIf: {mail_driver: 'smtp'}},
            {key: 'mail_username', label: 'Username', type: 'text', showIf: {mail_driver: 'smtp'}},
            {key: 'mail_password', label: 'Password', type: 'password', showIf: {mail_driver: 'smtp'}},
            {
                key: 'mail_encryption',
                label: 'Encryption',
                type: 'select',
                options: [
                    {value: 'tls', label: 'TLS'},
                    {value: 'ssl', label: 'SSL'},
                    {value: '', label: 'None'}
                ]
            },
            {key: 'mail_from_address', label: 'From Email', type: 'email', required: true},
            {key: 'mail_from_name', label: 'From Name', type: 'text', required: true}
        ]
    }
]

// SMS Settings Configuration
const smsSettingsConfig = [
    {
        title: 'SMS Configuration',
        fields: [
            {key: 'sms_driver', label: 'SMS Provider', type: 'text', placeholder: 'twilio'},
            {key: 'sms_api_key', label: 'API Key', type: 'text'},
            {key: 'sms_api_secret', label: 'API Secret', type: 'password'},
            {key: 'sms_from', label: 'From Number', type: 'text', placeholder: '+1234567890'}
        ]
    }
]

// Security Settings Configuration
const securitySettingsConfig = [
    {
        title: 'Security Configuration',
        fields: [
            {key: 'login_attempts', label: 'Max Login Attempts', type: 'number', min: 3, max: 10},
            {key: 'session_timeout', label: 'Session Timeout (minutes)', type: 'number', min: 15, max: 1440},
            {key: 'password_min_length', label: 'Min Password Length', type: 'number', min: 6, max: 32},
            {key: 'two_factor_auth', label: 'Enable 2FA', type: 'checkbox'},
            {key: 'require_email_verification', label: 'Require Email Verification', type: 'checkbox'}
        ]
    }
]

// SEO Settings Configuration
const seoSettingsConfig = [
    {
        title: 'Meta Tags',
        fields: [
            {
                key: 'meta_title',
                label: 'Meta Title',
                type: 'text',
                required: true,
                description: 'SEO meta title for homepage'
            },
            {
                key: 'meta_description',
                label: 'Meta Description',
                type: 'textarea',
                rows: 3,
                required: true,
                description: 'Keep under 160 characters for best SEO results'
            },
            {
                key: 'meta_keywords',
                label: 'Meta Keywords',
                type: 'textarea',
                rows: 2,
                description: 'Comma separated keywords (optional)'
            }
        ]
    },
    {
        title: 'Social Media',
        fields: [
            {
                key: 'og_title',
                label: 'Open Graph Title',
                type: 'text',
                required: true,
                description: 'Social media title'
            },
            {
                key: 'og_description',
                label: 'Open Graph Description',
                type: 'textarea',
                rows: 3,
                required: true,
                description: 'Description for social media sharing'
            },
            {
                key: 'og_image',
                label: 'Open Graph Image',
                type: 'file',
                accept: 'image/*',
                currentFile: props.seo_og_image_url,
                description: 'Recommended: 1200x630 pixels. JPG, PNG. Max size: 2MB'
            },
            {
                key: 'twitter_card',
                label: 'Twitter Card Type',
                type: 'select',
                options: [
                    {value: 'summary', label: 'Summary'},
                    {value: 'summary_large_image', label: 'Summary Large Image'},
                    {value: 'app', label: 'App'},
                    {value: 'player', label: 'Player'}
                ]
            }
        ]
    },
    {
        title: 'Analytics & Tracking',
        gridCols: 3,
        fields: [
            {key: 'google_analytics', label: 'Google Analytics ID', type: 'text', placeholder: 'GA4-XXXXXXXXX'},
            {key: 'google_tag_manager', label: 'Google Tag Manager ID', type: 'text', placeholder: 'GTM-XXXXXXX'},
            {key: 'facebook_pixel', label: 'Facebook Pixel ID', type: 'text', placeholder: '123456789012345'}
        ]
    }
]

// AI Settings Configuration
const aiSettingsConfig = [
    {
        title: 'General Controls',
        fields: [
            {
                key: 'ai_enabled',
                label: 'Enable AI Recommendations',
                type: 'checkbox',
                description: 'Turn AI-powered investment recommendations on or off',
                colSpan: 'full'
            },
            {
                key: 'ai_max_recommendations_per_user',
                label: 'Max Recommendations Per User',
                type: 'number',
                min: 1,
                max: 10,
                description: 'Maximum active recommendations each user can have'
            },
            {
                key: 'ai_recommendation_cooldown_hours',
                label: 'Recommendation Cooldown (Hours)',
                type: 'number',
                min: 1,
                max: 168,
                description: 'Hours to wait before generating new recommendations'
            },
            {
                key: 'ai_max_plans_per_recommendation',
                label: 'Max Plans Per Recommendation',
                type: 'number',
                min: 1,
                max: 10,
                description: 'Maximum investment plans to recommend at once'
            }
        ]
    },
    {
        title: 'Confidence & Thresholds',
        fields: [
            {
                key: 'ai_confidence_threshold',
                label: 'Confidence Threshold (0-100)',
                type: 'number',
                min: 0,
                max: 100,
                description: 'Minimum confidence score to create recommendation'
            },
            {
                key: 'ai_high_priority_threshold',
                label: 'High Priority Threshold (0-100)',
                type: 'number',
                min: 0,
                max: 100,
                description: 'Confidence score to mark recommendation as high priority'
            },
            {
                key: 'ai_min_plan_confidence',
                label: 'Min Plan Confidence (0-100)',
                type: 'number',
                min: 0,
                max: 100,
                description: 'Minimum confidence score for a plan to be considered'
            },
            {
                key: 'ai_base_confidence',
                label: 'Base Confidence Score (0-100)',
                type: 'number',
                min: 0,
                max: 100,
                description: 'Starting confidence score before adjustments'
            }
        ]
    },
    {
        title: 'Scoring Weights & Bonuses',
        gridCols: 3,
        fields: [
            {key: 'ai_risk_match_weight', label: 'Risk Match Weight', type: 'number', min: 0},
            {key: 'ai_affordability_multiplier', label: 'Affordability Multiplier', type: 'number', step: 0.1, min: 0},
            {key: 'ai_affordability_bonus', label: 'Affordability Bonus', type: 'number', min: 0},
            {key: 'ai_performance_bonus', label: 'Performance Bonus', type: 'number', min: 0},
            {key: 'ai_featured_bonus', label: 'Featured Plan Bonus', type: 'number', min: 0},
            {key: 'ai_success_rate_threshold', label: 'Success Rate Threshold (%)', type: 'number', min: 0, max: 100}
        ]
    },
    {
        title: 'Risk Matching - Conservative User',
        gridCols: 3,
        fields: [
            {key: 'ai_conservative_low_match', label: 'Low Risk Match (%)', type: 'number', min: 0, max: 100},
            {key: 'ai_conservative_medium_match', label: 'Medium Risk Match (%)', type: 'number', min: 0, max: 100},
            {key: 'ai_conservative_high_match', label: 'High Risk Match (%)', type: 'number', min: 0, max: 100}
        ]
    },
    {
        title: 'Risk Matching - Moderate User',
        gridCols: 3,
        fields: [
            {key: 'ai_moderate_low_match', label: 'Low Risk Match (%)', type: 'number', min: 0, max: 100},
            {key: 'ai_moderate_medium_match', label: 'Medium Risk Match (%)', type: 'number', min: 0, max: 100},
            {key: 'ai_moderate_high_match', label: 'High Risk Match (%)', type: 'number', min: 0, max: 100}
        ]
    },
    {
        title: 'Risk Matching - Aggressive User',
        gridCols: 3,
        fields: [
            {key: 'ai_aggressive_low_match', label: 'Low Risk Match (%)', type: 'number', min: 0, max: 100},
            {key: 'ai_aggressive_medium_match', label: 'Medium Risk Match (%)', type: 'number', min: 0, max: 100},
            {key: 'ai_aggressive_high_match', label: 'High Risk Match (%)', type: 'number', min: 0, max: 100},
            {key: 'ai_default_risk_match', label: 'Default Risk Match (%)', type: 'number', min: 0, max: 100}
        ]
    },
    {
        title: 'Investment Amount Calculation',
        gridCols: 3,
        fields: [
            {
                key: 'ai_conservative_percentage',
                label: 'Conservative Investment %',
                type: 'number',
                step: 0.01,
                min: 0,
                max: 1,
                description: 'Investment percentage for conservative users (0-1)'
            },
            {
                key: 'ai_moderate_percentage',
                label: 'Moderate Investment %',
                type: 'number',
                step: 0.01,
                min: 0,
                max: 1,
                description: 'Investment percentage for moderate users (0-1)'
            },
            {
                key: 'ai_aggressive_percentage',
                label: 'Aggressive Investment %',
                type: 'number',
                step: 0.01,
                min: 0,
                max: 1,
                description: 'Investment percentage for aggressive users (0-1)'
            }
        ]
    },
    {
        title: 'Reinvestment Settings',
        gridCols: 3,
        fields: [
            {
                key: 'ai_min_reinvestment_balance',
                label: 'Min Reinvestment Balance',
                type: 'number',
                min: 0,
                description: 'Minimum balance required for reinvestment recommendations'
            },
            {
                key: 'ai_min_recent_earnings',
                label: 'Min Recent Earnings',
                type: 'number',
                min: 0,
                description: 'Minimum recent earnings required for reinvestment'
            },
            {
                key: 'ai_data_lookback_days',
                label: 'Data Lookback Days',
                type: 'number',
                min: 1,
                description: 'Number of days to analyze for earning patterns'
            },
            {
                key: 'ai_max_balance_percentage',
                label: 'Max Reinvestment Balance %',
                type: 'number',
                step: 0.01,
                min: 0,
                max: 1,
                description: 'Maximum percentage of balance to suggest (0-1)'
            },
            {
                key: 'ai_earnings_multiplier',
                label: 'Earnings Multiplier',
                type: 'number',
                step: 0.1,
                min: 0,
                description: 'Multiply recent earnings by this for calculation'
            },
            {
                key: 'ai_reinvestment_base_confidence',
                label: 'Reinvestment Base Confidence',
                type: 'number',
                min: 0,
                max: 100,
                description: 'Starting confidence for reinvestment (0-100)'
            },
            {
                key: 'ai_earnings_threshold',
                label: 'Earnings Threshold',
                type: 'number',
                min: 0,
                description: 'Earnings amount required for earnings bonus'
            },
            {
                key: 'ai_earnings_bonus',
                label: 'Earnings Bonus',
                type: 'number',
                min: 0,
                description: 'Confidence bonus when earnings exceed threshold'
            },
            {
                key: 'ai_balance_threshold',
                label: 'Balance Threshold',
                type: 'number',
                min: 0,
                description: 'Balance amount required for balance bonus'
            },
            {
                key: 'ai_balance_bonus',
                label: 'Balance Bonus',
                type: 'number',
                min: 0,
                description: 'Confidence bonus when balance exceeds threshold'
            }
        ]
    }
]

// Utility functions
const getErrorMessage = (err) => Array.isArray(err) ? err[0] : err
const clearErrors = (errorKey) => {
    errors[errorKey] = {}
}

// Check if field should be shown based on showIf condition
const shouldShowField = (field, form) => {
    if (!field.showIf) return true
    const [key, value] = Object.entries(field.showIf)[0]
    return form[key] === value
}

// File handlers
const handleFileChange = (event, form, key) => {
    form[key] = event.target.files[0] || null
}

// Update functions
const updateSettings = (type, form, endpoint) => {
    loadingStates[type] = true
    clearErrors(type)

    const isFormData = ['general', 'seo'].includes(type)
    let data = form

    if (isFormData) {
        data = new FormData()
        Object.keys(form).forEach(key => {
            if (form[key] !== null) {
                data.append(key, form[key])
            }
        })
        data.append('_method', 'PATCH')
    }

    const method = isFormData ? 'post' : 'patch'

    router[method](endpoint, data, {
        onSuccess: () => {
            loadingStates[type] = false
            success(`${type.charAt(0).toUpperCase() + type.slice(1)} settings updated successfully`)
        },
        onError: (errs) => {
            loadingStates[type] = false
            errors[type] = errs

            if (errs.error) {
                error(Array.isArray(errs.error) ? errs.error[0] : errs.error)
            } else {
                error(`Failed to update ${type} settings`)
            }
        }
    })
}

const updateGeneral = () => updateSettings('general', generalForm, '/admin/settings/general')
const updateEmail = () => updateSettings('email', emailForm, '/admin/settings/email')
const updateSms = () => updateSettings('sms', smsForm, '/admin/settings/sms')
const updateSecurity = () => updateSettings('security', securityForm, '/admin/settings/security')
const updateSeo = () => updateSettings('seo', seoForm, '/admin/settings/seo')
const updateAi = () => updateSettings('ai', aiForm, '/admin/settings/ai')

// Test email/SMS functions
const testEmail = () => {
    testEmailForm.email = ''
    testEmailForm.message = 'This is a test email from your application settings.'
    modals.showTestEmail = true
}

const closeTestEmailModal = () => {
    modals.showTestEmail = false
    testEmailForm.email = ''
    testEmailForm.message = 'This is a test email from your application settings.'
}

const sendTestEmail = () => {
    loadingStates.testEmail = true
    router.post('/admin/settings/test-email', testEmailForm, {
        onSuccess: () => {
            loadingStates.testEmail = false
            closeTestEmailModal()
            success('Test email sent successfully')
        },
        onError: () => {
            loadingStates.testEmail = false
            error('Failed to send test email')
        }
    })
}

const testSms = () => {
    testSmsForm.phone = ''
    testSmsForm.message = 'This is a test SMS from your application settings.'
    modals.showTestSms = true
}

const closeTestSmsModal = () => {
    modals.showTestSms = false
    testSmsForm.phone = ''
    testSmsForm.message = 'This is a test SMS from your application settings.'
}

const sendTestSms = () => {
    loadingStates.testSms = true
    router.post('/admin/settings/test-sms', testSmsForm, {
        onSuccess: () => {
            loadingStates.testSms = false
            closeTestSmsModal()
            success('Test SMS sent successfully')
        },
        onError: () => {
            loadingStates.testSms = false
            error('Failed to send test SMS')
        }
    })
}

// Template functions
const editEmailTemplate = (template) => {
    editingEmailTemplate.value = {...template, is_active: Boolean(template.is_active)}
    modals.showEmail = true
    clearErrors('template')
}

const closeEmailModal = () => {
    modals.showEmail = false
    editingEmailTemplate.value = {}
    clearErrors('template')
}

const saveEmailTemplate = () => {
    if (!editingEmailTemplate.value.id) {
        error('Template ID is missing')
        return
    }

    loadingStates.template = true
    clearErrors('template')

    const templateData = {
        name: editingEmailTemplate.value.name,
        subject: editingEmailTemplate.value.subject,
        body: editingEmailTemplate.value.body,
        is_active: editingEmailTemplate.value.is_active
    }

    router.patch(`/admin/settings/email-template/${editingEmailTemplate.value.id}`, templateData, {
        onSuccess: () => {
            loadingStates.template = false
            const index = emailTemplates.value.findIndex(t => t.id === editingEmailTemplate.value.id)
            if (index !== -1) {
                emailTemplates.value[index] = {...emailTemplates.value[index], ...templateData}
            }
            closeEmailModal()
            success('Email template updated successfully')
        },
        onError: (errs) => {
            loadingStates.template = false
            errors.template = errs
            error('Failed to update email template')
        }
    })
}

const editSmsTemplate = (template) => {
    editingSmsTemplate.value = {...template, is_active: Boolean(template.is_active)}
    modals.showSms = true
    clearErrors('smsTemplate')
}

const closeSmsModal = () => {
    modals.showSms = false
    editingSmsTemplate.value = {}
    clearErrors('smsTemplate')
}

const saveSmsTemplate = () => {
    if (!editingSmsTemplate.value.id) {
        error('Template ID is missing')
        return
    }

    loadingStates.smsTemplate = true
    clearErrors('smsTemplate')

    const templateData = {
        name: editingSmsTemplate.value.name,
        message: editingSmsTemplate.value.message,
        is_active: editingSmsTemplate.value.is_active
    }

    router.patch(`/admin/settings/sms-template/${editingSmsTemplate.value.id}`, templateData, {
        onSuccess: () => {
            loadingStates.smsTemplate = false
            const index = smsTemplates.value.findIndex(t => t.id === editingSmsTemplate.value.id)
            if (index !== -1) {
                smsTemplates.value[index] = {...smsTemplates.value[index], ...templateData}
            }
            closeSmsModal()
            success('SMS template updated successfully')
        },
        onError: (errs) => {
            loadingStates.smsTemplate = false
            errors.smsTemplate = errs
            error('Failed to update SMS template')
        }
    })
}

onMounted(() => {
    if (flash.value?.success) success(flash.value.success)
    else if (flash.value?.error) error(flash.value.error)
    else if (flash.value?.info) info(flash.value.info)
})
</script>

<template>
    <AdminLayout title="Settings">
        <div class="py-8">
            <div class="max-w-6xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Settings</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Manage your platform configuration and preferences
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md overflow-hidden">
                    <div class="border-b border-gray-200 dark:border-slate-700">
                        <nav class="flex space-x-8 px-6 overflow-x-auto" aria-label="Tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 whitespace-nowrap"
                                :class="activeTab === tab.key
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                                @click="activeTab = tab.key"
                            >
                                <span class="flex items-center">
                                    <span class="mr-2" v-html="tab.icon"/>
                                    {{ tab.name }}
                                </span>
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- General Settings Tab -->
                        <div v-if="activeTab === 'general'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                General Settings
                            </h3>
                            <form class="space-y-6" @submit.prevent="updateGeneral">
                                <div
                                    v-for="section in generalSettingsConfig"
                                    :key="section.title"
                                    class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg"
                                >
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{
                                            section.title
                                        }}</h4>
                                    <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                                        <div
                                            v-for="field in section.fields"
                                            :key="field.key"
                                            :class="field.colSpan === 'full' ? 'col-span-full' : ''"
                                        >
                                            <!-- Checkbox -->
                                            <label v-if="field.type === 'checkbox'" class="flex items-center">
                                                <input
                                                    v-model="generalForm[field.key]"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:bg-slate-700"
                                                >
                                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ field.label }}
                                                </span>
                                            </label>

                                            <!-- File Input -->
                                            <template v-else-if="field.type === 'file'">
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <div v-if="field.currentFile" class="mb-3">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Current
                                                        File:</p>
                                                    <div
                                                        class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-slate-600 rounded-lg">
                                                        <img
                                                            :src="field.currentFile"
                                                            :alt="`Current ${field.label}`"
                                                            class="h-12 w-auto object-contain rounded"
                                                            @error="$event.target.style.display='none'"
                                                        >
                                                        <p class="text-sm text-gray-700 dark:text-gray-300">Click below
                                                            to change</p>
                                                    </div>
                                                </div>
                                                <input
                                                    :type="field.type"
                                                    :accept="field.accept"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                    :class="{ 'border-red-500': errors.general[field.key] }"
                                                    @change="handleFileChange($event, generalForm, field.key)"
                                                >
                                            </template>

                                            <!-- Select -->
                                            <template v-else-if="field.type === 'select'">
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <select
                                                    v-model="generalForm[field.key]"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                    :class="{ 'border-red-500': errors.general[field.key] }"
                                                >
                                                    <option v-for="option in field.options" :key="option.value"
                                                            :value="option.value">
                                                        {{ option.label }}
                                                    </option>
                                                </select>
                                            </template>

                                            <!-- Regular Input -->
                                            <template v-else>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <input
                                                    v-model="generalForm[field.key]"
                                                    :type="field.type"
                                                    :placeholder="field.placeholder"
                                                    :maxlength="field.maxlength"
                                                    :required="field.required"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                    :class="{ 'border-red-500': errors.general[field.key] }"
                                                >
                                            </template>

                                            <p v-if="field.description"
                                               class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ field.description }}
                                            </p>
                                            <p v-if="errors.general[field.key]" class="text-red-500 text-sm mt-1">
                                                {{ getErrorMessage(errors.general[field.key]) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="loadingStates.general"
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg v-if="loadingStates.general"
                                             class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none"
                                             viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor"
                                                  d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        {{ loadingStates.general ? 'Saving...' : 'Save General Settings' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Email Settings Tab -->
                        <div v-if="activeTab === 'email'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Email Configuration
                            </h3>
                            <form class="space-y-6" @submit.prevent="updateEmail">
                                <div
                                    v-for="section in emailSettingsConfig"
                                    :key="section.title"
                                    class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg"
                                >
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{
                                            section.title
                                        }}</h4>
                                    <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                                        <div
                                            v-for="field in section.fields"
                                            :key="field.key"
                                            v-show="shouldShowField(field, emailForm)"
                                        >
                                            <label v-if="field.type === 'select'"
                                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                {{ field.label }}
                                            </label>
                                            <select
                                                v-if="field.type === 'select'"
                                                v-model="emailForm[field.key]"
                                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                            >
                                                <option v-for="option in field.options" :key="option.value"
                                                        :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>

                                            <template v-else>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <input
                                                    v-model="emailForm[field.key]"
                                                    :type="field.type"
                                                    :placeholder="field.placeholder"
                                                    :required="field.required"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                >
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-between">
                                    <button
                                        type="button"
                                        class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors"
                                        @click="testEmail"
                                    >
                                        Test Email
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="loadingStates.email"
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg v-if="loadingStates.email"
                                             class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none"
                                             viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor"
                                                  d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        {{ loadingStates.email ? 'Saving...' : 'Save Email Settings' }}
                                    </button>
                                </div>
                            </form>

                            <!-- Email Templates Section -->
                            <div class="border-t dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                    Email Templates
                                </h3>
                                <div class="space-y-4">
                                    <div
                                        v-for="template in emailTemplates"
                                        :key="template.id"
                                        class="border dark:border-slate-700 rounded-lg p-4"
                                    >
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ template.name }}
                                            </h4>
                                            <div class="flex items-center space-x-2">
                                                <span
                                                    class="text-xs px-2 py-1 rounded-full"
                                                    :class="template.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                                                >
                                                    {{ template.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                                <button
                                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm"
                                                    @click="editEmailTemplate(template)"
                                                >
                                                    Edit
                                                </button>
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <p><strong>Subject:</strong> {{ template.subject }}</p>
                                            <p class="mt-1">
                                                <strong>Variables:</strong> {{
                                                    template.variables?.join(', ') || 'None'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SMS Settings Tab -->
                        <div v-if="activeTab === 'sms'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                SMS Configuration
                            </h3>
                            <form class="space-y-6" @submit.prevent="updateSms">
                                <div
                                    v-for="section in smsSettingsConfig"
                                    :key="section.title"
                                    class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg"
                                >
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{
                                            section.title
                                        }}</h4>
                                    <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                                        <div v-for="field in section.fields" :key="field.key">
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                {{ field.label }}
                                            </label>
                                            <input
                                                v-model="smsForm[field.key]"
                                                :type="field.type"
                                                :placeholder="field.placeholder"
                                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-between">
                                    <button
                                        type="button"
                                        class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors"
                                        @click="testSms"
                                    >
                                        Test SMS
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="loadingStates.sms"
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg v-if="loadingStates.sms" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                             fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor"
                                                  d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        {{ loadingStates.sms ? 'Saving...' : 'Save SMS Settings' }}
                                    </button>
                                </div>
                            </form>

                            <!-- SMS Templates Section -->
                            <div class="border-t dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                    SMS Templates
                                </h3>
                                <div class="space-y-4">
                                    <div
                                        v-for="template in smsTemplates"
                                        :key="template.id"
                                        class="border dark:border-slate-700 rounded-lg p-4"
                                    >
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ template.name }}
                                            </h4>
                                            <div class="flex items-center space-x-2">
                                                <span
                                                    class="text-xs px-2 py-1 rounded-full"
                                                    :class="template.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                                                >
                                                    {{ template.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                                <button
                                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm"
                                                    @click="editSmsTemplate(template)"
                                                >
                                                    Edit
                                                </button>
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <p><strong>Message:</strong> {{
                                                    template.message.substring(0, 100)
                                                }}{{ template.message.length > 100 ? '...' : '' }}</p>
                                            <p class="mt-1">
                                                <strong>Variables:</strong> {{
                                                    template.variables?.join(', ') || 'None'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings Tab -->
                        <div v-if="activeTab === 'security'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Security Settings
                            </h3>
                            <form class="space-y-6" @submit.prevent="updateSecurity">
                                <div
                                    v-for="section in securitySettingsConfig"
                                    :key="section.title"
                                    class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg"
                                >
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{
                                            section.title
                                        }}</h4>
                                    <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                                        <div v-for="field in section.fields" :key="field.key">
                                            <label v-if="field.type === 'checkbox'" class="flex items-center">
                                                <input
                                                    v-model="securityForm[field.key]"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:bg-slate-700"
                                                >
                                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ field.label }}
                                                </span>
                                            </label>
                                            <template v-else>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <input
                                                    v-model.number="securityForm[field.key]"
                                                    :type="field.type"
                                                    :min="field.min"
                                                    :max="field.max"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                >
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="loadingStates.security"
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg v-if="loadingStates.security"
                                             class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none"
                                             viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor"
                                                  d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        {{ loadingStates.security ? 'Saving...' : 'Save Security Settings' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- SEO Settings Tab -->
                        <div v-if="activeTab === 'seo'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                SEO Settings
                            </h3>
                            <form class="space-y-6" @submit.prevent="updateSeo">
                                <div
                                    v-for="section in seoSettingsConfig"
                                    :key="section.title"
                                    class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg"
                                >
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{
                                            section.title
                                        }}</h4>
                                    <div
                                        class="grid gap-4"
                                        :class="section.gridCols === 3 ? 'grid-cols-1 md:grid-cols-3' : 'grid-cols-1 md:grid-cols-2'"
                                    >
                                        <div v-for="field in section.fields" :key="field.key">
                                            <!-- File Input -->
                                            <template v-if="field.type === 'file'">
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <div v-if="field.currentFile" class="mb-3">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Current
                                                        File:</p>
                                                    <div class="p-3 bg-gray-50 dark:bg-slate-600 rounded-lg">
                                                        <img
                                                            :src="field.currentFile"
                                                            :alt="`Current ${field.label}`"
                                                            class="w-full max-w-sm h-32 object-cover rounded border"
                                                            @error="$event.target.style.display='none'"
                                                        >
                                                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-2">Click
                                                            below to change</p>
                                                    </div>
                                                </div>
                                                <input
                                                    :type="field.type"
                                                    :accept="field.accept"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                    :class="{ 'border-red-500': errors.seo[field.key] }"
                                                    @change="handleFileChange($event, seoForm, field.key)"
                                                >
                                            </template>

                                            <!-- Select -->
                                            <template v-else-if="field.type === 'select'">
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <select
                                                    v-model="seoForm[field.key]"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                    :class="{ 'border-red-500': errors.seo[field.key] }"
                                                >
                                                    <option v-for="option in field.options" :key="option.value"
                                                            :value="option.value">
                                                        {{ option.label }}
                                                    </option>
                                                </select>
                                            </template>

                                            <!-- Textarea -->
                                            <template v-else-if="field.type === 'textarea'">
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <textarea
                                                    v-model="seoForm[field.key]"
                                                    :rows="field.rows"
                                                    :required="field.required"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                    :class="{ 'border-red-500': errors.seo[field.key] }"
                                                />
                                            </template>

                                            <!-- Regular Input -->
                                            <template v-else>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <input
                                                    v-model="seoForm[field.key]"
                                                    :type="field.type"
                                                    :placeholder="field.placeholder"
                                                    :required="field.required"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                    :class="{ 'border-red-500': errors.seo[field.key] }"
                                                >
                                            </template>

                                            <p v-if="field.description"
                                               class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ field.description }}
                                            </p>
                                            <p v-if="errors.seo[field.key]" class="text-red-500 text-sm mt-1">
                                                {{ getErrorMessage(errors.seo[field.key]) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="loadingStates.seo"
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg v-if="loadingStates.seo" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                             fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor"
                                                  d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        {{ loadingStates.seo ? 'Saving...' : 'Save SEO Settings' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- AI Settings Tab -->
                        <div v-if="activeTab === 'ai'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                AI Recommendation Settings
                            </h3>
                            <form class="space-y-6" @submit.prevent="updateAi">
                                <div
                                    v-for="section in aiSettingsConfig"
                                    :key="section.title"
                                    class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg"
                                >
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{
                                            section.title
                                        }}</h4>
                                    <div
                                        class="grid gap-4"
                                        :class="section.gridCols === 3 ? 'grid-cols-1 md:grid-cols-3' : 'grid-cols-1 md:grid-cols-2'"
                                    >
                                        <div
                                            v-for="field in section.fields"
                                            :key="field.key"
                                            :class="field.colSpan === 'full' ? 'col-span-full' : ''"
                                        >
                                            <label v-if="field.type === 'checkbox'" class="flex items-center">
                                                <input
                                                    v-model="aiForm[field.key]"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:bg-slate-700"
                                                >
                                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ field.label }}
                                                </span>
                                            </label>
                                            <template v-else>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ field.label }}
                                                </label>
                                                <input
                                                    v-model.number="aiForm[field.key]"
                                                    :type="field.type"
                                                    :min="field.min"
                                                    :max="field.max"
                                                    :step="field.step || 1"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                                    :class="{ 'border-red-500': errors.ai[field.key] }"
                                                >
                                            </template>
                                            <p v-if="field.description"
                                               class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ field.description }}
                                            </p>
                                            <p v-if="errors.ai[field.key]" class="text-red-500 text-sm mt-1">
                                                {{ getErrorMessage(errors.ai[field.key]) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="loadingStates.ai"
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg v-if="loadingStates.ai" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                             fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor"
                                                  d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        {{ loadingStates.ai ? 'Saving...' : 'Save AI Settings' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Template Modal -->
        <div
            v-if="modals.showEmail"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        >
            <div
                class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white dark:bg-slate-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Edit Email Template
                    </h3>
                    <form class="space-y-4" @submit.prevent="saveEmailTemplate">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Template
                                    Name</label>
                                <input
                                    v-model="editingEmailTemplate.name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                    :class="{ 'border-red-500': errors.template.name }"
                                    required
                                >
                                <p v-if="errors.template.name" class="text-red-500 text-sm mt-1">
                                    {{ getErrorMessage(errors.template.name) }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject</label>
                                <input
                                    v-model="editingEmailTemplate.subject"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                    :class="{ 'border-red-500': errors.template.subject }"
                                    required
                                >
                                <p v-if="errors.template.subject" class="text-red-500 text-sm mt-1">
                                    {{ getErrorMessage(errors.template.subject) }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email
                                Body</label>
                            <textarea
                                v-model="editingEmailTemplate.body"
                                rows="10"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                :class="{ 'border-red-500': errors.template.body }"
                                required
                            />
                            <p v-if="errors.template.body" class="text-red-500 text-sm mt-1">
                                {{ getErrorMessage(errors.template.body) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Available variables: {{ editingEmailTemplate.variables?.join(', ') || 'None' }}
                            </p>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="editingEmailTemplate.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:bg-slate-700"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                            </label>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500"
                                @click="closeEmailModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loadingStates.template"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-blue-400 flex items-center justify-center"
                            >
                                <svg v-if="loadingStates.template" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                     fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                {{ loadingStates.template ? 'Saving...' : 'Save Template' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SMS Template Modal -->
        <div
            v-if="modals.showSms"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        >
            <div
                class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white dark:bg-slate-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Edit SMS Template
                    </h3>
                    <form class="space-y-4" @submit.prevent="saveSmsTemplate">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Template
                                Name</label>
                            <input
                                v-model="editingSmsTemplate.name"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                :class="{ 'border-red-500': errors.smsTemplate.name }"
                                required
                            >
                            <p v-if="errors.smsTemplate.name" class="text-red-500 text-sm mt-1">
                                {{ getErrorMessage(errors.smsTemplate.name) }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMS
                                Message</label>
                            <textarea
                                v-model="editingSmsTemplate.message"
                                rows="5"
                                maxlength="160"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                :class="{ 'border-red-500': errors.smsTemplate.message }"
                                required
                            />
                            <p v-if="errors.smsTemplate.message" class="text-red-500 text-sm mt-1">
                                {{ getErrorMessage(errors.smsTemplate.message) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ editingSmsTemplate.message?.length || 0 }}/160 characters
                                | Available variables: {{ editingSmsTemplate.variables?.join(', ') || 'None' }}
                            </p>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="editingSmsTemplate.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:bg-slate-700"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                            </label>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500"
                                @click="closeSmsModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loadingStates.smsTemplate"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-blue-400 flex items-center justify-center"
                            >
                                <svg v-if="loadingStates.smsTemplate" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                     fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                {{ loadingStates.smsTemplate ? 'Saving...' : 'Save Template' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Test Email Modal -->
        <div
            v-if="modals.showTestEmail"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        >
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-slate-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Test Email Configuration
                    </h3>
                    <form class="space-y-4" @submit.prevent="sendTestEmail">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email
                                Address</label>
                            <input
                                v-model="testEmailForm.email"
                                type="email"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                placeholder="test@example.com"
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Test
                                Message</label>
                            <textarea
                                v-model="testEmailForm.message"
                                rows="4"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                required
                            />
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500"
                                @click="closeTestEmailModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loadingStates.testEmail"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-blue-400 flex items-center justify-center"
                            >
                                <svg v-if="loadingStates.testEmail" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                     fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                {{ loadingStates.testEmail ? 'Sending...' : 'Send Test Email' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Test SMS Modal -->
        <div
            v-if="modals.showTestSms"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        >
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-slate-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Test SMS Configuration
                    </h3>
                    <form class="space-y-4" @submit.prevent="sendTestSms">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone
                                Number</label>
                            <input
                                v-model="testSmsForm.phone"
                                type="tel"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                placeholder="+1234567890"
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Test
                                Message</label>
                            <textarea
                                v-model="testSmsForm.message"
                                rows="3"
                                maxlength="160"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-100 p-3"
                                required
                            />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ testSmsForm.message?.length || 0 }}/160 characters
                            </p>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500"
                                @click="closeTestSmsModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loadingStates.testSms"
                                class="px-4 py-2 bg-lime-500 text-white rounded-lg hover:bg-lime-600 disabled:bg-lime-400 flex items-center justify-center"
                            >
                                <svg v-if="loadingStates.testSms" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                     fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                {{ loadingStates.testSms ? 'Sending...' : 'Send Test SMS' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

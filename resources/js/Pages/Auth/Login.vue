<template>
    <div
        class="min-h-screen flex items-center justify-center px-4 py-8 relative overflow-hidden"
        :style="sectionStyle"
    >
        <div class="absolute inset-0 opacity-30">
            <div
                class="absolute inset-0"
                :style="gridStyle"
            ></div>
        </div>

        <div class="absolute inset-0 overflow-hidden">
            <div
                class="absolute top-20 left-20 w-96 h-96 rounded-full blur-3xl animate-blob"
                :style="blob1Style"
            ></div>
            <div
                class="absolute bottom-20 right-20 w-80 h-80 rounded-full blur-3xl animate-blob animation-delay-2000"
                :style="blob2Style"
            ></div>
            <div
                class="absolute top-1/2 left-1/2 w-64 h-64 rounded-full blur-3xl animate-blob animation-delay-4000"
                :style="blob3Style"
            ></div>
        </div>

        <div class="absolute inset-0" ref="particlesContainer"></div>

        <div class="relative z-10 w-full max-w-sm sm:max-w-md lg:max-w-lg mx-auto mt-16 sm:mt-20">
            <div class="text-center mb-6 sm:mb-8 animate-fade-in-up">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black mb-2 sm:mb-3 leading-tight">
                    <span :style="{ background: gradientText, WebkitBackgroundClip: 'text', backgroundClip: 'text', color: 'transparent' }">
                        {{ appName }}
                    </span>
                </h1>
                <p class="text-sm sm:text-base lg:text-lg font-medium text-gray-300">
                    {{ t('authSubtitle') }}
                </p>
                <div class="mt-3 sm:mt-4 w-12 sm:w-16 h-1 rounded-full mx-auto" :style="underlineStyle"></div>
            </div>

            <div
                class="backdrop-blur-xl border rounded-2xl sm:rounded-3xl p-6 sm:p-8 shadow-2xl animate-fade-in-up animation-delay-300"
                :style="cardStyle"
            >
                <div class="text-center mb-6 sm:mb-8">
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-2 sm:mb-3 text-white">
                        {{ getViewTitle() }}
                    </h2>
                    <p class="text-sm sm:text-base text-gray-300">
                        {{ getViewDescription() }}
                    </p>
                </div>

                <form
                    v-if="currentView === 'login'"
                    class="space-y-4 sm:space-y-6"
                    @submit.prevent="handleLogin"
                >
                    <div class="space-y-4 sm:space-y-5">
                        <div>
                            <label class="block text-sm font-semibold mb-2 sm:mb-3 text-gray-300">
                                {{ t('email') || 'Email Address' }}
                            </label>
                            <input
                                v-model="loginForm.email"
                                type="email"
                                required
                                class="w-full px-4 sm:px-5 py-3 sm:py-4 rounded-lg sm:rounded-xl border focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-300 text-sm sm:text-base"
                                :style="getInputStyle(loginForm.errors.email)"
                                :placeholder="t('emailPlaceholder') || 'Enter your email'"
                            >
                            <div v-if="loginForm.errors.email" class="mt-2 text-sm text-red-400">
                                {{ loginForm.errors.email }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2 sm:mb-3 text-gray-300">
                                {{ t('password') || 'Password' }}
                            </label>
                            <div class="relative">
                                <input
                                    v-model="loginForm.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    class="w-full px-4 sm:px-5 py-3 sm:py-4 pr-12 sm:pr-14 rounded-lg sm:rounded-xl border focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-300 text-sm sm:text-base"
                                    :style="getInputStyle(loginForm.errors.password)"
                                    :placeholder="t('passwordPlaceholder') || 'Enter your password'"
                                >
                                <button
                                    type="button"
                                    class="absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-300 transition-all duration-300 hover:scale-110"
                                    @click="showPassword = !showPassword"
                                >
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                            <div v-if="loginForm.errors.password" class="mt-2 text-sm text-red-400">
                                {{ loginForm.errors.password }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                        <label class="flex items-center">
                            <input
                                v-model="loginForm.remember"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                            <span class="ml-2 sm:ml-3 text-sm font-medium text-gray-300">
                                {{ t('rememberMe') || 'Remember me' }}
                            </span>
                        </label>
                        <button
                            type="button"
                            class="text-sm font-semibold hover:opacity-80 transition-all duration-300 hover:scale-105 text-left sm:text-right text-blue-400"
                            @click="setCurrentView('forgot')"
                        >
                            {{ t('forgotPassword') || 'Forgot Password?' }}
                        </button>
                    </div>

                    <button
                        type="submit"
                        :disabled="loginForm.processing"
                        class="w-full py-3 sm:py-4 font-bold text-sm sm:text-lg rounded-lg sm:rounded-xl transition-all duration-300 hover:shadow-xl hover:scale-105 disabled:opacity-50 disabled:transform-none"
                        :style="primaryButtonStyle"
                    >
                        <span v-if="loginForm.processing" class="flex items-center justify-center gap-2 sm:gap-3">
                            <i class="fas fa-spinner fa-spin"></i>
                            {{ t('signingIn') || 'Signing In...' }}
                        </span>
                        <span v-else class="flex items-center justify-center gap-2 sm:gap-3">
                            <i class="fas fa-sign-in-alt"></i>
                            {{ t('signIn') || 'Sign In' }}
                        </span>
                    </button>
                </form>

                <form
                    v-else-if="currentView === 'forgot'"
                    class="space-y-6"
                    @submit.prevent="handleForgotPassword"
                >
                    <div>
                        <label class="block text-sm font-semibold mb-3 text-gray-300">
                            {{ t('email') }}
                        </label>
                        <input
                            v-model="forgotForm.email"
                            type="email"
                            required
                            class="w-full px-5 py-4 rounded-xl border focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-300"
                            :style="getInputStyle(forgotForm.errors.email)"
                            :placeholder="t('emailPlaceholder')"
                        >
                        <div v-if="forgotForm.errors.email" class="mt-2 text-sm text-red-400">
                            {{ forgotForm.errors.email }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="forgotForm.processing"
                        class="w-full py-4 font-bold text-lg rounded-xl transition-all duration-300 hover:shadow-xl hover:scale-105 disabled:opacity-50 disabled:transform-none"
                        :style="primaryButtonStyle"
                    >
                        <span v-if="forgotForm.processing" class="flex items-center justify-center gap-3">
                            <i class="fas fa-spinner fa-spin"></i>
                            {{ t('sendingResetLink') }}
                        </span>
                        <span v-else class="flex items-center justify-center gap-3">
                            <i class="fas fa-paper-plane"></i>
                            {{ t('sendResetLink') }}
                        </span>
                    </button>

                    <div class="text-center">
                        <button
                            type="button"
                            class="text-sm font-semibold hover:opacity-80 transition-all duration-300 hover:scale-105 text-blue-400"
                            @click="setCurrentView('login')"
                        >
                            {{ t('backToSignIn') }}
                        </button>
                    </div>
                </form>

                <form
                    v-else-if="currentView === 'reset'"
                    class="space-y-6"
                    @submit.prevent="handleResetPassword"
                >
                    <div class="mb-6 p-4 rounded-xl border border-blue-400/30 bg-blue-400/10">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                            <div>
                                <p class="font-semibold text-blue-400">{{ t('resetPasswordFor') || 'Reset Password' }}</p>
                                <p class="text-sm text-gray-300">
                                    {{ t('resettingPasswordFor') || 'Resetting password for:' }} <strong>{{ resetEmail }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold mb-3 text-gray-300">
                                {{ t('newPassword') || 'New Password' }}
                            </label>
                            <div class="relative">
                                <input
                                    v-model="resetPasswordForm.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    class="w-full px-5 py-4 pr-14 rounded-xl border focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-300"
                                    :style="getInputStyle(resetPasswordForm.errors.password)"
                                    :placeholder="t('enterNewPassword') || 'Enter your new password'"
                                >
                                <button
                                    type="button"
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-300 transition-all duration-300 hover:scale-110"
                                    @click="showPassword = !showPassword"
                                >
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                            <div v-if="resetPasswordForm.errors.password" class="mt-2 text-sm text-red-400">
                                {{ resetPasswordForm.errors.password }}
                            </div>
                            <div class="mt-2 text-xs text-gray-400">
                                {{ t('passwordRequirements') || 'Password must contain uppercase, lowercase, number, and special character' }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-3 text-gray-300">
                                {{ t('confirmNewPassword') || 'Confirm New Password' }}
                            </label>
                            <div class="relative">
                                <input
                                    v-model="resetPasswordForm.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    required
                                    class="w-full px-5 py-4 pr-14 rounded-xl border focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-300"
                                    :style="getInputStyle(resetPasswordForm.errors.password_confirmation)"
                                    :placeholder="t('confirmNewPassword') || 'Confirm your new password'"
                                >
                                <button
                                    type="button"
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-300 transition-all duration-300 hover:scale-110"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                >
                                    <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                            <div v-if="resetPasswordForm.errors.password_confirmation" class="mt-2 text-sm text-red-400">
                                {{ resetPasswordForm.errors.password_confirmation }}
                            </div>
                        </div>
                    </div>

                    <div v-if="resetPasswordForm.errors.email || resetPasswordForm.errors.token" class="text-center">
                        <div class="text-sm text-red-400">
                            {{ resetPasswordForm.errors.email || resetPasswordForm.errors.token }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="resetPasswordForm.processing"
                        class="w-full py-4 font-bold text-lg rounded-xl transition-all duration-300 hover:shadow-xl hover:scale-105 disabled:opacity-50 disabled:transform-none"
                        :style="primaryButtonStyle"
                    >
                        <span v-if="resetPasswordForm.processing" class="flex items-center justify-center gap-3">
                            <i class="fas fa-spinner fa-spin"></i>
                            {{ t('resettingPassword') || 'Resetting Password...' }}
                        </span>
                        <span v-else class="flex items-center justify-center gap-3">
                            <i class="fas fa-key"></i>
                            {{ t('resetPassword') || 'Reset Password' }}
                        </span>
                    </button>

                    <div class="text-center">
                        <button
                            type="button"
                            class="text-sm font-semibold hover:opacity-80 transition-all duration-300 hover:scale-105 text-blue-400"
                            @click="setCurrentView('login')"
                        >
                            {{ t('backToSignIn') || 'Back to Sign In' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-6 sm:mt-8 text-center animate-fade-in-up animation-delay-600">
                <div class="flex items-center justify-center gap-2 sm:gap-3 text-xs sm:text-sm backdrop-blur-xl border rounded-xl sm:rounded-2xl p-3 sm:p-4" :style="securityNoticeStyle">
                    <i class="fas fa-shield-alt text-green-400 text-sm sm:text-base"></i>
                    <span class="text-gray-300">{{ t('securityNotice') || 'Your data is secure with us' }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
import { useTranslation } from '@/composables/useTranslation'

const props = defineProps({
    appName: {
        type: String,
        default: null
    }
})

const page = usePage()
const { success, error, warning, info } = useToast()
const { t } = useTranslation()

const colors = {
    primary: '#0a0a0a',
    secondary: '#1a1a1a',
    background: '#000000',
    surface: '#0f0f0f',
    surfaceLight: '#1a1a1a',
    textPrimary: '#ffffff',
    textSecondary: '#a3a3a3',
    textMuted: '#737373',
    border: '#262626',
    borderLight: '#333333',
    accent: '#0a0a0a',
    accentHover: '#0a0a0a',
    success: '#10b981',
    warning: '#f59e0b',
    error: '#ef4444'
}

const sectionStyle = {
    background: '#000000'
}

const gridStyle = {
    backgroundImage: 'linear-gradient(rgba(38, 38, 38, 0.15) 1px, transparent 1px), linear-gradient(90deg, rgba(38, 38, 38, 0.15) 1px, transparent 1px)',
    backgroundSize: '50px 50px'
}

const blob1Style = {
    background: 'linear-gradient(45deg, rgba(37, 99, 235, 0.15), rgba(26, 26, 26, 0.1))'
}

const blob2Style = {
    background: 'linear-gradient(135deg, rgba(26, 26, 26, 0.15), rgba(37, 99, 235, 0.1))'
}

const blob3Style = {
    background: 'linear-gradient(225deg, rgba(37, 99, 235, 0.12), rgba(15, 15, 15, 0.1))'
}

const gradientText = 'linear-gradient(135deg, #ffffff 0%, #a3a3a3 50%, #2563eb 100%)'

const underlineStyle = {
    background: 'linear-gradient(90deg, #2563eb, #1a1a1a)'
}

const cardStyle = {
    backgroundColor: 'rgba(15, 15, 15, 0.95)',
    borderColor: 'rgba(38, 38, 38, 0.5)',
    boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.8)'
}

const primaryButtonStyle = {
    background: 'linear-gradient(135deg, #2563eb 0%, #1a1a1a 100%)',
    color: '#ffffff',
    boxShadow: '0 20px 40px -12px rgba(37, 99, 235, 0.3)'
}

const securityNoticeStyle = {
    backgroundColor: 'rgba(15, 15, 15, 0.6)',
    borderColor: 'rgba(38, 38, 38, 0.4)'
}

const particlesContainer = ref(null)
const currentView = ref('login')
const showPassword = ref(false)
const showConfirmPassword = ref(false)

const resetToken = ref(null)
const resetEmail = ref(null)

const loginForm = useForm({
    email: '',
    password: '',
    remember: false
})

const forgotForm = useForm({
    email: ''
})

const resetPasswordForm = useForm({
    token: '',
    email: '',
    password: '',
    password_confirmation: ''
})

const getViewTitle = () => {
    const titles = {
        'login': t('welcomeBack'),
        'forgot': t('resetPassword'),
        'reset': t('resetPassword') || 'Reset Password'
    }
    return titles[currentView.value] || t('authenticationRequired')
}

const getViewDescription = () => {
    const descriptions = {
        'login': t('loginToAccount'),
        'forgot': t('enterEmailForReset'),
        'reset': t('enterNewPassword') || 'Enter your new password'
    }
    return descriptions[currentView.value] || t('authenticationRequired')
}

const setCurrentView = (view) => {
    currentView.value = view
    loginForm.reset()
    forgotForm.reset()
    if (view !== 'reset') {
        resetPasswordForm.reset()
    }
}

const getInputStyle = (error) => {
    return {
        backgroundColor: 'rgba(15, 15, 15, 0.8)',
        borderColor: error ? colors.error : 'rgba(38, 38, 38, 0.8)',
        color: colors.textPrimary
    }
}

const handleLogin = () => {
    loginForm.post('/login', {
        onSuccess: () => {
            loginForm.reset()
        },
        onError: (errors) => {
        }
    })
}

const handleForgotPassword = () => {
    forgotForm.post('/forgot-password', {
        onSuccess: () => {
            forgotForm.reset()
        },
        onError: (errors) => {
        }
    })
}

const handleResetPassword = () => {
    resetPasswordForm.post('/reset-password', {
        onSuccess: () => {
            resetPasswordForm.reset()
            success(t('passwordResetSuccess') || 'Password reset successfully! You can now login with your new password.')
            setCurrentView('login')
        },
        onError: (errors) => {
            console.error('Reset password errors:', errors)
        }
    })
}

const createModernParticles = () => {
    if (!particlesContainer.value) return

    const particleCount = 30

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div')
        particle.className = 'absolute rounded-full opacity-0 animate-ping'

        const size = Math.random() * 4 + 2
        particle.style.width = size + 'px'
        particle.style.height = size + 'px'
        particle.style.backgroundColor = colors.accent
        particle.style.left = Math.random() * 100 + '%'
        particle.style.top = Math.random() * 100 + '%'
        particle.style.animationDelay = Math.random() * 15 + 's'
        particle.style.animationDuration = (Math.random() * 10 + 8) + 's'

        particlesContainer.value.appendChild(particle)
    }
}

const handleFlashMessages = () => {
    const flashData = page.props.flash || {}

    if (flashData.success) {
        success(flashData.success)
    }

    if (flashData.error) {
        error(flashData.error)
    }

    if (flashData.warning) {
        warning(flashData.warning)
    }

    if (flashData.info) {
        info(flashData.info)
    }
}

const checkForResetToken = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    const email = urlParams.get('email');

    if (token && email) {
        resetToken.value = token;
        resetEmail.value = email;
        resetPasswordForm.token = token;
        resetPasswordForm.email = email;
        currentView.value = 'reset';

        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
}

watch(() => page.props.flash, () => {
    handleFlashMessages()
}, { deep: true })

onMounted(() => {
    checkForResetToken();
    createModernParticles()
    handleFlashMessages()
})
</script>

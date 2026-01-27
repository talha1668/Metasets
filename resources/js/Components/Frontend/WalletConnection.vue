<template>
    <!-- Show wallet connect only for guests (not logged in at all) -->
    <div v-if="!isLoggedIn"
         class="p-3 sm:p-4 lg:p-6 rounded-xl sm:rounded-2xl backdrop-blur-xl border animate-scale-in animation-delay-1200"
         :style="walletPromptStyle">
        <div class="flex items-start sm:items-center gap-3 mb-3 sm:mb-4">
            <div
                class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl flex items-center justify-center flex-shrink-0"
                :style="walletIconStyle">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 18v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1M7 10l5-5 5 5M12 16V4"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-white font-bold text-base sm:text-lg lg:text-xl">{{ t('connectWeb3Wallet') }}</h3>
                <p class="text-white/70 text-xs sm:text-sm mt-0.5 font-normal">{{ t('accessProfessionalDefi') }}</p>
            </div>
        </div>
        <button
            @click="$emit('connectWallet')"
            :disabled="connecting"
            class="w-full py-2.5 sm:py-3 px-4 rounded-lg sm:rounded-xl font-bold text-sm sm:text-base transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
            :style="primaryButtonStyle"
        >
            <span class="flex items-center justify-center gap-2">
                <div v-if="connecting"
                     class="w-3.5 h-3.5 sm:w-4 sm:h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                <svg v-else class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                {{ connecting ? t('connectingUpper') : t('connectWalletUpper') }}
            </span>
        </button>
    </div>

    <!-- Show wallet connected state -->
    <div v-else-if="walletConnected && walletAddress"
         class="p-3 sm:p-4 lg:p-6 rounded-xl sm:rounded-2xl backdrop-blur-xl border animate-scale-in"
         :style="connectedWalletStyle">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl flex items-center justify-center"
                         :style="connectedIconStyle">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full animate-pulse"
                         :style="{ backgroundColor: themeColors.primary }"></div>
                </div>
                <div>
                    <p class="text-white font-bold text-sm sm:text-base">{{ t('walletConnected') }}</p>
                    <p class="text-white/70 text-xs sm:text-sm font-medium">
                        {{ formatAddress(walletAddress) }} • {{ walletBalance }} ETH
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="$emit('copyAddress')"
                        class="p-1.5 sm:p-2 rounded-lg backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all hover:scale-105 active:scale-95">
                    <svg v-if="!copied" class="w-4 h-4 sm:w-5 sm:h-5 text-white/70" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg v-else class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20"
                         :style="{ color: themeColors.primary }">
                        <path fill-rule="evenodd"
                              d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                              clip-rule="evenodd"/>
                    </svg>
                </button>
                <Link
                    href="/user/investments"
                    class="py-1.5 px-3 sm:py-2 sm:px-4 rounded-lg font-bold text-sm sm:text-base transition-all hover:scale-105 active:scale-95 inline-flex items-center justify-center"
                    :style="secondaryButtonStyle"
                >
                    {{ t('startInvesting') }}
                </Link>
            </div>
        </div>
    </div>

    <!-- Show user authenticated state (only for role: user) -->
    <div v-else-if="isUserRole"
         class="p-3 sm:p-4 lg:p-6 rounded-xl sm:rounded-2xl backdrop-blur-xl border animate-scale-in"
         :style="connectedWalletStyle">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl flex items-center justify-center"
                         :style="connectedIconStyle">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full animate-pulse"
                         :style="{ backgroundColor: themeColors.primary }"></div>
                </div>
                <div>
                    <p class="text-white font-bold text-sm sm:text-base">{{ t('welcomeUser') }}, {{ userName }}</p>
                    <p class="text-white/70 text-xs sm:text-sm font-medium">
                        {{ userEmail }}
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <Link
                    href="/user/investments"
                    class="py-1.5 px-3 sm:py-2 sm:px-4 rounded-lg font-bold text-sm sm:text-base transition-all hover:scale-105 active:scale-95 inline-flex items-center justify-center"
                    :style="secondaryButtonStyle"
                >
                    {{ t('startInvesting') }}
                </Link>
            </div>
        </div>
    </div>

    <!-- Admin logged in - show admin dashboard link -->
    <div v-else-if="isAdminRole"
         class="p-3 sm:p-4 lg:p-6 rounded-xl sm:rounded-2xl backdrop-blur-xl border animate-scale-in"
         :style="connectedWalletStyle">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl flex items-center justify-center"
                         :style="connectedIconStyle">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full animate-pulse"
                         :style="{ backgroundColor: themeColors.primary }"></div>
                </div>
                <div>
                    <p class="text-white font-bold text-sm sm:text-base">{{ t('adminPanel') }}</p>
                    <p class="text-white/70 text-xs sm:text-sm font-medium">
                        {{ userName }} • {{ t('administrator') }}
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <Link
                    href="/admin/dashboard"
                    class="py-1.5 px-3 sm:py-2 sm:px-4 rounded-lg font-bold text-sm sm:text-base transition-all hover:scale-105 active:scale-95 inline-flex items-center justify-center gap-2"
                    :style="secondaryButtonStyle"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    {{ t('goToDashboard') }}
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue'
import {usePage, Link} from '@inertiajs/vue3'
import {useTranslation} from '@/composables/useTranslation'

const props = defineProps({
    walletConnected: {
        type: Boolean,
        default: false
    },
    walletAddress: {
        type: String,
        default: ''
    },
    walletBalance: {
        type: String,
        default: '0.00'
    },
    connecting: {
        type: Boolean,
        default: false
    },
    copied: {
        type: Boolean,
        default: false
    }
})

defineEmits(['connectWallet', 'copyAddress'])
const {t} = useTranslation()
const page = usePage()

const authUser = computed(() => page.props.auth?.user || null)
const isLoggedIn = computed(() => !!authUser.value)
const isUserRole = computed(() => authUser.value?.role === 'user')
const isAdminRole = computed(() => authUser.value?.role === 'admin')
const userName = computed(() => authUser.value?.name || 'User')
const userEmail = computed(() => authUser.value?.email || '')

const primaryColor = computed(() => page.props.primaryColor || '#86efac')

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') {
        return {r: 134, g: 239, b: 172}
    }
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : {r: 134, g: 239, b: 172}
}

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex)
    const newR = Math.min(255, Math.max(0, rgb.r + amount))
    const newG = Math.min(255, Math.max(0, rgb.g + amount))
    const newB = Math.min(255, Math.max(0, rgb.b + amount))
    return "#" + ((1 << 24) + (newR << 16) + (newG << 8) + newB).toString(16).slice(1)
}

const themeColors = computed(() => {
    const rgb = hexToRgb(primaryColor.value)
    return {
        primary: primaryColor.value,
        primaryDark: adjustColor(primaryColor.value, -20),
        primaryLight: adjustColor(primaryColor.value, 40),
        primaryRgb: `${rgb.r}, ${rgb.g}, ${rgb.b}`
    }
})

const walletPromptStyle = computed(() => ({
    backgroundColor: 'rgba(15, 23, 42, 0.6)',
    borderColor: 'rgba(255, 255, 255, 0.1)'
}))

const walletIconStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`
}))

const primaryButtonStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`,
    color: '#ffffff',
    boxShadow: `0 10px 40px rgba(${themeColors.value.primaryRgb}, 0.3)`
}))

const connectedWalletStyle = computed(() => ({
    backgroundColor: 'rgba(15, 23, 42, 0.6)',
    borderColor: `rgba(${themeColors.value.primaryRgb}, 0.3)`
}))

const connectedIconStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`
}))

const secondaryButtonStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`,
    color: '#ffffff'
}))

const formatAddress = (address) => {
    if (!address) return ''
    return `${address.slice(0, 6)}...${address.slice(-4)}`
}
</script>

<style scoped>
@keyframes scale-in {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-scale-in {
    animation: scale-in 0.5s ease-out forwards;
}

.animation-delay-1200 {
    animation-delay: 1.2s;
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .animate-scale-in {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
}
</style>

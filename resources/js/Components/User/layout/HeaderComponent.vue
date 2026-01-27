<template>
    <header class="relative z-50 border-b border-slate-800/30 backdrop-blur-xl bg-slate-900/50 shadow-2xl" role="banner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14 sm:h-16">
                <div class="flex items-center gap-3 sm:gap-5 flex-1 min-w-0">
                    <Link :href="'/user/dashboard'" class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 rounded-lg p-1.5 flex-shrink-0" :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`">
                        <img v-if="logoUrl" :src="logoUrl" :alt="appName + ' logo'" class="h-8 w-auto">
                        <span v-else class="text-xl font-bold text-white">{{ appName }}</span>
                    </Link>

                    <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-lg border animate-fade-in-up whitespace-nowrap"
                         :style="`background: rgba(${themeColors.primaryRgb}, 0.08); border-color: rgba(${themeColors.primaryRgb}, 0.2);`"
                         role="status"
                         aria-live="polite"
                         :aria-label="t('platformStatusIndicator')">
                        <div class="w-2 h-2 rounded-full animate-pulse-slow"
                             :style="{ backgroundColor: themeColors.primary }"
                             aria-hidden="true"></div>
                        <span class="text-xs font-semibold" :style="{ color: themeColors.primary }">{{ t('livePlatform') }}</span>
                        <span class="text-xs font-medium text-white/80">{{ t('platformNumber') }}+ {{ t('active') }}</span>
                    </div>

                    <nav class="hidden lg:flex items-center gap-1 ml-3 animate-fade-in animation-delay-300"
                         role="navigation"
                         :aria-label="t('mainNavigation')">
                        <Link v-for="item in navigationItems"
                              :key="item.url"
                              :href="item.url"
                              :class="['px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900',
                                       isActive(item.url) ? 'shadow-lg hover:scale-[1.02]' : 'text-slate-300 hover:text-white hover:bg-slate-800/30 hover:scale-[1.02]']"
                              :style="isActive(item.url) ? `background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : ''"
                              :aria-current="isActive(item.url) ? 'page' : undefined">
                            <span class="relative z-10">{{ t(item.label) }}</span>
                        </Link>
                    </nav>
                </div>

                <div class="flex items-center gap-2 animate-slide-in-right animation-delay-600 flex-shrink-0" role="group" :aria-label="t('accountInformationControls')">
                    <div class="hidden lg:block relative" ref="userMenuRef">
                        <button @click="toggleUserMenu"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg border transition-all duration-300 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`background: rgba(${themeColors.primaryRgb}, 0.08); border-color: rgba(${themeColors.primaryRgb}, 0.2); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                :aria-expanded="showUserMenu"
                                :aria-label="t('userMenu')">
                            <div v-if="user?.avatar_url" class="w-7 h-7 rounded-full overflow-hidden border-2" :style="`border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <img :src="user.avatar_url" :alt="user.name" class="w-full h-full object-cover">
                            </div>
                            <div v-else class="w-7 h-7 rounded-full flex items-center justify-center text-slate-900 font-bold text-xs" :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark});`">
                                {{ user?.initials || 'U' }}
                            </div>
                            <div class="hidden xl:block text-left">
                                <p class="text-sm font-semibold text-white leading-tight truncate max-w-[100px]">{{ user?.name || 'User' }}</p>
                                <p class="text-xs font-medium text-slate-400 leading-tight truncate max-w-[100px]">{{ user?.email || '' }}</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transition-transform"
                                 :class="{ 'rotate-180': showUserMenu }"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95">
                            <div v-if="showUserMenu"
                                 class="absolute right-0 mt-2 w-64 bg-slate-900/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl overflow-hidden z-50">
                                <div class="p-4 border-b border-slate-700/30" :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.08), rgba(${themeColors.primaryRgb}, 0.05));`">
                                    <div class="flex items-start gap-3 mb-3">
                                        <div v-if="user?.avatar_url" class="w-10 h-10 rounded-lg overflow-hidden border-2 flex-shrink-0" :style="`border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                            <img :src="user.avatar_url" :alt="user.name" class="w-full h-full object-cover">
                                        </div>
                                        <div v-else class="w-10 h-10 rounded-lg flex items-center justify-center text-slate-900 font-bold text-sm flex-shrink-0" :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark});`">
                                            {{ user?.initials || 'U' }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-white truncate">{{ user?.name }}</p>
                                            <p class="text-xs font-medium text-slate-400 truncate mb-1.5">{{ user?.email }}</p>
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-800/50 border border-slate-700/30">
                                                <span class="text-xs font-medium text-slate-400">{{ t('uid') }}:</span>
                                                <span class="text-xs font-mono font-semibold" :style="{ color: themeColors.primary }">{{ user?.uid || '----' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="wallet" class="grid grid-cols-2 gap-2">
                                        <div class="px-2.5 py-2 rounded-lg bg-slate-800/40 border border-slate-700/30">
                                            <p class="text-xs font-medium text-slate-400 mb-0.5">{{ t('balance') }}</p>
                                            <p class="text-sm font-bold text-white">{{ currencySymbol }}{{ formatBalance(wallet.balance) }}</p>
                                        </div>
                                        <div class="px-2.5 py-2 rounded-lg bg-slate-800/40 border border-slate-700/30">
                                            <p class="text-xs font-medium text-slate-400 mb-0.5">{{ t('totalEarning') }}</p>
                                            <p class="text-sm font-bold" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatBalance(wallet.total_earnings) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-1.5">
                                    <Link :href="'/user/settings'"
                                          @click="showUserMenu = false"
                                          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/50 hover:scale-[1.02] transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ t('setting') }}
                                    </Link>

                                    <Link :href="'/user/support-tickets'"
                                          @click="showUserMenu = false"
                                          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/50 hover:scale-[1.02] transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        {{ t('support') }}
                                    </Link>

                                    <div class="my-1 border-t border-slate-700/30"></div>

                                    <Link :href="'/logout'" method="post"
                                          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-400/10 hover:scale-[1.02] transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        {{ t('logout') }}
                                    </Link>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <button @click="toggleMobileMenu"
                            class="lg:hidden p-2 rounded-lg border transition-all duration-300 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                            :style="`background: rgba(${themeColors.primaryRgb}, 0.08); border-color: rgba(${themeColors.primaryRgb}, 0.2); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                            :aria-expanded="showMobileMenu"
                            aria-controls="mobile-menu"
                            :aria-label="t('toggleMobileNavigation')">
                        <svg class="w-5 h-5 text-white"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24"
                             aria-hidden="true">
                            <path v-if="!showMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1">
            <div v-if="showMobileMenu"
                 id="mobile-menu"
                 class="lg:hidden border-t border-slate-800/30 backdrop-blur-xl bg-slate-900/95"
                 role="navigation"
                 :aria-label="t('mobileNavigation')">
                <div v-if="user" class="px-4 pt-4 pb-3 border-b border-slate-700/30">
                    <div class="flex items-center gap-3 mb-3">
                        <div v-if="user?.avatar_url" class="w-10 h-10 rounded-lg overflow-hidden border-2" :style="`border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                            <img :src="user.avatar_url" :alt="user.name" class="w-full h-full object-cover">
                        </div>
                        <div v-else class="w-10 h-10 rounded-lg flex items-center justify-center text-slate-900 font-bold text-sm" :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark});`">
                            {{ user?.initials || 'U' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white truncate">{{ user?.name }}</p>
                            <p class="text-xs font-medium text-slate-400 truncate mb-1">{{ user?.email }}</p>
                            <div class="inline-flex items-center gap-1">
                                <span class="text-xs font-medium text-slate-500">{{ t('uid') }}:</span>
                                <span class="text-xs font-mono font-semibold" :style="{ color: themeColors.primary }">{{ user?.uid || '----' }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="wallet" class="grid grid-cols-2 gap-2">
                        <div class="px-2.5 py-2 rounded-lg border" :style="`background: rgba(${themeColors.primaryRgb}, 0.08); border-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                            <p class="text-xs font-medium text-slate-400 mb-0.5">{{ t('balance') }}</p>
                            <p class="text-sm font-bold text-white truncate">{{ currencySymbol }}{{ formatBalance(wallet.balance) }}</p>
                        </div>
                        <div class="px-2.5 py-2 rounded-lg border" :style="`background: rgba(${themeColors.primaryRgb}, 0.08); border-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                            <p class="text-xs font-medium text-slate-400 mb-0.5">{{ t('totalEarning') }}</p>
                            <p class="text-sm font-bold" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatBalance(wallet.total_earnings) }}</p>
                        </div>
                    </div>
                </div>

                <nav class="px-4 py-3 space-y-1">
                    <Link v-for="item in navigationItems"
                          :key="item.url"
                          :href="item.url"
                          @click="showMobileMenu = false"
                          :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all duration-300 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900',
                                   isActive(item.url) ? 'shadow-lg' : 'text-slate-300 hover:text-white hover:bg-slate-800/50']"
                          :style="isActive(item.url) ? `background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : ''"
                          :aria-current="isActive(item.url) ? 'page' : undefined">
                        <span class="relative z-10">{{ t(item.label) }}</span>
                    </Link>
                </nav>

                <div class="px-4 py-3 border-t border-slate-700/30 space-y-1">
                    <Link :href="'/user/settings'"
                          @click="showMobileMenu = false"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/50 hover:scale-[1.02] transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ t('setting') }}
                    </Link>

                    <Link :href="'/user/support-tickets'"
                          @click="showMobileMenu = false"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/50 hover:scale-[1.02] transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        {{ t('support') }}
                    </Link>

                    <Link :href="'/logout'" method="post"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-400/10 hover:scale-[1.02] transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        {{ t('logout') }}
                    </Link>
                </div>
            </div>
        </Transition>
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();
const page = usePage();

const showUserMenu = ref(false);
const showMobileMenu = ref(false);
const userMenuRef = ref(null);

const user = computed(() => page.props.auth?.user || null);
const wallet = computed(() => page.props.wallet || null);
const appName = computed(() => page.props.appName || 'App');
const logoUrl = computed(() => page.props.logoUrl || '');
const primaryColor = computed(() => page.props.primaryColor || '#86efac');
const currencySymbol = computed(() => page.props.currencySymbol || '$');

const navigationItems = [
    { label: 'Dashboard', url: '/user/dashboard' },
    { label: 'Investments', url: '/user/investments' },
    { label: 'Finance', url: '/user/finance' },
    { label: 'Earnings', url: '/user/earnings' },
    { label: 'Referral', url: '/user/referral' },
];

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') return { r: 134, g: 239, b: 172 };
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 };
};

const rgbToHex = (r, g, b) => "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex);
    return rgbToHex(
        Math.min(255, Math.max(0, rgb.r + amount)),
        Math.min(255, Math.max(0, rgb.g + amount)),
        Math.min(255, Math.max(0, rgb.b + amount))
    );
};

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryLight: adjustColor(primaryColor.value, 40),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}));

const isActive = (url) => page.url.startsWith(url);

const formatBalance = (balance) => {
    if (!balance) return '0.00';
    const num = parseFloat(balance);
    if (num >= 1000000) return (num / 1000000).toFixed(2) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
};

const toggleMobileMenu = () => {
    showMobileMenu.value = !showMobileMenu.value;
};

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value;
};

const handleClickOutside = (event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        showUserMenu.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slide-in-right {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulse-slow {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.1);
    }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out forwards;
}

.animate-fade-in-up {
    animation: fade-in 0.6s ease-out forwards;
}.animate-slide-in-right {
     animation: slide-in-right 0.6s ease-out forwards;
 }

.animate-pulse-slow {
    animation: pulse-slow 2s ease-in-out infinite;
}

.animation-delay-300 {
    animation-delay: 0.15s;
    opacity: 0;
}

.animation-delay-600 {
    animation-delay: 0.3s;
    opacity: 0;
}

.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

.transition {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: rgba(30, 41, 59, 0.3);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: rgba(71, 85, 105, 0.5);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(71, 85, 105, 0.7);
}

button:focus-visible,
a:focus-visible,
[tabindex]:focus-visible {
    outline: 2px solid v-bind('`rgba(${themeColors.primaryRgb}, 0.4)`');
    outline-offset: 2px;
}

body, * {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
}

.hover\:scale-\[1\.02\]:hover {
    will-change: transform;
}

@media (max-width: 768px) {
    * {
        -webkit-overflow-scrolling: touch;
    }
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in,
    .animate-fade-in-up,
    .animate-slide-in-right,
    .animate-pulse-slow,
    .transition-all,
    .hover\:scale-\[1\.02\]:hover {
        animation: none !important;
        transition: none !important;
        transform: none !important;
    }
}

@media (max-width: 1024px) {
    .animate-fade-in,
    .animate-fade-in-up,
    .animate-slide-in-right {
        animation: none;
        opacity: 1;
        transform: none;
    }
}

@media (prefers-contrast: high) {
    button,
    a {
        border-width: 2px;
    }
}

@media print {
    .hover\:scale-\[1\.02\] {
        transform: none !important;
    }

    button,
    a[href^="/"] {
        display: none;
    }

    header {
        border-bottom: 2px solid #000;
    }
}

@media (prefers-color-scheme: dark) {
    .backdrop-blur-xl {
        backdrop-filter: blur(20px) brightness(0.95);
        -webkit-backdrop-filter: blur(20px) brightness(0.95);
    }
}

@media (hover: none) and (pointer: coarse) {
    .hover\:scale-\[1\.02\]:hover {
        transform: none;
    }

    button,
    a {
        min-height: 44px;
    }
}

*:focus-visible {
    outline-offset: 3px;
    outline-width: 2px;
}

.z-50 {
    z-index: 50;
}

[aria-expanded="true"] svg {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

img {
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

button:active,
a:active {
    transform: scale(0.98);
}

@keyframes skeleton {
    0% {
        background-position: -200px 0;
    }
    100% {
        background-position: calc(200px + 100%) 0;
    }
}
</style>

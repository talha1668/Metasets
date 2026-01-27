<template>
    <UserLayout
        :page-title="t('referralLevel')"
        :page-section="t('network')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('referralLevelDashboard')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">

                <section :aria-labelledby="t('currentLevel')" class="w-full">
                    <h2 class="sr-only">{{ t('currentLevel') }}</h2>
                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300"
                        role="region" :aria-labelledby="t('currentLevel')" tabindex="0">
                        <div class="mb-5 sm:mb-6">
                            <h3 class="text-lg sm:text-xl font-bold text-white mb-1.5">{{ t('currentLevel') }}</h3>
                            <p class="text-sm font-medium text-slate-400">{{ t('yourActiveCommissionRate') }}</p>
                        </div>

                        <div class="bg-slate-800/40 rounded-lg p-5 sm:p-6 border-2"
                             :style="`border-color: ${themeColors.primary}`">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('active') }}</span>
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                                      :style="`background: ${themeColors.primary}; color: #0f172a;`">
                                    {{ t('level') }} {{ currentLevel.level }}
                                </span>
                            </div>
                            <div class="flex items-baseline gap-2.5 mb-5">
                                <span class="text-3xl sm:text-4xl font-bold" :style="{ color: themeColors.primary }">
                                    {{ currentLevel.commission_rate }}%
                                </span>
                                <span class="text-base font-semibold text-slate-400">{{ t('commission') }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-900/50 rounded-lg p-3">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('yourReferrals') }}</p>
                                    <p class="text-xl sm:text-2xl font-bold text-white">{{ userStats.activeReferrals }}</p>
                                </div>
                                <div class="bg-slate-900/50 rounded-lg p-3">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('teamInvestment') }}</p>
                                    <p class="text-xl sm:text-2xl font-bold text-white">
                                        {{ currencySymbol }}{{ formatCurrency(userStats.totalInvestment) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('levelProgress')" class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
                    <h2 class="sr-only">{{ t('levelProgress') }}</h2>

                    <div v-if="previousLevel"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300"
                         role="region" :aria-labelledby="t('previousLevel')" tabindex="0">
                        <div class="mb-5 sm:mb-6">
                            <h3 class="text-lg sm:text-xl font-bold text-white mb-1.5">{{ t('previousLevel') }}</h3>
                            <p class="text-sm font-medium text-slate-400">{{ t('completedMilestone') }}</p>
                        </div>

                        <div class="bg-slate-800/20 rounded-lg p-4 border border-slate-700/30">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('level') }} {{ previousLevel.level }}</span>
                                <span class="text-xs font-medium text-green-400">✓ {{ t('completed') }}</span>
                            </div>
                            <div class="flex items-baseline gap-2 mb-3">
                                <span class="text-xl sm:text-2xl font-bold text-slate-500">{{ previousLevel.commission_rate }}%</span>
                                <span class="text-sm font-semibold text-slate-400">{{ t('commission') }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('requiredReferrals') }}</p>
                                    <p class="text-sm font-semibold text-slate-500">{{ previousLevel.min_referrals_required }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('requiredInvestment') }}</p>
                                    <p class="text-sm font-semibold text-slate-500">
                                        {{ currencySymbol }}{{ formatCurrency(previousLevel.min_investment_required) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="nextLevel"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 hover:shadow-lg hover:shadow-900/20 transition-all duration-300"
                         :class="{ 'lg:col-span-2': !previousLevel }"
                         role="region" :aria-labelledby="t('nextLevel')" tabindex="0">
                        <div class="mb-5 sm:mb-6">
                            <h3 class="text-lg sm:text-xl font-bold text-white mb-1.5">{{ t('nextLevel') }}</h3>
                            <p class="text-sm font-medium text-slate-400">{{ t('unlockHigherCommission') }}</p>
                        </div>

                        <div class="bg-slate-800/40 rounded-lg p-5 sm:p-6 border border-slate-700/30">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('level') }} {{ nextLevel.level }}</span>
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-slate-700/50 text-slate-300">
                                    {{ t('inProgress') }}
                                </span>
                            </div>
                            <div class="flex items-baseline gap-2 mb-5">
                                <span class="text-xl sm:text-2xl font-bold text-white">
                                    {{ nextLevel.commission_rate }}%
                                </span>
                                <span class="text-sm font-semibold text-slate-400">{{ t('targetCommission') }}</span>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('referralsProgress') }}</span>
                                        <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                            {{ userStats.activeReferrals }} / {{ nextLevel.min_referrals_required }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-700/50 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500"
                                             :style="`width: ${referralsProgress}%; background: ${themeColors.primary};`">
                                        </div>
                                    </div>
                                    <p class="text-xs font-medium text-slate-500 mt-1.5">
                                        {{ referralsRemaining }} {{ t('moreReferralsNeeded') }}
                                    </p>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('investmentProgress') }}</span>
                                        <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                            {{ currencySymbol }}{{ formatCurrency(userStats.totalInvestment) }} /
                                            {{ currencySymbol }}{{ formatCurrency(nextLevel.min_investment_required) }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-700/50 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500"
                                             :style="`width: ${investmentProgress}%; background: ${themeColors.primary};`">
                                        </div>
                                    </div>
                                    <p class="text-xs font-medium text-slate-500 mt-1.5">
                                        {{ currencySymbol }}{{ formatCurrency(investmentRemaining) }} {{ t('moreInvestmentNeeded') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-t border-slate-700/30">
                                <div class="flex items-start gap-2.5">
                                    <div>
                                        <p class="text-sm font-semibold text-white mb-0.5">{{ t('requirementsToUnlock') }}</p>
                                        <p class="text-xs font-medium text-slate-400">
                                            {{ t('refer') }} {{ nextLevel.min_referrals_required }} {{ t('activeMembersAnd') }}
                                            {{ t('achieve') }} {{ currencySymbol }}{{ formatCurrency(nextLevel.min_investment_required) }}
                                            {{ t('inTeamInvestment') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!nextLevel"
                         class="lg:col-span-2 bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300"
                         role="region" :aria-labelledby="t('maxLevelReached')" tabindex="0">
                        <div class="text-center">
                            <h3 class="text-lg sm:text-xl font-bold text-white mb-1.5">{{ t('maxLevelReached') }}</h3>
                            <p class="text-sm font-medium text-slate-400">{{ t('congratulationsMaxLevel') }}</p>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import {computed, onMounted} from 'vue';
import {usePage} from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import {useTranslation} from "@/composables/useTranslation.js";

const page = usePage();
const {t} = useTranslation();

const props = defineProps({
    allLevels: Array,
    userStats: Object,
});


const primaryColor = computed(() => page.props.primaryColor || '#86efac');
const currencySymbol = computed(() => page.props.currencySymbol || '$');

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') {
        return {r: 134, g: 239, b: 172};
    }
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : {r: 134, g: 239, b: 172};
};

const rgbToHex = (r, g, b) => {
    return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
};

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex);
    const newR = Math.min(255, Math.max(0, rgb.r + amount));
    const newG = Math.min(255, Math.max(0, rgb.g + amount));
    const newB = Math.min(255, Math.max(0, rgb.b + amount));
    return rgbToHex(newR, newG, newB);
};

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryLight: adjustColor(primaryColor.value, 40),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}));

const currentLevel = computed(() => {
    const sortedLevels = [...props.allLevels].sort((a, b) => b.level - a.level);

    for (const level of sortedLevels) {
        if (props.userStats.activeReferrals >= level.min_referrals_required &&
            props.userStats.totalInvestment >= level.min_investment_required) {
            return level;
        }
    }

    return props.allLevels[0];
});

const previousLevel = computed(() => {
    if (currentLevel.value.level === 1) return null;
    return props.allLevels.find(level => level.level === currentLevel.value.level - 1);
});

const nextLevel = computed(() => {
    return props.allLevels.find(level => level.level === currentLevel.value.level + 1);
});

const referralsProgress = computed(() => {
    if (!nextLevel.value) return 100;
    return Math.min((props.userStats.activeReferrals / nextLevel.value.min_referrals_required) * 100, 100);
});

const investmentProgress = computed(() => {
    if (!nextLevel.value) return 100;
    return Math.min((props.userStats.totalInvestment / nextLevel.value.min_investment_required) * 100, 100);
});

const referralsRemaining = computed(() => {
    if (!nextLevel.value) return 0;
    return Math.max(0, nextLevel.value.min_referrals_required - props.userStats.activeReferrals);
});

const investmentRemaining = computed(() => {
    if (!nextLevel.value) return 0;
    return Math.max(0, nextLevel.value.min_investment_required - props.userStats.totalInvestment);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

onMounted(() => {
    try {
        const flash = page.props.flash;
        if (flash?.success) console.log(flash.success);
        if (flash?.error) console.error(flash.error);
        if (flash?.warning) console.warn(flash.warning);
        if (flash?.info) console.info(flash.info);
    } catch (error) {
        console.error('Component mount error:', error);
    }
});
</script>

<style scoped>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

button:focus,
[tabindex]:focus {
    outline: 2px solid v-bind('`rgba(${themeColors.primaryRgb}, 0.4)`');
    outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in-up,
    .transition-all {
        animation: none !important;
        transition: none !important;
    }
}

@media (max-width: 640px) {
    .grid {
        gap: 1rem;
    }
}

button,
a,
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

*:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

body {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
}

.hover\:scale-\[1\.01\]:hover,
.hover\:scale-\[1\.02\]:hover {
    will-change: transform;
}

@media (max-width: 768px) {
    * {
        -webkit-overflow-scrolling: touch;
    }
}

@media print {
    .hover\:scale-\[1\.01\],
    .hover\:scale-\[1\.02\] {
        transform: none !important;
    }

    button,
    a[href^="/"] {
        display: none;
    }
}
</style>

<template>
    <section
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900/70 to-slate-800/50 backdrop-blur-xl border border-slate-700/30 shadow-2xl animate-fade-in-up"
        role="region"
        aria-labelledby="investment-staking-hub-title"
    >
        <div class="absolute inset-0 opacity-10" aria-hidden="true">
            <div class="absolute inset-0" :style="`background-image: radial-gradient(circle at 1px 1px, rgba(${themeColors.primaryRgb}, 0.3) 1px, transparent 0); background-size: 32px 32px;`"></div>
        </div>

        <div class="relative z-10 p-5 sm:p-6">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6 items-center mb-5 sm:mb-6">
                <div class="xl:col-span-8 space-y-4 sm:space-y-5">
                    <div class="space-y-2 sm:space-y-3">
                        <h1 id="investment-staking-hub-title" class="text-2xl sm:text-3xl font-bold text-white leading-tight">
                            {{ t('investment') }} &
                            <span class="bg-gradient-to-r bg-clip-text text-transparent" :style="`background-image: linear-gradient(to right, ${themeColors.primary}, ${themeColors.primaryLight});`">
                                {{ t('stakingHub') }}
                            </span>
                        </h1>
                        <p class="text-sm sm:text-base font-medium text-slate-300 leading-relaxed max-w-3xl">
                            {{ t('growWealthDescription') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 sm:gap-3" role="list" :aria-label="t('platformFeatures')">
                        <div class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-lg" :style="`background-color: rgba(${themeColors.primaryRgb}, 0.1); border: 1px solid rgba(${themeColors.primaryRgb}, 0.2);`" role="listitem">
                            <div class="w-1.5 h-1.5 rounded-full animate-pulse" :style="{ backgroundColor: themeColors.primary }" aria-hidden="true"></div>
                            <span class="text-xs font-semibold" :style="{ color: themeColors.primary }">{{ stats.activeUsers.toLocaleString() }}+ {{ t('activeInvestors') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 bg-blue-400/10 border border-blue-400/20 rounded-lg" role="listitem">
                            <div class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse" aria-hidden="true"></div>
                            <span class="text-xs font-semibold text-blue-400">{{ t('support247') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 bg-purple-400/10 border border-purple-400/20 rounded-lg" role="listitem">
                            <div class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse" aria-hidden="true"></div>
                            <span class="text-xs font-semibold text-purple-400">{{ t('aiPowered') }}</span>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4">
                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                        <div
                            class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-3.5 sm:p-4 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                            tabindex="0"
                            role="region"
                            :aria-label="`${t('totalPlans')}: ${stats.totalPlans}, ${stats.activePlans} ${t('active')}`"
                        >
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 sm:mb-2">{{ t('totalPlans') }}</h3>
                            <p class="text-lg sm:text-xl font-bold text-white mb-1">{{ stats.totalPlans }}</p>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-semibold" :style="{ color: themeColors.primary }">{{ stats.activePlans }} {{ t('active') }}</span>
                            </div>
                        </div>

                        <div
                            class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-3.5 sm:p-4 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                            tabindex="0"
                            role="region"
                            :aria-label="`${t('bestApy')}: ${stats.bestApy}% ${t('fromStaking')}`"
                        >
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 sm:mb-2">{{ t('bestApy') }}</h3>
                            <p class="text-lg sm:text-xl font-bold text-white mb-1">{{ stats.bestApy }}%</p>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-semibold" :style="{ color: themeColors.primary }">{{ t('staking') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                <div
                    class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                    tabindex="0"
                    role="region"
                    :aria-label="`${t('totalInvested')}: ${currencySymbol}${formatCurrency(stats.totalInvested)}, ${t('up')} ${stats.investedGrowth}% ${t('thisMonth')}`"
                >
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ t('totalInvested') }}</h3>
                    <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">{{ currencySymbol }}{{ formatCurrency(stats.totalInvested) }}</p>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">+{{ stats.investedGrowth }}%</span>
                        <span class="text-xs font-medium text-slate-400">{{ t('thisMonth') }}</span>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                    tabindex="0"
                    role="region"
                    :aria-label="`${t('totalStaked')}: ${currencySymbol}${formatCurrency(stats.totalStaked)}, ${t('up')} ${stats.stakedGrowth}% ${t('thisMonth')}`"
                >
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ t('totalStaked') }}</h3>
                    <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">{{ currencySymbol }}{{ formatCurrency(stats.totalStaked) }}</p>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">+{{ stats.stakedGrowth }}%</span>
                        <span class="text-xs font-medium text-slate-400">{{ t('thisMonth') }}</span>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                    tabindex="0"
                    role="region"
                    :aria-label="`${t('totalReturns')}: ${currencySymbol}${formatCurrency(stats.totalReturns)}, ${t('up')} ${stats.returnsGrowth}% ${t('thisMonth')}`"
                >
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ t('totalReturns') }}</h3>
                    <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">{{ currencySymbol }}{{ formatCurrency(stats.totalReturns) }}</p>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">+{{ stats.returnsGrowth }}%</span>
                        <span class="text-xs font-medium text-slate-400">{{ t('thisMonth') }}</span>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                    tabindex="0"
                    role="region"
                    :aria-label="`${t('activeUsers')}: ${stats.activeUsers.toLocaleString()}, ${stats.newUsers} ${t('newThisWeek')}`"
                >
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ t('activeUsers') }}</h3>
                    <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">{{ stats.activeUsers.toLocaleString() }}</p>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">+{{ stats.newUsers }} {{ t('new') }}</span>
                        <span class="text-xs font-medium text-slate-400">{{ t('thisWeek') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="absolute top-3 right-3 w-16 h-16 sm:w-20 sm:h-20 rounded-full blur-xl" :style="`background: linear-gradient(to bottom right, rgba(${themeColors.primaryRgb}, 0.1), rgba(${themeColors.primaryRgb}, 0.05));`" aria-hidden="true"></div>
        <div class="absolute bottom-3 left-3 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gradient-to-br from-purple-400/10 to-violet-500/10 blur-xl" aria-hidden="true"></div>
    </section>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        default: () => ({
            totalInvested: 2450000,
            totalStaked: 1850000,
            totalReturns: 485000,
            activeUsers: 1247,
            totalPlans: 12,
            activePlans: 8,
            bestApy: 18.7,
            investedGrowth: 12.5,
            stakedGrowth: 8.3,
            returnsGrowth: 24.1,
            newUsers: 156
        })
    }
})

const page = usePage()
const { t } = useTranslation()

const primaryColor = computed(() => page.props.primaryColor || '#86efac')
const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 }
}

const rgbToHex = (r, g, b) => {
    return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)
}

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex)
    const newR = Math.min(255, Math.max(0, rgb.r + amount))
    const newG = Math.min(255, Math.max(0, rgb.g + amount))
    const newB = Math.min(255, Math.max(0, rgb.b + amount))
    return rgbToHex(newR, newG, newB)
}

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryLight: adjustColor(primaryColor.value, 40),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}))

const currencySymbol = computed(() => page.props.currencySymbol || '$')
const formatCurrency = (amount) => {
    if (amount >= 1000000) {
        return (amount / 1000000).toFixed(1) + 'M'
    } else if (amount >= 1000) {
        return (amount / 1000).toFixed(0) + 'K'
    }
    return amount.toLocaleString()
}
</script>

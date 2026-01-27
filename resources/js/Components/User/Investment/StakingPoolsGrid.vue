<template>
    <section class="space-y-5 sm:space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 sm:mb-6">
            <div class="flex-1">
                <h2 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('stakingPools') }}</h2>
                <p class="text-sm font-medium text-slate-400">{{ t('earnPassiveIncome') }}</p>
            </div>
            <div class="px-3 py-1.5 rounded-lg border" :style="`background: rgba(${themeColors.primaryRgb}, 0.1); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                <span class="text-xs font-semibold" :style="{ color: themeColors.primary }">{{ pools.length }} {{ t('available') }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 lg:gap-5">
            <div
                v-for="(pool, index) in pools"
                :key="pool.id"
                class="group relative bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl overflow-hidden hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 animate-fade-in-up"
                :style="`animation-delay: ${index * 100}ms`"
                role="region"
                :aria-labelledby="`pool-title-${pool.id}`"
                tabindex="0"
            >
                <div
                    v-if="pool.apy_rate >= 15"
                    class="absolute top-3 right-3 z-20 px-2.5 py-1 rounded-lg text-xs font-semibold border shadow-lg"
                    :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); color: #0f172a; border-color: rgba(${themeColors.primaryRgb}, 0.3);`"
                >
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        {{ t('highYield').toUpperCase() }}
                    </span>
                </div>

                <div class="relative h-full flex flex-col z-10 p-4 sm:p-5">
                    <div class="flex items-start justify-between mb-3 sm:mb-4">
                        <div class="flex-1 min-w-0 mr-3">
                            <h3 :id="`pool-title-${pool.id}`" class="text-lg sm:text-xl font-bold text-white mb-1 truncate">{{ pool.name }}</h3>
                            <p class="text-xs font-medium text-slate-400">{{ pool.token_symbol }} {{ t('staking') }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-lg sm:text-xl font-bold" :style="{ color: themeColors.primary }" :aria-label="`${pool.apy_rate}% ${t('apyRate')}`">
                                {{ pool.apy_rate }}%
                            </p>
                            <p class="text-xs font-medium text-slate-400">{{ t('apy') }}</p>
                        </div>
                    </div>

                    <p class="text-sm font-medium text-slate-300 leading-relaxed mb-3 sm:mb-4 line-clamp-2">{{ pool.description }}</p>
                    <div class="flex flex-wrap gap-1.5 mb-3 sm:mb-4">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-lg border" :class="getRiskClass(pool.risk_level)">
                            {{ t(pool.risk_level) }} {{ t('risk') }}
                        </span>
                        <span v-if="pool.auto_compound" class="px-2.5 py-1 text-xs font-semibold rounded-lg border" :style="`background-color: rgba(${themeColors.primaryRgb}, 0.1); border-color: rgba(${themeColors.primaryRgb}, 0.3); color: ${themeColors.primary};`">
                            {{ t('autoCompound') }}
                        </span>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-lg border" :style="`background-color: rgba(${themeColors.primaryRgb}, 0.1); border-color: rgba(${themeColors.primaryRgb}, 0.3); color: ${themeColors.primary};`">
                            {{ pool.lock_days === 0 ? t('flexible') : `${pool.lock_days} ${t('days')}` }}
                        </span>
                    </div>

                    <div class="space-y-2.5 mb-3 sm:mb-4 p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('minStake') }}</span>
                            <span class="text-sm font-semibold text-white">
                                {{ currencySymbol }}{{ formatCurrency(pool.min_stake_amount) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('lockPeriod') }}</span>
                            <span class="text-sm font-semibold text-white">
                                {{ pool.lock_days === 0 ? t('flexible') : `${pool.lock_days} ${t('days')}` }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('apyRate') }}</span>
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ pool.apy_rate }}%
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3 mb-3 sm:mb-4">
                        <div class="bg-slate-800/40 rounded-lg p-3 border border-slate-700/30 hover:bg-slate-800/60 transition-colors">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('totalStaked') }}</h4>
                            <p class="text-base sm:text-lg font-bold text-white mb-1">
                                {{ currencySymbol }}{{ formatCurrency(pool.current_staked || 0) }}
                            </p>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="text-xs font-semibold"
                                    :class="pool.staking_growth_this_month >= 0 ? '' : 'text-red-400'"
                                    :style="pool.staking_growth_this_month >= 0 ? { color: themeColors.primary } : {}"
                                >
                                    {{ pool.staking_growth_this_month >= 0 ? '+' : '' }}{{ pool.staking_growth_this_month }}%
                                </span>
                                <span class="text-xs font-medium text-slate-400">{{ t('thisMonth') }}</span>
                            </div>
                        </div>
                        <div class="bg-slate-800/40 rounded-lg p-3 border border-slate-700/30 hover:bg-slate-800/60 transition-colors">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('poolUsed') }}</h4>
                            <p class="text-base sm:text-lg font-bold text-white mb-1">
                                {{ calculateUtilization(pool) }}%
                            </p>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-semibold" :style="{ color: themeColors.primary }">{{ t('available') }}</span>
                                <span class="text-xs font-medium text-slate-400">{{ t('spaceLeft') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 sm:mb-4">
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="text-slate-400 font-medium">{{ t('poolCapacity') }}</span>
                            <span class="text-white font-semibold">{{ calculateUtilization(pool) }}%</span>
                        </div>
                        <div
                            class="relative w-full bg-slate-700/50 rounded-full h-2 overflow-hidden"
                            role="progressbar"
                            :aria-valuenow="calculateUtilization(pool)"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-label="`${t('poolCapacity')}: ${calculateUtilization(pool)}%`"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-1000"
                                :style="`width: ${calculateUtilization(pool)}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark});`"
                            ></div>
                        </div>
                    </div>

                    <button
                        @click="$emit('selectPool', pool)"
                        class="w-full py-2.5 sm:py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 shadow-lg"
                        :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                        :aria-label="`${t('stakeIn')} ${pool.name}`"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                            </svg>
                            {{ t('stakeNow') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="!pools || pools.length === 0" class="text-center py-12 sm:py-16">
            <svg class="w-14 h-14 sm:w-16 sm:h-16 mx-auto mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
            </svg>
            <p class="text-sm font-medium text-slate-400">{{ t('noStakingPoolsAvailable') }}</p>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'

const props = defineProps({
    pools: {
        type: Array,
        default: () => []
    }
})

defineEmits(['selectPool'])

const page = usePage()
const { t } = useTranslation()

const primaryColor = computed(() => page.props.primaryColor || '#86efac')
const currencySymbol = computed(() => page.props.currencySymbol || '$')

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') return { r: 134, g: 239, b: 172 }
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 }
}

const rgbToHex = (r, g, b) => "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex)
    return rgbToHex(
        Math.min(255, Math.max(0, rgb.r + amount)),
        Math.min(255, Math.max(0, rgb.g + amount)),
        Math.min(255, Math.max(0, rgb.b + amount))
    )
}

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryLight: adjustColor(primaryColor.value, 40),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}))

const formatCurrency = (amount) => {
    const number = parseFloat(amount) || 0
    if (number >= 1000000) return (number / 1000000).toFixed(1) + 'M'
    if (number >= 1000) return (number / 1000).toFixed(0) + 'K'
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number)
}

const calculateUtilization = (pool) => {
    if (!pool.total_pool_size || pool.total_pool_size === 0) return 0
    return Math.min(Math.round(((pool.current_staked || 0) / pool.total_pool_size) * 100), 100)
}

const getRiskClass = (risk) => {
    switch (risk?.toLowerCase()) {
        case 'low':
            return 'bg-green-400/10 text-green-400 border-green-400/30'
        case 'medium':
            return 'bg-yellow-400/10 text-yellow-400 border-yellow-400/30'
        case 'high':
            return 'bg-red-400/10 text-red-400 border-red-400/30'
        default:
            return 'bg-slate-400/10 text-slate-400 border-slate-400/30'
    }
}
</script>

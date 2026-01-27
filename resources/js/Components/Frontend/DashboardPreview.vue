<template>
    <div class="relative h-full">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-xs mx-auto backdrop-blur-2xl border rounded-xl sm:rounded-2xl p-4 sm:p-6 transition-all duration-500 hover:scale-[1.02] animate-float-gentle"
            :style="dashboardStyle"
        >
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-black mb-1.5 text-white">{{ t('portfolioOverview') }}</h3>
                    <p class="text-white/70 text-xs sm:text-sm font-normal">{{ t('realTimeAssetManagement') }}</p>
                </div>
                <div
                    class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl flex items-center justify-center animate-pulse-slow"
                    :style="dashboardIconStyle">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                        <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
                    </svg>
                </div>
            </div>

            <div class="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
                <div class="p-2.5 sm:p-3 rounded-lg sm:rounded-xl transition-all duration-300 hover:scale-[1.02]"
                     :style="portfolioCardStyle">
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-white/70 text-xs sm:text-sm font-semibold">{{ t('totalPortfolioValue') }}</span>
                        <span class="text-xs font-bold animate-pulse"
                              :style="{ color: themeColors.primary }">+{{ portfolioGrowth }}%</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black text-white">{{ currencySymbol }}{{ portfolioValue.toLocaleString() }}</span>
                </div>

                <div class="p-2.5 sm:p-3 rounded-lg sm:rounded-xl transition-all duration-300 hover:scale-[1.02]"
                     :style="yieldCardStyle">
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-white/70 text-xs sm:text-sm font-semibold">{{ t('dailyYield24h') }}</span>
                        <span class="text-xs font-bold animate-pulse"
                              :style="{ color: themeColors.primary }">{{ t('active') }}</span>
                    </div>
                    <span class="text-lg sm:text-xl font-black text-white">+{{ currencySymbol }}{{ dailyYield.toLocaleString() }}</span>
                </div>

                <div class="p-2.5 sm:p-3 rounded-lg sm:rounded-xl transition-all duration-300 hover:scale-[1.02]"
                     :style="rewardsCardStyle">
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-white/70 text-xs sm:text-sm font-semibold">{{ t('stakingRewards') }}</span>
                        <span class="text-xs font-bold" :style="{ color: themeColors.primary }">{{ stakingRewards.apy }}% {{ t('apy') }}</span>
                    </div>
                    <span class="text-lg sm:text-xl font-black text-white">{{ stakingRewards.amount }} ETH</span>
                </div>
            </div>

            <div
                class="h-14 sm:h-16 rounded-lg sm:rounded-xl relative overflow-hidden mb-3 sm:mb-4 group cursor-pointer"
                :style="chartContainerStyle">
                <div class="absolute inset-2 sm:inset-3">
                    <div class="w-full h-full rounded-md transition-all duration-300 group-hover:scale-105"
                         :style="chartStyle" ref="profitChart"></div>
                </div>
                <div class="absolute top-1.5 sm:top-2 left-2 sm:left-3 text-[10px] sm:text-xs text-white/70 font-medium">
                    {{ t('growthTrend') }}
                </div>
                <div class="absolute bottom-1.5 right-2 sm:right-3 text-xs font-bold"
                     :style="{ color: themeColors.primary }">+{{ trendPercentage }}%
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <div class="w-1.5 h-1.5 rounded-full animate-pulse"
                         :style="{ backgroundColor: themeColors.primary }"></div>
                    <span class="text-xs font-medium text-white/80">{{ t('portfolioActive') }}</span>
                </div>
                <span class="text-xs text-white/70">{{ t('updated') }} {{ lastUpdate }}{{ t('secondsAgo') }}</span>
            </div>
        </div>

        <div
            class="absolute top-3 sm:top-6 right-0 w-52 sm:w-56 backdrop-blur-2xl border rounded-lg sm:rounded-xl p-3 sm:p-4 transition-all duration-500 hover:scale-[1.02] animation-delay-1000 animate-slide-in-right"
            :style="opportunitiesStyle">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <h3 class="text-base sm:text-lg font-black text-white">{{ t('liveOpportunities') }}</h3>
                <div
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-md sm:rounded-lg flex items-center justify-center animate-bounce-slow"
                    :style="opportunityIconStyle">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>

            <div class="space-y-2 sm:space-y-3">
                <div
                    class="p-2 rounded-md sm:rounded-lg border border-white/10 transition-all hover:border-white/30 hover:scale-[1.02] group cursor-pointer"
                    :style="opportunityItemStyle">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-md bg-blue-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <span class="text-blue-400 text-[10px] sm:text-xs font-black">ETH</span>
                            </div>
                            <span class="text-white text-xs sm:text-sm font-semibold">{{ t('liquidStaking') }}</span>
                        </div>
                        <span class="text-blue-400 font-black text-xs">4.2% {{ t('apy') }}</span>
                    </div>
                </div>

                <div
                    class="p-2 rounded-md sm:rounded-lg border border-white/10 transition-all hover:border-white/30 hover:scale-[1.02] group cursor-pointer"
                    :style="opportunityItemStyle">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-md bg-purple-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <span class="text-purple-400 text-[10px] sm:text-xs font-black">RWA</span>
                            </div>
                            <span class="text-white text-xs sm:text-sm font-semibold">{{ t('realEstate') }}</span>
                        </div>
                        <span class="text-purple-400 font-black text-xs">8.5% {{ t('apy') }}</span>
                    </div>
                </div>

                <div
                    class="p-2 rounded-md sm:rounded-lg border border-white/10 transition-all hover:border-white/30 hover:scale-[1.02] group cursor-pointer"
                    :style="opportunityItemStyle">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-md flex items-center justify-center group-hover:scale-110 transition-transform"
                                :style="{ background: `rgba(${themeColors.primaryRgb}, 0.2)` }">
                                <span class="text-[10px] sm:text-xs font-black"
                                      :style="{ color: themeColors.primary }">DFI</span>
                            </div>
                            <span class="text-white text-xs sm:text-sm font-semibold">{{ t('yieldFarming') }}</span>
                        </div>
                        <span class="font-black text-xs" :style="{ color: themeColors.primary }">15.3% {{ t('apy') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="absolute bottom-3 sm:bottom-6 left-0 w-44 sm:w-48 backdrop-blur-2xl border rounded-lg sm:rounded-xl p-3 sm:p-4 transition-all duration-500 hover:scale-[1.02] animation-delay-2000 animate-slide-in-left"
            :style="statsCardStyle">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <h3 class="text-base sm:text-lg font-black text-white">{{ t('platformStats') }}</h3>
                <div
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-md sm:rounded-lg flex items-center justify-center animate-pulse-slow"
                    :style="statsIconStyle">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>

            <div class="text-center space-y-3">
                <div class="group hover:scale-105 transition-transform cursor-pointer">
                    <div class="text-xl sm:text-2xl font-black mb-0.5"
                         :style="{ background: primaryGradient, WebkitBackgroundClip: 'text', backgroundClip: 'text', color: 'transparent' }">
                        {{ currencySymbol }}{{ stats.tvl }}M+
                    </div>
                    <div class="text-xs text-white/70 font-medium">{{ t('totalValueLocked') }}</div>
                </div>

                <div class="group hover:scale-105 transition-transform cursor-pointer">
                    <div class="text-lg sm:text-xl font-black mb-0.5"
                         :style="{ background: primaryGradient, WebkitBackgroundClip: 'text', backgroundClip: 'text', color: 'transparent' }">
                        {{ stats.users }}K+
                    </div>
                    <div class="text-xs text-white/70 font-medium">{{ t('activeWallets') }}</div>
                </div>

                <div class="group hover:scale-105 transition-transform cursor-pointer">
                    <div class="text-base sm:text-lg font-black mb-0.5"
                         :style="{ background: secondaryGradient, WebkitBackgroundClip: 'text', backgroundClip: 'text', color: 'transparent' }">
                        {{ stats.transactions }}M+
                    </div>
                    <div class="text-xs text-white/70 font-medium">{{ t('transactions') }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, onMounted, computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import {useTranslation} from '@/composables/useTranslation'

defineProps({
    portfolioValue: {
        type: Number,
        required: true
    },
    dailyYield: {
        type: Number,
        required: true
    },
    portfolioGrowth: {
        type: [String, Number],
        required: true
    },
    trendPercentage: {
        type: [String, Number],
        required: true
    },
    lastUpdate: {
        type: Number,
        required: true
    },
    stakingRewards: {
        type: Object,
        required: true
    },
    stats: {
        type: Object,
        required: true
    }
})

const page = usePage()
const {t} = useTranslation()
const profitChart = ref(null)

const primaryColor = computed(() => page.props.primaryColor || '#86efac')
const currencySymbol = computed(() => page.props.currencySymbol || '$')

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

const primaryGradient = computed(() =>
    `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`
)

const secondaryGradient = computed(() =>
    `linear-gradient(135deg, ${themeColors.value.primaryDark} 0%, ${adjustColor(themeColors.value.primaryDark, -20)} 100%)`
)

const dashboardStyle = computed(() => ({
    background: 'rgba(15, 15, 35, 0.95)',
    borderColor: 'rgba(255, 255, 255, 0.3)',
    backdropFilter: 'blur(30px)',
    boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.4)'
}))

const dashboardIconStyle = computed(() => ({
    background: primaryGradient.value
}))

const portfolioCardStyle = computed(() => ({
    background: `rgba(${themeColors.value.primaryRgb}, 0.15)`,
    borderColor: `rgba(${themeColors.value.primaryRgb}, 0.3)`,
    border: '1px solid'
}))

const yieldCardStyle = computed(() => {
    const darkRgb = hexToRgb(themeColors.value.primaryDark)
    return {
        background: `rgba(${darkRgb.r}, ${darkRgb.g}, ${darkRgb.b}, 0.15)`,
        borderColor: `rgba(${darkRgb.r}, ${darkRgb.g}, ${darkRgb.b}, 0.3)`,
        border: '1px solid'
    }
})

const rewardsCardStyle = computed(() => {
    const lightRgb = hexToRgb(themeColors.value.primaryLight)
    return {
        background: `rgba(${lightRgb.r}, ${lightRgb.g}, ${lightRgb.b}, 0.15)`,
        borderColor: `rgba(${lightRgb.r}, ${lightRgb.g}, ${lightRgb.b}, 0.3)`,
        border: '1px solid'
    }
})

const chartContainerStyle = computed(() => ({
    background: 'rgba(255, 255, 255, 0.08)',
    borderColor: 'rgba(255, 255, 255, 0.15)',
    border: '1px solid'
}))

const chartStyle = computed(() => ({
    background: primaryGradient.value,
    borderRadius: '6px',
    opacity: '0.9'
}))

const opportunitiesStyle = computed(() => ({
    background: 'rgba(15, 15, 35, 0.95)',
    borderColor: 'rgba(255, 255, 255, 0.3)',
    backdropFilter: 'blur(30px)'
}))

const opportunityIconStyle = computed(() => ({
    background: primaryGradient.value
}))

const opportunityItemStyle = computed(() => ({
    background: 'rgba(255, 255, 255, 0.05)'
}))

const statsCardStyle = computed(() => ({
    background: 'rgba(15, 15, 35, 0.95)',
    borderColor: 'rgba(255, 255, 255, 0.3)',
    backdropFilter: 'blur(30px)'
}))

const statsIconStyle = computed(() => ({
    background: primaryGradient.value
}))

const initProfitChart = () => {
    if (!profitChart.value) return

    let growth = 0
    let amplitude = 25
    let frequency = 0.008

    function animate() {
        growth += frequency
        const height = Math.sin(growth) * amplitude + 65
        const opacity = Math.sin(growth * 0.5) * 0.2 + 0.8

        profitChart.value.style.height = height + '%'
        profitChart.value.style.opacity = opacity

        requestAnimationFrame(animate)
    }

    animate()
}

onMounted(() => {
    initProfitChart()
})
</script>

<style scoped>
@keyframes float-gentle {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-8px);
    }
}

@keyframes slide-in-right {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slide-in-left {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulse-slow {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

@keyframes bounce-slow {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

.animate-float-gentle {
    animation: float-gentle 6s ease-in-out infinite;
}

.animate-slide-in-right {
    animation: slide-in-right 0.8s ease-out forwards;
}

.animate-slide-in-left {
    animation: slide-in-left 0.8s ease-out forwards;
}

.animate-pulse-slow {
    animation: pulse-slow 3s ease-in-out infinite;
}

.animate-bounce-slow {
    animation: bounce-slow 2s ease-in-out infinite;
}

.animation-delay-1000 {
    animation-delay: 1s;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

@media (prefers-reduced-motion: reduce) {
    .animate-float-gentle,
    .animate-slide-in-right,
    .animate-slide-in-left,
    .animate-pulse-slow,
    .animate-bounce-slow {
        animation: none !important;
    }
}
</style>

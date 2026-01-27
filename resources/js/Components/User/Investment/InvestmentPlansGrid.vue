<template>
    <section class="space-y-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('investmentPlans') }}</h2>
                <p class="text-sm font-medium text-slate-400">{{ t('choosePerfectPlan') }}</p>
            </div>
            <div class="px-3 py-1.5 rounded-lg" :style="`background: linear-gradient(to right, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.05)); border: 1px solid rgba(${themeColors.primaryRgb}, 0.3);`">
                <span class="text-xs font-semibold" :style="{ color: themeColors.primary }">{{ activePlans.length }} {{ t('available') }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-8">
            <div
                v-for="(plan, index) in plans"
                :key="plan.id"
                :id="`investment-plan-${plan.id}`"
                :class="['group relative bg-gradient-to-br from-slate-900/70 to-slate-800/50 backdrop-blur-xl border border-slate-700/30 rounded-xl overflow-hidden shadow-2xl hover:scale-[1.02] transition-all duration-500 focus-within:ring-2 animate-fade-in-up', { 'highlighted-plan': plan.id === highlightedPlanId }]"
                :style="`animation-delay: ${index * 100}ms; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.2);`"
                role="region"
                :aria-labelledby="`plan-title-${plan.id}`"
                tabindex="0"
            >
                <div
                    v-if="plan.featured"
                    class="absolute top-3 right-3 z-20 px-2.5 py-1 bg-gradient-to-r from-amber-400/90 to-yellow-500/90 backdrop-blur-sm border border-amber-300/30 text-amber-900 text-xs font-bold rounded-lg shadow-lg"
                >
                <span class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    {{ t('featured').toUpperCase() }}
                </span>
                </div>

                <div
                    v-if="plan.status === 'coming_soon' && plan.featured"
                    class="absolute top-3 left-3 z-20 px-2.5 py-1 bg-gradient-to-r from-slate-700/90 to-slate-600/90 backdrop-blur-sm border border-slate-500/30 text-slate-200 text-xs font-bold rounded-lg shadow-lg"
                >
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ t('comingSoon').toUpperCase() }}
                    </span>
                </div>

                <div class="relative h-full flex flex-col z-10 p-3.5">
                    <div v-if="plan.status === 'coming_soon' && !plan.featured" class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-700/50 border border-slate-600/30 text-slate-300 text-xs font-semibold rounded-lg mb-2.5 w-fit">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ t('comingSoon').toUpperCase() }}
                    </div>

                    <div class="flex items-center justify-between mb-1.5">
                        <p class="text-xs font-medium text-slate-300">{{ t(plan.return_type) }} {{ t('returns') }}</p>
                        <p
                            class="text-xl sm:text-2xl font-bold text-white"
                            :aria-label="`${plan.interest_rate}% ${t(plan.return_type)} ${t('interestRate')}`"
                        >
                            {{ plan.interest_rate }}%
                        </p>
                    </div>

                    <h3 :id="`plan-title-${plan.id}`" class="text-lg sm:text-xl font-bold text-white mb-2">{{ plan.name }}</h3>
                    <p class="text-sm sm:text-base font-medium text-slate-300 leading-relaxed mb-3 flex-grow line-clamp-2">{{ plan.description }}</p>
                    <div class="mb-3" v-if="plan.features && plan.features.length">
                        <div class="flex flex-wrap gap-1.5">
                    <span
                        v-for="feature in plan.features"
                        :key="feature"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg border"
                        :style="`background-color: rgba(${themeColors.primaryRgb}, 0.1); border-color: rgba(${themeColors.primaryRgb}, 0.2); color: ${themeColors.primary};`"
                    >
                        {{ feature }}
                    </span>
                        </div>
                    </div>

                    <div class="space-y-2.5 mb-3 p-2.5 bg-gradient-to-br from-slate-900/70 to-slate-800/50 backdrop-blur-xl border border-slate-700/30 rounded-lg hover:scale-[1.02] transition-all duration-500 shadow-2xl">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">{{ t('range') }}</span>
                            <span class="text-sm font-semibold text-white">
                        {{ currencySymbol }}{{ formatCurrency(plan.min_amount) }} - {{ currencySymbol }}{{ formatCurrency(plan.max_amount) }}
                    </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">{{ t('duration') }}</span>
                            <span class="text-sm font-semibold text-white">{{ plan.duration_days }} {{ t('days') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">{{ t('principal') }}</span>
                            <span class="text-sm font-semibold text-white capitalize">
                        {{ t(plan.principal_return.replace('_', '')) }}
                    </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 mb-3">
                        <div class="bg-gradient-to-br from-slate-900/70 to-slate-800/50 backdrop-blur-xl border border-slate-700/30 rounded-lg p-2.5 hover:scale-[1.02] transition-all duration-500 shadow-2xl">
                            <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">{{ t('invested') }}</h3>
                            <p class="text-base sm:text-lg font-bold text-white mb-1">
                                {{ currencySymbol }}{{ formatCurrency(plan.current_invested || 0) }}
                            </p>
                            <div class="flex items-center gap-1.5">
            <span
                class="text-xs font-semibold"
                :class="plan.investment_growth_this_month >= 0 ? '' : 'text-red-400'"
                :style="plan.investment_growth_this_month >= 0 ? { color: themeColors.primary } : {}"
            >
                {{ plan.investment_growth_this_month >= 0 ? '+' : '' }}{{ plan.investment_growth_this_month }}%
            </span>
                                <span class="text-xs font-medium text-slate-400">{{ t('thisMonth') }}</span>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-slate-900/70 to-slate-800/50 backdrop-blur-xl border border-slate-700/30 rounded-lg p-2.5 hover:scale-[1.02] transition-all duration-500 shadow-2xl">
                            <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">{{ t('investors') }}</h3>
                            <p class="text-base sm:text-lg font-bold text-white mb-1">
                                {{ plan.current_investors || 0 }}
                            </p>
                            <div class="flex items-center gap-1.5">
            <span class="text-xs font-semibold text-purple-400">
                +{{ plan.new_investors_this_week || 0 }} {{ t('new') }}
            </span>
                                <span class="text-xs font-medium text-slate-400">{{ t('thisWeek') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="text-slate-300 font-medium">{{ t('capacity') }}</span>
                            <span class="text-white font-semibold">{{ getCapacityPercentage(plan) }}%</span>
                        </div>
                        <div
                            class="relative w-full bg-slate-700/50 rounded-full h-2 overflow-hidden"
                            role="progressbar"
                            :aria-valuenow="getCapacityPercentage(plan)"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-label="`${t('capacity')}: ${getCapacityPercentage(plan)}%`"
                        >
                            <div
                                class="h-2 rounded-full transition-all duration-1000"
                                :style="`width: ${getCapacityPercentage(plan)}%; background: linear-gradient(to right, ${themeColors.primary}, ${themeColors.primaryLight});`"
                            ></div>
                        </div>
                    </div>

                    <button
                        @click="$emit('selectPlan', plan)"
                        :disabled="plan.status === 'coming_soon'"
                        class="w-full py-2.5 sm:py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 shadow-lg"
                        :style="plan.status === 'coming_soon'
                    ? 'background: #64748b; color: #ffffff;'
                    : `background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                        :aria-label="plan.status === 'coming_soon' ? t('planComingSoon') : `${t('investIn')} ${plan.name}`"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg v-if="plan.status === 'coming_soon'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            {{ plan.status === 'coming_soon' ? t('comingSoon') : t('investNow') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    plans: {
        type: Array,
        default: () => []
    },
    highlightedPlanId: {
        type: Number,
        default: null
    }
});

const emit = defineEmits(['selectPlan'])
const page = usePage()
const { t } = useTranslation()
const { success, error } = useToast()

const currencySymbol = computed(() => page.props.currencySymbol || '$')
const primaryColor = computed(() => page.props.primaryColor || '#86efac')

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') {
        return { r: 134, g: 239, b: 172 };
    }
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 };
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

const activePlans = computed(() => {
    return props.plans.filter(plan => plan.status === 'active')
})

const formatCurrency = (amount) => {
    if (!amount || isNaN(amount)) return '0'
    if (amount >= 1000000) {
        return (amount / 1000000).toFixed(1) + 'M'
    } else if (amount >= 1000) {
        return (amount / 1000).toFixed(0) + 'K'
    }
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount)
}

const getCapacityPercentage = (plan) => {
    if (!plan.max_investors || plan.max_investors === 0) return 0
    const percentage = Math.min(Math.round(((plan.current_investors || 0) / plan.max_investors) * 100), 100)
    return isNaN(percentage) ? 0 : percentage
}

const flash = computed(() => page.props.flash);

onMounted(() => {
    if (flash.value?.success) {
        success(flash.value.success);
    } else if (flash.value?.error) {
        error(flash.value.error);
    }
});
</script>

<style scoped>
.highlighted-plan {
    animation: pulse-highlight 2s ease-in-out;
}

@keyframes pulse-highlight {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(134, 239, 172, 0);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 0 0 10px rgba(134, 239, 172, 0.2);
    }
}
</style>

<template>
    <UserLayout
        :page-title="recommendation?.title || t('recommendationDetails')"
        :page-section="t('aiRecommendations')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8">
            <div class="max-w-5xl mx-auto space-y-5 sm:space-y-6">
                <div class="animate-fade-in">
                    <Link
                        href="/user/ai-recommendations"
                        class="inline-flex items-center text-sm font-semibold text-slate-400 hover:text-white transition-colors duration-200"
                    >
                        <svg class="w-4.5 h-4.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ t('backToRecommendations') }}
                    </Link>
                </div>

                <section class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl overflow-hidden shadow-2xl animate-fade-in animation-delay-200">
                    <div class="p-5 sm:p-6 border-b border-slate-700/40">
                        <div class="flex items-start justify-between gap-5 mb-5">
                            <div class="flex items-start gap-3 flex-1">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg flex items-center justify-center flex-shrink-0"
                                     :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.3), rgba(${themeColors.primaryRgb}, 0.1));`">
                                    <svg v-if="recommendation.type === 'investment_plan'" class="w-6 h-6 sm:w-7 sm:h-7" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                    <svg v-else class="w-6 h-6 sm:w-7 sm:h-7" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h1 class="text-xl sm:text-2xl font-bold text-white mb-2">
                                        {{ recommendation.title }}
                                    </h1>
                                    <p class="text-sm font-medium text-slate-400">
                                        {{ t('generatedOn') }} {{ formatDate(recommendation.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5 flex-shrink-0">
                                <span class="inline-flex items-center px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg font-bold text-xs capitalize"
                                      :class="getStatusBadgeClass(recommendation.status)">
                                    {{ recommendation.status }}
                                </span>
                                <span class="inline-flex items-center px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg font-bold text-xs capitalize"
                                      :class="getPriorityBadgeClass(recommendation.priority)">
                                    {{ recommendation.priority }} {{ t('priority') }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-slate-300">
                                    {{ t('confidenceScore') }}
                                </span>
                                <span class="text-lg sm:text-xl font-bold" :style="{ color: themeColors.primary }">
                                    {{ recommendation.confidence_score }}%
                                </span>
                            </div>
                            <div class="w-full bg-slate-700/50 rounded-full h-2 sm:h-2.5 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-1000"
                                    :style="`width: ${recommendation.confidence_score}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark})`"
                                ></div>
                            </div>
                            <p class="text-xs font-medium text-slate-400">
                                {{ getConfidenceLabel(recommendation.confidence_score) }}
                            </p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 border-b border-slate-700/40">
                        <h2 class="text-base sm:text-lg font-bold text-white mb-3 flex items-center">
                            <div class="w-1.5 h-1.5 rounded-full mr-2.5" :style="{ backgroundColor: themeColors.primary }"></div>
                            {{ t('description') }}
                        </h2>
                        <p class="text-sm font-medium text-slate-300 leading-relaxed">
                            {{ recommendation.description }}
                        </p>
                    </div>

                    <div v-if="recommendation.type === 'investment_plan' && investmentPlan" class="p-5 sm:p-6 border-b border-slate-700/40">
                        <h2 class="text-base sm:text-lg font-bold text-white mb-5 flex items-center">
                            <div class="w-1.5 h-1.5 rounded-full mr-2.5" :style="{ backgroundColor: themeColors.primary }"></div>
                            {{ t('recommendedPlan') }}
                        </h2>

                        <div class="bg-slate-800/40 rounded-lg p-4 sm:p-5 mb-5">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg sm:text-xl font-bold text-white">{{ investmentPlan.name }}</h3>
                                <span v-if="investmentPlan.featured"
                                      class="px-2.5 py-1 rounded-full text-xs font-bold"
                                      :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); color: ${themeColors.primary};`">
                                    {{ t('featured') }}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-slate-300 mb-5">
                                {{ investmentPlan.description }}
                            </p>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-3">
                                <div class="p-3 bg-slate-900/60 rounded-lg">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                                        {{ t('interestRate') }}
                                    </p>
                                    <p class="text-lg sm:text-xl font-bold" :style="{ color: themeColors.primary }">
                                        {{ investmentPlan.interest_rate }}%
                                    </p>
                                    <p class="text-xs font-medium text-slate-400 mt-0.5">
                                        {{ investmentPlan.return_type }}
                                    </p>
                                </div>
                                <div class="p-3 bg-slate-900/60 rounded-lg">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                                        {{ t('duration') }}
                                    </p>
                                    <p class="text-lg sm:text-xl font-bold text-white">
                                        {{ investmentPlan.duration_days }}
                                    </p>
                                    <p class="text-xs font-medium text-slate-400 mt-0.5">
                                        {{ t('days') }}
                                    </p>
                                </div>
                                <div class="p-3 bg-slate-900/60 rounded-lg">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                                        {{ t('minInvestment') }}
                                    </p>
                                    <p class="text-lg sm:text-xl font-bold text-white">
                                        {{ currencySymbol }}{{ formatCurrency(investmentPlan.min_amount) }}
                                    </p>
                                </div>
                                <div class="p-3 bg-slate-900/60 rounded-lg">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                                        {{ t('riskLevel') }}
                                    </p>
                                    <p class="text-lg sm:text-xl font-bold capitalize"
                                       :class="getRiskLevelClass(investmentPlan.risk_level)">
                                        {{ investmentPlan.risk_level }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 border-b border-slate-700/40">
                        <h2 class="text-base sm:text-lg font-bold text-white mb-5 flex items-center">
                            <div class="w-1.5 h-1.5 rounded-full mr-2.5" :style="{ backgroundColor: themeColors.primary }"></div>
                            {{ t('keyInsights') }}
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-5">
                            <div v-if="recommendation.recommendation_data.suggested_amount"
                                 class="p-4 sm:p-5 rounded-lg border"
                                 :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.05)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <div class="flex items-center gap-2.5 mb-2.5">
                                    <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-white">{{ t('suggestedAmount') }}</h3>
                                </div>
                                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                                    {{ currencySymbol }}{{ formatCurrency(recommendation.recommendation_data.suggested_amount) }}
                                </p>
                                <p class="text-xs font-medium text-slate-300">
                                    {{ t('basedOnYourPortfolio') }}
                                </p>
                            </div>

                            <div v-if="recommendation.recommendation_data.expected_roi"
                                 class="p-4 sm:p-5 rounded-lg border"
                                 :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.05)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <div class="flex items-center gap-2.5 mb-2.5">
                                    <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-white">{{ t('expectedROI') }}</h3>
                                </div>
                                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                                    {{ recommendation.recommendation_data.expected_roi }}%
                                </p>
                                <p class="text-xs font-medium text-slate-300">
                                    {{ t('projectedReturn') }}
                                </p>
                            </div>

                            <div v-if="recommendation.recommendation_data.recent_earnings"
                                 class="p-4 sm:p-5 rounded-lg border"
                                 :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.05)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <div class="flex items-center gap-2.5 mb-2.5">
                                    <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-white">{{ t('recentEarnings') }}</h3>
                                </div>
                                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                                    {{ currencySymbol }}{{ formatCurrency(recommendation.recommendation_data.recent_earnings) }}
                                </p>
                                <p class="text-xs font-medium text-slate-300">
                                    {{ t('last30Days') }}
                                </p>
                            </div>

                            <div v-if="recommendation.recommendation_data.duration_days"
                                 class="p-4 sm:p-5 rounded-lg border"
                                 :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.05)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <div class="flex items-center gap-2.5 mb-2.5">
                                    <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-white">{{ t('investmentPeriod') }}</h3>
                                </div>
                                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                                    {{ recommendation.recommendation_data.duration_days }}
                                </p>
                                <p class="text-xs font-medium text-slate-300">
                                    {{ t('days') }}
                                </p>
                            </div>
                        </div>

                        <div v-if="recommendation.recommendation_data.reason"
                             class="mt-5 p-4 sm:p-5 bg-slate-800/40 rounded-lg">
                            <h3 class="text-sm font-bold text-white mb-2.5 flex items-center gap-1.5">
                                <svg class="w-4.5 h-4.5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ t('whyThisRecommendation') }}
                            </h3>
                            <p class="text-sm font-medium text-slate-300 leading-relaxed">
                                {{ recommendation.recommendation_data.reason }}
                            </p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 bg-slate-800/40">
                        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                            <button
                                v-if="recommendation.status === 'active' && recommendation.type === 'investment_plan'"
                                @click="applyRecommendation"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg font-bold text-sm transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`background: ${themeColors.primary}; color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                            >
                                <svg class="w-4.5 h-4.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ t('applyRecommendation') }}
                            </button>

                            <button
                                v-if="recommendation.status === 'active'"
                                @click="dismissRecommendation"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg font-bold text-sm bg-slate-800/60 text-slate-300 hover:bg-slate-800 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                            >
                                <svg class="w-4.5 h-4.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                {{ t('dismiss') }}
                            </button>

                            <Link
                                href="/user/ai-recommendations"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg font-bold text-sm bg-slate-800/60 text-slate-300 hover:bg-slate-800 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                            >
                                {{ t('backToList') }}
                            </Link>
                        </div>
                    </div>
                </section>

                <section class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 animate-fade-in animation-delay-400">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                             :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                            <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-base sm:text-lg font-bold text-white mb-1.5">{{ t('howAiWorks') }}</h2>
                            <p class="text-sm font-medium text-slate-300 leading-relaxed">
                                {{ t('aiAnalyzesYourPortfolio') }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="p-3.5 sm:p-4 bg-slate-800/40 rounded-lg">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-2.5"
                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                <svg class="w-4.5 h-4.5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-white mb-1.5">{{ t('dataAnalysis') }}</h3>
                            <p class="text-xs font-medium text-slate-400">
                                {{ t('analyzesHistoricalData') }}
                            </p>
                        </div>

                        <div class="p-3.5 sm:p-4 bg-slate-800/40 rounded-lg">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-2.5"
                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                <svg class="w-4.5 h-4.5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-white mb-1.5">{{ t('riskMatching') }}</h3>
                            <p class="text-xs font-medium text-slate-400">
                                {{ t('matchesYourRiskProfile') }}
                            </p>
                        </div>

                        <div class="p-3.5 sm:p-4 bg-slate-800/40 rounded-lg">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-2.5"
                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                <svg class="w-4.5 h-4.5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-white mb-1.5">{{ t('realTimeUpdates') }}</h3>
                            <p class="text-xs font-medium text-slate-400">
                                {{ t('continuouslyUpdated') }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import { useToast } from "@/composables/useToast.js";
import { useTranslation } from '@/composables/useTranslation';

const props = defineProps({
    recommendation: {
        type: Object,
        required: true
    },
    investmentPlan: {
        type: Object,
        default: null
    }
});

const { showToast } = useToast();
const { t } = useTranslation();
const page = usePage();

const primaryColor = computed(() => page.props.primaryColor || '#86efac');
const currencySymbol = computed(() => page.props.currencySymbol || '$');

const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 };
};

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex);
    const newR = Math.min(255, Math.max(0, rgb.r + amount));
    const newG = Math.min(255, Math.max(0, rgb.g + amount));
    const newB = Math.min(255, Math.max(0, rgb.b + amount));
    return "#" + ((1 << 24) + (newR << 16) + (newG << 8) + newB).toString(16).slice(1);
};

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}));

const applyRecommendation = () => {
    router.post(`/user/ai-recommendations/${props.recommendation.id}/apply`, {}, {
        onSuccess: () => {
        },
        onError: () => {
            showToast(t('failedToApply') || 'Failed to apply recommendation', 'error');
        }
    });
};

const dismissRecommendation = () => {
    router.post(`/user/ai-recommendations/${props.recommendation.id}/dismiss`, {}, {
        onSuccess: () => {
            showToast(t('recommendationDismissed') || 'Recommendation dismissed', 'success');
        },
        onError: () => {
            showToast(t('failedToDismiss') || 'Failed to dismiss recommendation', 'error');
        }
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatCurrency = (amount) => {
    const number = parseFloat(amount) || 0;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
};

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-green-500/20 text-green-400 border-green-500/30',
        dismissed: 'bg-slate-700/60 text-slate-400 border-slate-600/30',
        applied: 'bg-blue-500/20 text-blue-400 border-blue-500/30'
    };
    return classes[status] || 'bg-slate-700/60 text-slate-400 border-slate-600/30';
};

const getPriorityBadgeClass = (priority) => {
    const classes = {
        high: 'bg-red-500/20 text-red-400 border-red-500/30',
        medium: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        low: 'bg-slate-700/60 text-slate-400 border-slate-600/30'
    };
    return classes[priority] || 'bg-slate-700/60 text-slate-400 border-slate-600/30';
};

const getRiskLevelClass = (risk) => {
    const classes = {
        low: 'text-green-400',
        medium: 'text-yellow-400',
        high: 'text-red-400'
    };
    return classes[risk] || 'text-slate-400';
};

const getConfidenceLabel = (score) => {
    if (score >= 85) return t('veryHighConfidence') || 'Very High Confidence';
    if (score >= 70) return t('highConfidence') || 'High Confidence';
    if (score >= 55) return t('moderateConfidence') || 'Moderate Confidence';
    return t('lowConfidence') || 'Low Confidence';
};
</script>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out forwards;
}

.animation-delay-200 {
    animation-delay: 0.2s;
    opacity: 0;
}

.animation-delay-400 {
    animation-delay: 0.4s;
    opacity: 0;
}

.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

input:focus,
select:focus,
button:focus {
    outline: none;
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in {
        animation: none !important;
        opacity: 1;
    }
}
</style>

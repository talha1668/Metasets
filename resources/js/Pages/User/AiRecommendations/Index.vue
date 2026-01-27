<template>
    <UserLayout
        :page-title="t('aiRecommendations')"
        :page-section="t('smartInsights')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <section class="animate-fade-in">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 sm:gap-4">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-xl sm:text-2xl font-bold text-white mb-1">
                                {{ t('aiRecommendations') }}
                            </h1>
                            <p class="text-sm font-medium text-slate-400">
                                {{ t('personalizedInvestmentInsights') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2.5 flex-shrink-0">
                            <button
                                @click="generateRecommendations"
                                :disabled="isGenerating"
                                class="inline-flex items-center px-4 py-2.5 rounded-lg font-semibold text-sm transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                                :style="`background: ${themeColors.primary}; color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                            >
                                <svg v-if="!isGenerating" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                <svg v-else class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="hidden sm:inline">{{ isGenerating ? t('generating') : t('generateNew') }}</span>
                                <span class="inline sm:hidden">{{ isGenerating ? t('generating') : t('generate') }}</span>
                            </button>
                        </div>
                    </div>
                </section>

                <section class="animate-fade-in animation-delay-400">
                    <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl overflow-hidden hover:border-slate-600/50 transition-all duration-300 shadow-2xl">
                        <div class="px-4 sm:px-5 lg:px-6 py-4 sm:py-5 border-b border-slate-700/30">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div class="flex-1">
                                    <h2 class="text-lg sm:text-xl font-bold text-white mb-1">
                                        {{ t('yourRecommendations') }}
                                    </h2>
                                    <p class="text-sm font-medium text-slate-400">
                                        {{ t('aiPoweredInvestmentSuggestions') }}
                                    </p>
                                </div>
                                <div class="text-sm font-medium text-slate-400">
                                    {{ recommendations?.meta?.total || 0 }} {{ t('total') }}
                                </div>
                            </div>
                        </div>

                        <div class="px-4 sm:px-5 lg:px-6 py-3 sm:py-4 border-b border-slate-700/30 bg-slate-800/30">
                            <div class="flex flex-wrap gap-1.5 sm:gap-2">
                                <button
                                    @click="filters.type = ''; applyFilters()"
                                    :class="['px-2.5 sm:px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900']"
                                    :style="!filters.type ? `background-color: ${themeColors.primary}; color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : 'background-color: rgba(71, 85, 105, 0.5); color: #e2e8f0;'"
                                >
                                    {{ t('allTypes') }}
                                </button>
                                <button
                                    @click="filters.type = 'investment_plan'; applyFilters()"
                                    :class="['px-2.5 sm:px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900']"
                                    :style="filters.type === 'investment_plan' ? `background-color: ${themeColors.primary}; color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : 'background-color: rgba(71, 85, 105, 0.5); color: #e2e8f0;'"
                                >
                                    {{ t('investmentPlan') }}
                                </button>
                                <button
                                    @click="filters.type = 'reinvestment'; applyFilters()"
                                    :class="['px-2.5 sm:px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900']"
                                    :style="filters.type === 'reinvestment' ? `background-color: ${themeColors.primary}; color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : 'background-color: rgba(71, 85, 105, 0.5); color: #e2e8f0;'"
                                >
                                    {{ t('reinvestment') }}
                                </button>
                                <button
                                    @click="filters.status = filters.status === 'active' ? '' : 'active'; applyFilters()"
                                    :class="['px-2.5 sm:px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900']"
                                    :style="filters.status === 'active' ? `background-color: ${themeColors.primary}; color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : 'background-color: rgba(71, 85, 105, 0.5); color: #e2e8f0;'"
                                >
                                    {{ t('activeOnly') }}
                                </button>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-700/30">
                            <div v-if="!recommendations.data || recommendations.data.length === 0"
                                 class="text-center py-12 sm:py-16 px-4">
                                <div class="flex flex-col items-center">
                                    <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                    <p class="text-base font-semibold text-slate-300 mb-1">{{ t('noRecommendations') }}</p>
                                    <p class="text-sm font-medium text-slate-400">{{ t('clickGenerateToGetPersonalizedInsights') }}</p>
                                </div>
                            </div>

                            <div
                                v-for="recommendation in recommendations.data"
                                :key="recommendation.id"
                                class="p-4 sm:p-5 hover:bg-slate-800/30 transition-colors"
                            >
                                <div class="flex items-start justify-between gap-3 sm:gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2.5 mb-3">
                                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                                <svg v-if="recommendation.type === 'investment_plan'" class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                                </svg>
                                                <svg v-else class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                                                    <span v-if="!recommendation.is_read"
                                                          class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                                          :style="`background-color: ${themeColors.primary};`"></span>
                                                    <h3 class="text-sm sm:text-base font-bold text-white truncate">
                                                        {{ recommendation.title }}
                                                    </h3>
                                                </div>
                                                <p class="text-xs font-medium text-slate-400">
                                                    {{ formatDate(recommendation.created_at) }}
                                                </p>
                                            </div>
                                        </div>

                                        <p class="text-sm font-medium text-slate-300 mb-3 leading-relaxed">
                                            {{ recommendation.description }}
                                        </p>

                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 sm:gap-3 mb-3">
                                            <div class="p-2.5 sm:p-3 bg-slate-800/40 rounded-lg">
                                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">
                                                    {{ t('confidence') }}
                                                </p>
                                                <p class="text-base sm:text-lg font-bold text-white">
                                                    {{ recommendation.confidence_score }}%
                                                </p>
                                            </div>
                                            <div class="p-2.5 sm:p-3 bg-slate-800/40 rounded-lg">
                                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">
                                                    {{ t('priority') }}
                                                </p>
                                                <span class="inline-flex items-center text-sm font-bold capitalize"
                                                      :class="getPriorityClass(recommendation.priority)">
                                                    {{ recommendation.priority }}
                                                </span>
                                            </div>
                                            <div class="p-2.5 sm:p-3 bg-slate-800/40 rounded-lg">
                                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">
                                                    {{ t('type') }}
                                                </p>
                                                <span class="inline-flex items-center text-sm font-bold text-white">
                                                    {{ formatType(recommendation.type) }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <Link
                                                :href="`/user/ai-recommendations/${recommendation.id}`"
                                                class="inline-flex items-center px-3 sm:px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                                :style="`background: ${themeColors.primary}; color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                            >
                                                {{ t('viewDetails') }}
                                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </Link>

                                            <button
                                                v-if="recommendation.status === 'active'"
                                                @click="dismissRecommendation(recommendation.id)"
                                                class="inline-flex items-center px-3 sm:px-4 py-2 rounded-lg font-semibold text-sm bg-slate-800/60 text-slate-300 hover:bg-slate-800 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                                :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                            >
                                                {{ t('dismiss') }}
                                            </button>

                                            <span
                                                v-else
                                                class="inline-flex items-center px-3 sm:px-4 py-2 rounded-lg font-semibold text-sm capitalize"
                                                :class="getStatusClass(recommendation.status)"
                                            >
                                                {{ recommendation.status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="recommendations.data && recommendations.data.length > 0 && recommendations.meta.last_page > 1"
                             class="px-4 sm:px-5 lg:px-6 py-4 sm:py-5 border-t border-slate-700/30">
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                                <div class="text-sm font-medium text-slate-400">
                                    {{ t('showing') }} {{ recommendations.meta.from || 0 }} {{ t('to') }} {{ recommendations.meta.to || 0 }} {{ t('of') }} {{ recommendations.meta.total || 0 }} {{ t('results') }}
                                </div>
                                <div class="flex flex-wrap justify-center sm:justify-end gap-1.5">
                                    <button
                                        v-for="(link, index) in paginationLinks"
                                        :key="index"
                                        :disabled="!link.url"
                                        :class="['px-3 py-1.5 text-sm font-semibold rounded-lg transition-all duration-200 hover:scale-105 focus:outline-none', link.active ? 'cursor-default' : link.url ? 'hover:opacity-80' : 'cursor-not-allowed opacity-50']"
                                        :style="link.active ? `background-color: ${themeColors.primary}; color: #0f172a;` : link.url ? 'background-color: rgba(71, 85, 105, 0.5); color: #e2e8f0;' : 'background-color: rgba(71, 85, 105, 0.3); color: #64748b;'"
                                        @click="goToPage(link.url)"
                                        v-html="link.label"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import { useToast } from "@/composables/useToast.js";
import { useTranslation } from '@/composables/useTranslation';

const props = defineProps({
    recommendations: {
        type: Object,
        required: true,
        default: () => ({
            data: [],
            meta: {
                total: 0,
                current_page: 1,
                per_page: 10,
                last_page: 1,
                from: null,
                to: null,
                links: []
            }
        })
    },
    stats: {
        type: Object,
        required: true,
        default: () => ({
            total: 0,
            active: 0,
            unread: 0,
            applied: 0
        })
    }
});

const { showToast } = useToast();
const { t } = useTranslation();
const page = usePage();

const isGenerating = ref(false);
const filters = ref({
    type: '',
    priority: '',
    status: ''
});

const primaryColor = computed(() => page.props.primaryColor || '#86efac');

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

const statsCards = computed(() => [
    {
        label: t('totalRecommendations') || 'Total',
        value: props.stats?.total || 0,
        icon: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' })
        ]),
        badge: null
    },
    {
        label: t('active') || 'Active',
        value: props.stats?.active || 0,
        icon: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' })
        ]),
        badge: (props.stats?.active || 0) > 0 ? t('live') || 'Live' : null
    },
    {
        label: t('unread') || 'Unread',
        value: props.stats?.unread || 0,
        icon: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' })
        ]),
        badge: (props.stats?.unread || 0) > 0 ? t('new') || 'New' : null
    },
    {
        label: t('applied') || 'Applied',
        value: props.stats?.applied || 0,
        icon: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })
        ]),
        badge: null
    }
]);

const paginationLinks = computed(() => {
    return props.recommendations?.meta?.links || [];
});

const generateRecommendations = () => {
    isGenerating.value = true;
    router.post('/user/ai-recommendations/generate', {}, {
        onSuccess: () => {
            showToast(t('recommendationsGenerated') || 'Recommendations generated successfully', 'success');
        },
        onError: () => {
            showToast(t('failedToGenerate') || 'Failed to generate recommendations', 'error');
        },
        onFinish: () => {
            isGenerating.value = false;
        }
    });
};

const dismissRecommendation = (id) => {
    router.post(`/user/ai-recommendations/${id}/dismiss`, {}, {
        onSuccess: () => {
            showToast(t('recommendationDismissed') || 'Recommendation dismissed', 'success');
        },
        onError: () => {
            showToast(t('failedToDismiss') || 'Failed to dismiss recommendation', 'error');
        }
    });
};

const applyFilters = () => {
    router.get('/user/ai-recommendations', filters.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const goToPage = (url) => {
    if (!url || typeof url !== 'string') return;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        onError: () => showToast(t('failedToLoadPage') || 'Failed to load page', 'error')
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatType = (type) => {
    return type === 'investment_plan' ? t('investmentPlan') || 'Investment Plan' : t('reinvestment') || 'Reinvestment';
};

const getPriorityClass = (priority) => {
    const classes = {
        high: 'text-red-400',
        medium: 'text-yellow-400',
        low: 'text-slate-400'
    };
    return classes[priority] || 'text-slate-400';
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-green-500/20 text-green-400',
        dismissed: 'bg-slate-700/40 text-slate-400',
        applied: 'bg-blue-500/20 text-blue-400'
    };
    return classes[status] || 'bg-slate-700/40 text-slate-400';
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

@keyframes spin {
    to { transform: rotate(360deg); }
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

.animate-spin {
    animation: spin 1s linear infinite;
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
    .animate-fade-in,
    .animate-spin {
        animation: none !important;
    }
}
</style>

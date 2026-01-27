<template>
    <UserLayout :page-title="t('referralCommissions')" :page-section="t('network')">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('totalCommissions') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                            {{ currencySymbol }}{{ formatCurrency(summary.total) }}</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ t('allTime') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('paidCommissions') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                            {{ currencySymbol }}{{ formatCurrency(summary.approved) }}</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ t('received') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('approvedCommissions') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                            {{ currencySymbol }}{{ formatCurrency(summary.paid) }}</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ t('processing') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('pendingCommissions') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                            {{ currencySymbol }}{{ formatCurrency(summary.pending) }}</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ t('awaiting') }}
                            </span>
                        </div>
                    </div>
                </section>

                <section
                    class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl overflow-hidden hover:border-slate-600/50 transition-all duration-300 shadow-2xl">
                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-700/30">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between gap-3">
                            <div class="flex-1">
                                <h2 class="text-base sm:text-lg font-bold text-white mb-1">{{ t('commissionHistory') }}
                                </h2>
                                <p class="text-sm font-medium text-slate-400">{{ t('allReferralCommissions') }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button
                                    v-for="filter in ['all', 'paid', 'approved', 'pending']"
                                    :key="filter"
                                    @click="changeFilter(filter)"
                                    :class="['px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 hover:scale-105 focus:outline-none']"
                                    :style="filterType === filter ? `background: ${themeColors.primary}; color: #0f172a;` : 'background: rgba(71, 85, 105, 0.5); color: #e2e8f0;'">
                                    {{ filter === 'all' ? t('all') : formatStatus(filter) }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full hidden lg:table">
                            <thead class="bg-slate-800/50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('referredUser') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('level') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('type') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('baseAmount') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('rate') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('commission') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('status') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('date') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/30">
                            <tr v-if="!filteredCommissions || filteredCommissions.length === 0">
                                <td colspan="8" class="px-5 py-12 sm:py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-3 text-slate-600" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-base font-semibold text-slate-300">{{
                                                t('noCommissionsFound')
                                            }}</p>
                                        <p class="text-sm font-medium text-slate-400 mt-1">{{
                                                t('noCommissionHistory')
                                            }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="commission in filteredCommissions" :key="commission.id"
                                class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                             :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                            <span class="text-sm font-bold" :style="{ color: themeColors.primary }">
                                                {{ getInitials(commission.referrer_name) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-white text-sm">{{
                                                    commission.referrer_name
                                                }}</p>
                                            <p class="text-xs font-medium text-slate-400 truncate max-w-[200px]">
                                                {{ commission.referrer_email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800/50 text-white">
                                        {{ t('level') }} {{ commission.level }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800/50 text-white capitalize">
                                        {{ formatType(commission.type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-sm font-semibold text-slate-300">
                                        {{ currencySymbol }}{{ formatCurrency(commission.base_amount) }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-sm font-semibold text-white">{{ commission.commission_rate }}%</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-bold text-sm" :style="{ color: themeColors.primary }">
                                        +{{ currencySymbol }}{{ formatCurrency(commission.commission_amount) }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full border"
                                          :style="getStatusStyle(commission.status)">
                                        {{ formatStatus(commission.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm">
                                    <div class="text-slate-300 font-medium">{{
                                            formatDate(commission.created_at)
                                        }}
                                    </div>
                                    <div class="text-xs font-medium text-slate-400">
                                        {{ formatTime(commission.created_at) }}
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <div class="block lg:hidden">
                            <div v-if="!filteredCommissions || filteredCommissions.length === 0"
                                 class="text-center py-12 sm:py-16 px-4">
                                <div class="flex flex-col items-center">
                                    <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-3 text-slate-600" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-base font-semibold text-slate-300">{{ t('noCommissionsFound') }}</p>
                                    <p class="text-sm font-medium text-slate-400 mt-1">{{
                                            t('noCommissionHistory')
                                        }}</p>
                                </div>
                            </div>

                            <div v-for="commission in filteredCommissions" :key="commission.id"
                                 class="border-b border-slate-700/30 last:border-b-0">
                                <div class="p-4 sm:p-5 space-y-3 hover:bg-slate-800/30 transition-colors">
                                    <div class="flex justify-between items-start gap-3">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <div
                                                class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                                :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                                <span class="text-sm font-bold"
                                                      :style="{ color: themeColors.primary }">
                                                    {{ getInitials(commission.referrer_name) }}
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-semibold text-white truncate">
                                                    {{ commission.referrer_name }}
                                                </div>
                                                <div class="text-xs font-medium text-slate-400 mt-0.5 truncate">
                                                    {{ commission.referrer_email }}
                                                </div>
                                            </div>
                                        </div>
                                        <span
                                            class="px-2.5 py-1 text-xs font-semibold rounded-full border flex-shrink-0"
                                            :style="getStatusStyle(commission.status)">
                                            {{ formatStatus(commission.status) }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-slate-800/40 rounded-lg p-3">
                                            <div
                                                class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">
                                                {{ t('commission') }}
                                            </div>
                                            <div class="text-base font-bold" :style="{ color: themeColors.primary }">
                                                +{{ currencySymbol }}{{ formatCurrency(commission.commission_amount) }}
                                            </div>
                                        </div>
                                        <div class="bg-slate-800/40 rounded-lg p-3">
                                            <div
                                                class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">
                                                {{ t('rate') }}
                                            </div>
                                            <div class="text-base font-bold text-white">
                                                {{ commission.commission_rate }}%
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center text-xs font-medium">
                                        <div class="space-y-0.5">
                                            <div class="text-slate-400">{{ t('type') }}: <span
                                                class="text-white capitalize">{{ formatType(commission.type) }}</span>
                                            </div>
                                            <div class="text-slate-400">{{ t('level') }}: <span
                                                class="text-white">{{ commission.level }}</span></div>
                                        </div>
                                        <div class="text-right text-slate-400">
                                            <div>{{ formatDate(commission.created_at) }}</div>
                                            <div>{{ formatTime(commission.created_at) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-t border-slate-700/30">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <p class="text-sm font-medium text-slate-400">
                                {{ t('showing') }} <span class="font-semibold text-white">{{
                                    commissions.from || 0
                                }}</span>
                                {{ t('to') }} <span class="font-semibold text-white">{{ commissions.to || 0 }}</span>
                                {{ t('of') }} <span class="font-semibold text-white">{{ commissions.total || 0 }}</span>
                                {{ t('results') }}
                            </p>
                            <div class="flex flex-wrap items-center justify-center sm:justify-end gap-1.5">
                                <Link
                                    :href="commissions.prev_page_url"
                                    :disabled="!commissions.prev_page_url"
                                    :class="['px-3 py-1.5 rounded-lg text-sm font-semibold transition-all hover:scale-105 focus:outline-none', !commissions.prev_page_url ? 'bg-slate-800/30 text-slate-600 cursor-not-allowed pointer-events-none' : 'bg-slate-800/50 hover:bg-slate-700/50 text-white']"
                                    preserve-scroll>
                                    {{ t('previous') }}
                                </Link>

                                <div class="flex items-center gap-1.5">
                                    <Link
                                        v-for="page in visiblePages"
                                        :key="page"
                                        :href="getPageUrl(page)"
                                        :class="['px-3 py-1.5 rounded-lg text-sm font-semibold transition-all hover:scale-105 focus:outline-none']"
                                        :style="page === commissions.current_page ? `background: ${themeColors.primary}; color: #0f172a;` : 'background: rgba(71, 85, 105, 0.5); color: #e2e8f0;'"
                                        preserve-scroll>
                                        {{ page }}
                                    </Link>
                                </div>

                                <Link
                                    :href="commissions.next_page_url"
                                    :disabled="!commissions.next_page_url"
                                    :class="['px-3 py-1.5 rounded-lg text-sm font-semibold transition-all hover:scale-105 focus:outline-none', !commissions.next_page_url ? 'bg-slate-800/30 text-slate-600 cursor-not-allowed pointer-events-none' : 'bg-slate-800/50 hover:bg-slate-700/50 text-white']"
                                    preserve-scroll>
                                    {{ t('next') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';
import {usePage, Link} from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import {useToast} from "@/composables/useToast.js";
import {useTranslation} from '@/composables/useTranslation';

const props = defineProps({
    commissions: {
        type: Object,
        required: true
    },
    summary: {
        type: Object,
        required: true
    }
});

const {showToast} = useToast();
const {t} = useTranslation();
const page = usePage();

const filterType = ref('all');

const primaryColor = computed(() => page.props.primaryColor || '#86efac');
const currencySymbol = computed(() => page.props.currencySymbol || '$');

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') return {r: 134, g: 239, b: 172};
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : {r: 134, g: 239, b: 172};
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

const filteredCommissions = computed(() => {
    if (filterType.value === 'all') return props.commissions.data || [];
    return (props.commissions.data || []).filter(c => c.status === filterType.value);
});

const visiblePages = computed(() => {
    const pages = [];
    const current = props.commissions.current_page || 1;
    const last = props.commissions.last_page || 1;

    for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || Math.abs(i - current) <= 1) {
            pages.push(i);
        }
    }
    return pages;
});

const getPageUrl = (page) => {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    return url.pathname + url.search;
};

const changeFilter = (filter) => {
    filterType.value = filter;
};

const formatCurrency = (amount) => {
    const number = parseFloat(amount) || 0;
    return new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(number);
};

const formatDate = (dateString) => {
    if (!dateString) return t('notAvailable');
    try {
        return new Date(dateString).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
    } catch {
        return t('invalidDate');
    }
};

const formatTime = (dateString) => {
    if (!dateString) return t('notAvailable');
    try {
        return new Date(dateString).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'});
    } catch {
        return t('invalidTime');
    }
};

const formatType = (type) => {
    const typeMap = {
        'deposit': t('deposit'),
        'investment_profit': t('investmentProfit'),
        'reinvestment': t('reinvestment')
    };
    return typeMap[type] || type.replace(/_/g, ' ');
};

const formatStatus = (status) => {
    if (!status || typeof status !== 'string') return t('unknown');
    return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
};

const getStatusStyle = (status) => {
    if (!status) return {
        backgroundColor: 'rgba(148, 163, 184, 0.2)',
        borderColor: 'rgba(148, 163, 184, 0.3)',
        color: '#94a3b8'
    };

    switch (status.toLowerCase()) {
        case 'paid':
            return {
                backgroundColor: `rgba(${themeColors.value.primaryRgb}, 0.2)`,
                borderColor: `rgba(${themeColors.value.primaryRgb}, 0.3)`,
                color: themeColors.value.primary
            };
        case 'approved':
            return {
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 0.3)',
                color: '#3b82f6'
            };
        case 'pending':
            return {
                backgroundColor: 'rgba(251, 191, 36, 0.2)',
                borderColor: 'rgba(251, 191, 36, 0.3)',
                color: '#fbbf24'
            };
        default:
            return {
                backgroundColor: 'rgba(148, 163, 184, 0.2)', borderColor: 'rgba(148, 163, 184, 0.3)',
                color: '#94a3b8'
            };
    }
};

const getInitials = (name) => {
    if (!name) return 'NA';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

onMounted(() => {
    try {
        const flash = page.props.flash;
        if (flash?.success) showToast(flash.success, 'success');
        if (flash?.error) showToast(flash.error, 'error');
        if (flash?.warning) showToast(flash.warning, 'warning');
        if (flash?.info) showToast(flash.info, 'info');
    } catch (error) {
        console.error('Component mount error:', error);
    }
});
</script>

<style scoped>
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
.hover\:scale-\[1\.02\]:hover,
.hover\:scale-105:hover {
    will-change: transform;
}

@media (max-width: 768px) {
    * {
        -webkit-overflow-scrolling: touch;
    }
}

@media print {
    .hover\:scale-\[1\.01\],
    .hover\:scale-\[1\.02\],
    .hover\:scale-105 {
        transform: none !important;
    }

    button,
    a[href^="/"] {
        display: none;
    }
}

::-webkit-scrollbar {
    height: 8px;
    width: 8px;
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
</style>

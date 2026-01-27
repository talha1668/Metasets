<template>
    <UserLayout
        :page-title="t('supportTickets')"
        :page-section="t('helpCenter')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('supportTicketsDashboard')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <section :aria-labelledby="t('ticketStatistics')"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                    <h2 class="sr-only">{{ t('ticketStatistics') }}</h2>
                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('totalTickets')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('totalTickets') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('totalTickets')}: ${ticketStats.total}`">
                            {{ ticketStats.total }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }"
                                  :aria-label="t('allTime')">
                                {{ t('allTime') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('openTickets')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{
                                t('open')
                            }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('openTickets')}: ${ticketStats.open}`">
                            {{ ticketStats.open }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" style="color: #3b82f6" :aria-label="t('active')">
                                {{ t('active') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('pendingTickets')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{
                                t('pending')
                            }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('pendingTickets')}: ${ticketStats.pending}`">
                            {{ ticketStats.pending }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" style="color: #f59e0b" :aria-label="t('waiting')">
                                {{ t('waiting') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('resolvedTickets')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{
                                t('resolved')
                            }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('resolvedTickets')}: ${ticketStats.resolved}`">
                            {{ ticketStats.resolved }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" style="color: #10b981" :aria-label="t('completed')">
                                {{ t('completed') }}
                            </span>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('ticketFilters')"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <h2 class="sr-only">{{ t('ticketFilters') }}</h2>
                    <div class="flex flex-col lg:flex-row gap-3 lg:gap-5">
                        <div class="flex-1">
                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{
                                    t('searchTickets')
                                }}</label>
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="t('searchPlaceholder')"
                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                @keyup.enter="applyFilters"
                                :aria-label="t('searchTickets')"
                            />
                        </div>

                        <div class="lg:w-44">
                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('status') }}</label>
                            <select
                                v-model="selectedStatus"
                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                :aria-label="t('filterByStatus')"
                            >
                                <option value="">{{ t('allStatuses') }}</option>
                                <option value="open">{{ t('open') }}</option>
                                <option value="pending">{{ t('pending') }}</option>
                                <option value="resolved">{{ t('resolved') }}</option>
                                <option value="closed">{{ t('closed') }}</option>
                            </select>
                        </div>

                        <div class="flex gap-2.5 lg:items-end">
                            <button
                                @click="applyFilters"
                                class="px-5 py-2.5 rounded-lg font-semibold transition-all hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.25), rgba(${themeColors.primaryRgb}, 0.15)); color: ${themeColors.primary}; border: 1px solid rgba(${themeColors.primaryRgb}, 0.3); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                :aria-label="t('applyFilters')"
                            >
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                     aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                            <button
                                @click="clearFilters"
                                class="px-5 py-2.5 bg-slate-800/60 text-slate-400 rounded-lg font-semibold text-sm hover:text-white hover:bg-slate-800 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                :aria-label="t('clearFilters')"
                            >
                                {{ t('clear') }}
                            </button>
                            <Link
                                href="/user/support-tickets/create"
                                class="px-5 py-2.5 rounded-lg font-semibold text-sm text-slate-900 transition-all hover:scale-105 whitespace-nowrap hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                :aria-label="t('createNewTicket')"
                            >
                                <span class="hidden sm:inline">{{ t('createTicket') }}</span>
                                <span class="sm:hidden">{{ t('create') }}</span>
                            </Link>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('ticketsList')"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 sm:mb-6">
                        <div class="flex-1">
                            <h3 class="text-lg sm:text-xl font-bold text-white mb-1.5">{{ t('yourTickets') }}</h3>
                            <p class="text-sm font-medium text-slate-400">{{ t('manageSupportRequests') }}</p>
                        </div>
                    </div>

                    <div v-if="tickets.length" class="space-y-3 sm:space-y-4" role="list"
                         :aria-label="t('supportTicketsList')">
                        <div
                            v-for="(ticket, index) in tickets"
                            :key="ticket.id"
                            class="bg-slate-800/40 rounded-lg p-4 sm:p-5 border border-slate-700/30 hover:bg-slate-800/60 hover:border-slate-600/50 transition-all duration-300 cursor-pointer group"
                            role="listitem"
                            tabindex="0"
                            :aria-label="`${t('ticket')} ${index + 1}: ${ticket.subject}`"
                            @click="router.visit(`/user/support-tickets/${ticket.id}`)"
                            @keyup.enter="router.visit(`/user/support-tickets/${ticket.id}`)"
                        >
                            <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-5">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform"
                                        :style="`background-color: ${getStatusBgColor(ticket.status)}`"
                                        :aria-hidden="true">
                                        <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5"
                                             :style="`color: ${getStatusColor(ticket.status)}`" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  :d="getStatusIcon(ticket.status)"/>
                                        </svg>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1.5 sm:gap-2.5 mb-1.5">
                                        <h4 class="font-semibold text-white text-sm sm:text-base truncate">
                                            {{ ticket.subject }}</h4>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold"
                                                  :style="`background-color: ${getStatusBgColor(ticket.status)}; color: ${getStatusColor(ticket.status)}`"
                                                  :aria-label="`${t('status')}: ${getStatusLabel(ticket.status)}`">
                                                {{ getStatusLabel(ticket.status) }}
                                            </span>
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold"
                                                  :style="`background-color: ${getPriorityBgColor(ticket.priority)}; color: ${getPriorityColor(ticket.priority)}`"
                                                  :aria-label="`${t('priority')}: ${getPriorityLabel(ticket.priority)}`">
                                                {{ getPriorityLabel(ticket.priority) }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-sm font-medium text-slate-400 mb-2.5 line-clamp-2">
                                        {{ ticket.description }}</p>
                                    <div class="flex flex-wrap items-center gap-3 text-xs font-medium text-slate-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                 aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                            </svg>
                                            {{ ticket.ticket_number }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                 aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ formatDate(ticket.created_at) }}
                                        </span>
                                        <span v-if="ticket.replies_count" class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                 aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                            </svg>
                                            {{ ticket.replies_count }} {{ t('replies') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex-shrink-0 hidden lg:block">
                                    <svg class="w-5 h-5 text-slate-600 group-hover:text-slate-400 transition-colors"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12 sm:py-16">
                        <svg class="w-14 h-14 sm:w-16 sm:h-16 mx-auto mb-3 text-slate-600" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <p class="text-sm font-medium text-slate-400 mb-5">{{
                                t('noSupportTicketsFound')
                            }}</p>
                        <Link
                            href="/user/support-tickets/create"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg font-semibold text-sm text-slate-900 transition-all hover:scale-105 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                            :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                            :aria-label="t('createFirstTicket')"
                        >
                            {{ t('createYourFirstTicket') }}
                            <svg class="w-4.5 h-4.5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';
import {usePage, Link, router} from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import {useToast} from "@/composables/useToast.js";
import {useTranslation} from '@/composables/useTranslation';

const props = defineProps({
    tickets: {
        type: Array,
        default: () => [],
        validator: (value) => Array.isArray(value)
    },
    meta: {
        type: Object,
        default: () => ({
            total: 0,
            current_page: 1,
            per_page: 10,
            last_page: 1
        }),
        validator: (value) => value && typeof value === 'object'
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: '',
            sort_field: 'created_at',
            sort_direction: 'desc'
        }),
        validator: (value) => value && typeof value === 'object'
    }
});

const {showToast} = useToast();
const {t} = useTranslation();
const page = usePage();

const searchQuery = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const sortField = ref(props.filters.sort_field || 'created_at');
const sortDirection = ref(props.filters.sort_direction || 'desc');

const primaryColor = computed(() => page.props.primaryColor || '#86efac');

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

const statusConfig = {
    open: {
        label: t('open') || 'Open',
        color: '#3b82f6',
        bgColor: 'rgba(59, 130, 246, 0.1)',
        icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'
    },
    pending: {
        label: t('pending') || 'Pending',
        color: '#f59e0b',
        bgColor: 'rgba(245, 158, 11, 0.1)',
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
    },
    resolved: {
        label: t('resolved') || 'Resolved',
        color: '#10b981',
        bgColor: 'rgba(16, 185, 129, 0.1)',
        icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    },
    closed: {
        label: t('closed') || 'Closed',
        color: '#6b7280',
        bgColor: 'rgba(107, 114, 128, 0.1)',
        icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    }
};

const priorityConfig = {
    low: {
        label: t('low') || 'Low',
        color: '#6b7280',
        bgColor: 'rgba(107, 114, 128, 0.1)'
    },
    medium: {
        label: t('medium') || 'Medium',
        color: '#3b82f6',
        bgColor: 'rgba(59, 130, 246, 0.1)'
    },
    high: {
        label: t('high') || 'High',
        color: '#f59e0b',
        bgColor: 'rgba(245, 158, 11, 0.1)'
    },
    urgent: {
        label: t('urgent') || 'Urgent',
        color: '#ef4444',
        bgColor: 'rgba(239, 68, 68, 0.1)'
    }
};

const ticketStats = computed(() => {
    const stats = {
        total: props.meta?.total || props.tickets.length,
        open: 0,
        pending: 0,
        resolved: 0,
        closed: 0
    };

    props.tickets.forEach(ticket => {
        if (stats[ticket.status] !== undefined) {
            stats[ticket.status]++;
        }
    });

    return stats;
});

const applyFilters = () => {
    router.get('/user/support-tickets', {
        search: searchQuery.value,
        status: selectedStatus.value,
        sort_field: sortField.value,
        sort_direction: sortDirection.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = '';
    sortField.value = 'created_at';
    sortDirection.value = 'desc';
    applyFilters();
};

const formatDate = (dateString) => {
    if (!dateString) return t('notAvailable') || 'N/A';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return t('invalidDate') || 'Invalid date';
        }
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        console.error('Date formatting error:', error);
        return t('invalidDate') || 'Invalid date';
    }
};

const getStatusIcon = (status) => {
    return statusConfig[status]?.icon || statusConfig.open.icon;
};

const getStatusLabel = (status) => {
    return statusConfig[status]?.label || status.charAt(0).toUpperCase() + status.slice(1);
};

const getStatusColor = (status) => {
    return statusConfig[status]?.color || statusConfig.open.color;
};

const getStatusBgColor = (status) => {
    return statusConfig[status]?.bgColor || statusConfig.open.bgColor;
};

const getPriorityLabel = (priority) => {
    return priorityConfig[priority]?.label || priority.charAt(0).toUpperCase() + priority.slice(1);
};

const getPriorityColor = (priority) => {
    return priorityConfig[priority]?.color || priorityConfig.medium.color;
};

const getPriorityBgColor = (priority) => {
    return priorityConfig[priority]?.bgColor || priorityConfig.medium.bgColor;
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

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
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
    .hover\:scale-105 {
        transform: none !important;
    }

    button,
    a[href^="/"] {
        display: none;
    }
}
</style>

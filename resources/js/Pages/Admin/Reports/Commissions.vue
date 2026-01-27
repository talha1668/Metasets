<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import { useSettings } from "@/composables/useSettings.js";
import { useToast } from "@/composables/useToast.js";
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        default: () => ({
            total_commissions: 0,
            pending_commissions: 0,
            paid_commissions: 0,
            total_transactions: 0,
            average_commission: 0
        })
    },
    charts: {
        type: Object,
        required: true,
        default: () => ({
            level_wise: [],
            type_wise: [],
            commission_trend: [],
            top_earners: [],
            status_distribution: [],
            rate_analysis: []
        })
    },
    filters: {
        type: Object,
        default: () => ({
            period: 'month',
            date_from: null,
            date_to: null,
            level: null,
            type: null,
            status: null
        })
    },
    commission_types: {
        type: Array,
        default: () => [
            { value: 'deposit', label: 'Deposit' },
            { value: 'investment_profit', label: 'Investment Profit' },
            { value: 'reinvestment', label: 'Reinvestment' }
        ]
    },
    status_options: {
        type: Array,
        default: () => [
            { value: 'pending', label: 'Pending' },
            { value: 'approved', label: 'Approved' },
            { value: 'paid', label: 'Paid' }
        ]
    }
});

const { currencySymbol } = useSettings();
const { showToast } = useToast();

const isLoading = ref(false);
const selectedPeriod = ref(props.filters?.period || 'month');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const selectedLevel = ref(props.filters?.level || '');
const selectedType = ref(props.filters?.type || '');
const selectedStatus = ref(props.filters?.status || '');
const showCustomDateRange = ref(selectedPeriod.value === 'custom');

// Chart instances
let levelWiseChart = null;
let typeWiseChart = null;
let commissionTrendChart = null;
let statusDistributionChart = null;
let rateAnalysisChart = null;

const periodOptions = [
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'This Week' },
    { value: 'month', label: 'This Month' },
    { value: 'year', label: 'This Year' },
    { value: 'custom', label: 'Custom Range' }
];

const levelOptions = [
    { value: '1', label: 'Level 1' },
    { value: '2', label: 'Level 2' },
    { value: '3', label: 'Level 3' },
    { value: '4', label: 'Level 4' },
    { value: '5', label: 'Level 5' }
];

const formatNumber = (value) => {
    if (value === null || value === undefined) return '0.00';
    const num = parseFloat(value) || 0;

    if (num < 0.01 && num > 0) {
        return num.toString();
    }

    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
};

const formatMoney = (value) => {
    if (!value || isNaN(value)) return '0.00';
    const num = Math.abs(parseFloat(value));

    if (num >= 1e9) return (num / 1e9).toFixed(2) + 'B';
    if (num >= 1e6) return (num / 1e6).toFixed(2) + 'M';
    if (num >= 1e3) return (num / 1e3).toFixed(2) + 'K';
    return num.toFixed(2);
};

watch(selectedPeriod, (newValue) => {
    showCustomDateRange.value = newValue === 'custom';
    if (newValue !== 'custom') {
        applyFilters();
    }
});

const applyFilters = () => {
    if (isLoading.value) return;

    if (selectedPeriod.value === 'custom' && (!dateFrom.value || !dateTo.value)) {
        showToast('Please select both start and end dates', 'error');
        return;
    }

    isLoading.value = true;

    const params = {
        period: selectedPeriod.value
    };

    if (selectedPeriod.value === 'custom') {
        params.date_from = dateFrom.value;
        params.date_to = dateTo.value;
    }

    if (selectedLevel.value) {
        params.level = selectedLevel.value;
    }

    if (selectedType.value) {
        params.type = selectedType.value;
    }

    if (selectedStatus.value) {
        params.status = selectedStatus.value;
    }

    router.get('/admin/reports/commissions', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
            nextTick(() => {
                initializeCharts();
            });
        },
        onError: (errors) => {
            isLoading.value = false;
            console.error('Filter error:', errors);
            showToast('Failed to apply filters', 'error');
        }
    });
};

const clearFilters = () => {
    selectedPeriod.value = 'month';
    dateFrom.value = '';
    dateTo.value = '';
    selectedLevel.value = '';
    selectedType.value = '';
    selectedStatus.value = '';
    showCustomDateRange.value = false;
    applyFilters();
};

const exportReport = () => {
    const params = new URLSearchParams();
    params.append('report_type', 'commissions');
    params.append('period', selectedPeriod.value);

    if (selectedPeriod.value === 'custom' && dateFrom.value && dateTo.value) {
        params.append('date_from', dateFrom.value);
        params.append('date_to', dateTo.value);
    }

    if (selectedLevel.value) {
        params.append('level', selectedLevel.value);
    }

    if (selectedType.value) {
        params.append('type', selectedType.value);
    }

    if (selectedStatus.value) {
        params.append('status', selectedStatus.value);
    }

    window.location.href = `/admin/reports/export?${params.toString()}`;
    showToast('Exporting commission report...', 'info');
};

const pendingPercentage = computed(() => {
    if (props.stats.total_commissions === 0) return 0;
    return ((props.stats.pending_commissions / props.stats.total_commissions) * 100).toFixed(2);
});

const paidPercentage = computed(() => {
    if (props.stats.total_commissions === 0) return 0;
    return ((props.stats.paid_commissions / props.stats.total_commissions) * 100).toFixed(2);
});

const hasActiveFilters = computed(() => {
    return selectedLevel.value || selectedType.value || selectedStatus.value || dateFrom.value || dateTo.value;
});

// Initialize all charts
const initializeCharts = () => {
    createLevelWiseChart();
    createTypeWiseChart();
    createCommissionTrendChart();
    createStatusDistributionChart();
    createRateAnalysisChart();
};

// Level-wise Commission Chart
const createLevelWiseChart = () => {
    const ctx = document.getElementById('levelWiseChart');
    if (!ctx) return;

    if (levelWiseChart) {
        levelWiseChart.destroy();
    }

    const data = props.charts.level_wise || [];

    levelWiseChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.level),
            datasets: [
                {
                    label: 'Total Commission',
                    data: data.map(item => item.total || 0),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: '#3b82f6',
                    borderWidth: 2
                },
                {
                    label: 'Average Commission',
                    data: data.map(item => item.average || 0),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + currencySymbol.value + formatNumber(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return currencySymbol.value + formatMoney(value);
                        }
                    }
                }
            }
        }
    });
};

// Type-wise Commission Chart
const createTypeWiseChart = () => {
    const ctx = document.getElementById('typeWiseChart');
    if (!ctx) return;

    if (typeWiseChart) {
        typeWiseChart.destroy();
    }

    const data = props.charts.type_wise || [];

    typeWiseChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(item => item.name),
            datasets: [{
                data: data.map(item => item.value),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(139, 92, 246, 0.8)'
                ],
                borderColor: [
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b',
                    '#8b5cf6'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + currencySymbol.value + formatNumber(context.parsed);
                        }
                    }
                }
            }
        }
    });
};

// Commission Trend Chart
const createCommissionTrendChart = () => {
    const ctx = document.getElementById('commissionTrendChart');
    if (!ctx) return;

    if (commissionTrendChart) {
        commissionTrendChart.destroy();
    }

    const data = props.charts.commission_trend || [];

    commissionTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.date),
            datasets: [
                {
                    label: 'Commission Amount',
                    data: data.map(item => item.commissions || 0),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y',
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Transaction Count',
                    data: data.map(item => item.count || 0),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: false,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.label === 'Transaction Count') {
                                return context.dataset.label + ': ' + context.parsed.y;
                            }
                            return context.dataset.label + ': ' + currencySymbol.value + formatNumber(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return currencySymbol.value + formatMoney(value);
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
};

// Status Distribution Chart
const createStatusDistributionChart = () => {
    const ctx = document.getElementById('statusDistributionChart');
    if (!ctx) return;

    if (statusDistributionChart) {
        statusDistributionChart.destroy();
    }

    const data = props.charts.status_distribution || [];

    statusDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(item => item.name),
            datasets: [{
                data: data.map(item => item.value),
                backgroundColor: [
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)'
                ],
                borderColor: [
                    '#f59e0b',
                    '#3b82f6',
                    '#10b981'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
};

// Rate Analysis Chart
const createRateAnalysisChart = () => {
    const ctx = document.getElementById('rateAnalysisChart');
    if (!ctx) return;

    if (rateAnalysisChart) {
        rateAnalysisChart.destroy();
    }

    const data = props.charts.rate_analysis || [];

    rateAnalysisChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.level),
            datasets: [
                {
                    label: 'Average Rate (%)',
                    data: data.map(item => item.avg_rate || 0),
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: '#8b5cf6',
                    borderWidth: 2,
                    yAxisID: 'y'
                },
                {
                    label: 'Total Commission',
                    data: data.map(item => item.total_commission || 0),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.label === 'Average Rate (%)') {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '%';
                            }
                            return context.dataset.label + ': ' + currencySymbol.value + formatNumber(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return currencySymbol.value + formatMoney(value);
                        }
                    }
                }
            }
        }
    });
};

onMounted(() => {
    const page = usePage();
    const flash = page.props.flash;

    if (flash?.success) showToast(flash.success, 'success');
    if (flash?.error) showToast(flash.error, 'error');
    if (flash?.warning) showToast(flash.warning, 'warning');
    if (flash?.info) showToast(flash.info, 'info');

    nextTick(() => {
        initializeCharts();
    });
});
</script>


<template>
    <AdminLayout
        title="Commission Report"
        page-section="Reports"
    >
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Commission Report
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Comprehensive referral commission analytics and performance tracking
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors duration-200"
                        @click="exportReport"
                    >
                        <svg
                            class="w-4 h-4 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        Export Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Time Period
                        </label>
                        <select
                            v-model="selectedPeriod"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        >
                            <option
                                v-for="option in periodOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div v-if="showCustomDateRange">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Start Date
                        </label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        >
                    </div>

                    <div v-if="showCustomDateRange">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            End Date
                        </label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Level
                        </label>
                        <select
                            v-model="selectedLevel"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">All Levels</option>
                            <option
                                v-for="level in levelOptions"
                                :key="level.value"
                                :value="level.value"
                            >
                                {{ level.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Type
                        </label>
                        <select
                            v-model="selectedType"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">All Types</option>
                            <option
                                v-for="type in commission_types"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status
                        </label>
                        <select
                            v-model="selectedStatus"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">All Status</option>
                            <option
                                v-for="status in status_options"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        :disabled="isLoading"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200"
                        @click="applyFilters"
                    >
                        <span v-if="!isLoading">Apply Filters</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            Loading...
                        </span>
                    </button>

                    <button
                        v-if="hasActiveFilters"
                        class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors duration-200"
                        @click="clearFilters"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
            <!-- Total Commissions -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Commissions
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.total_commissions) }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Commissions -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Pending
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.pending_commissions) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ pendingPercentage }}%
                        </p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Paid Commissions -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Paid
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.paid_commissions) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ paidPercentage }}%
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Transactions -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Transactions
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ stats.total_transactions }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Average Commission -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Average
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatNumber(stats.average_commission) }}
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commission Trend Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Commission Trend Over Time
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Track commission amounts and transaction count over time
                </p>
            </div>
            <div class="h-80">
                <canvas id="commissionTrendChart"></canvas>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Level-wise Commission Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Level-wise Commission Distribution
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Commission breakdown by referral levels
                    </p>
                </div>
                <div class="h-80">
                    <canvas id="levelWiseChart"></canvas>
                </div>
            </div>

            <!-- Type-wise Commission Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Commission Type Distribution
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Breakdown by commission type
                    </p>
                </div>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="typeWiseChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Status Distribution Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Status Distribution
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Commission status breakdown
                    </p>
                </div>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="statusDistributionChart"></canvas>
                </div>
            </div>

            <!-- Rate Analysis Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Commission Rate Analysis
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Average rates and totals by level
                    </p>
                </div>
                <div class="h-80">
                    <canvas id="rateAnalysisChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Earners Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Top Commission Earners
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Rank
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total Earned
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Referrals
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Transactions
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        <tr
                            v-for="(earner, index) in charts.top_earners"
                            :key="earner.email"
                            class="hover:bg-gray-50 dark:hover:bg-slate-700"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                        <span
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': index === 0,
                                                'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300': index === 1,
                                                'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300': index === 2,
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': index > 2
                                            }"
                                        >
                                            <svg v-if="index === 0" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            <span v-else>{{ index + 1 }}</span>
                                        </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <div class="h-full w-full rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-semibold text-sm">
                                            {{ earner.name?.charAt(0).toUpperCase() || '?' }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ earner.name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ earner.email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        {{ currencySymbol }}{{ formatNumber(earner.total_earned) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ earner.total_referrals }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ earner.commission_count }}
                                    </span>
                            </td>
                        </tr>
                        <tr v-if="charts.top_earners.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                No commission earner data available for the selected period
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.5);
}

.dark ::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.dark ::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

@media (max-width: 768px) {
    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-5,
    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-6 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-5,
    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-6 {
        grid-template-columns: 1fr;
    }
}

.h-80 {
    position: relative;
}

.overflow-x-auto {
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
    .overflow-x-auto table {
        min-width: 800px;
    }
}

.transition-colors {
    transition-property: color, background-color, border-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

button:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
}

button:focus-visible {
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px #3b82f6;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.bg-white.dark\:bg-slate-800 {
    animation: fadeIn 0.3s ease-out;
}

@keyframes countUp {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.text-2xl.font-bold {
    animation: countUp 0.5s ease-out;
}

tbody tr {
    transition: background-color 0.15s ease-in-out;
}

@media print {
    .no-print {
        display: none !important;
    }

    .bg-white.dark\:bg-slate-800 {
        box-shadow: none;
        border: 1px solid #e5e7eb;
    }

    * {
        color: #000 !important;
        background: #fff !important;
    }
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

*:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

.skeleton {
    animation: shimmer 2s infinite;
    background: linear-gradient(
        to right,
        #f3f4f6 0%,
        #e5e7eb 20%,
        #f3f4f6 40%,
        #f3f4f6 100%
    );
    background-size: 1000px 100%;
}

.dark .skeleton {
    background: linear-gradient(
        to right,
        #1e293b 0%,
        #334155 20%,
        #1e293b 40%,
        #1e293b 100%
    );
}

@media (max-width: 1024px) {
    .h-80 {
        height: 20rem;
    }
}

@media (max-width: 768px) {
    .h-80 {
        height: 16rem;
    }
}

.custom-tooltip {
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    pointer-events: none;
}

button:hover svg {
    transform: translateY(-1px);
    transition: transform 0.2s ease-in-out;
}

.shadow-sm {
    transition: box-shadow 0.3s ease-in-out;
}

.shadow-sm:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.dark .shadow-sm:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
}

@keyframes pulse-soft {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

.p-3.rounded-lg {
    animation: pulse-soft 3s ease-in-out infinite;
}

@media (max-width: 640px) {
    .gap-6 {
        gap: 1rem;
    }
}

.tabular-nums {
    font-variant-numeric: tabular-nums;
}

html {
    scroll-behavior: smooth;
}

::selection {
    background-color: rgba(59, 130, 246, 0.3);
    color: inherit;
}

.dark ::selection {
    background-color: rgba(59, 130, 246, 0.5);
}

/* Table enhancements */
table {
    border-collapse: separate;
    border-spacing: 0;
}

table thead tr th:first-child {
    border-top-left-radius: 0.5rem;
}

table thead tr th:last-child {
    border-top-right-radius: 0.5rem;
}

table tbody tr:last-child td:first-child {
    border-bottom-left-radius: 0.5rem;
}

table tbody tr:last-child td:last-child {
    border-bottom-right-radius: 0.5rem;
}

/* Gradient backgrounds for stats cards */
.gradient-blue {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.gradient-yellow {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.gradient-green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.gradient-purple {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

/* Loading skeleton for table */
.table-skeleton {
    height: 40px;
    background: linear-gradient(
        90deg,
        #f3f4f6 25%,
        #e5e7eb 50%,
        #f3f4f6 75%
    );
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.dark .table-skeleton {
    background: linear-gradient(
        90deg,
        #1e293b 25%,
        #334155 50%,
        #1e293b 75%
    );
    background-size: 200% 100%;
}

/* Chart container improvements */
canvas {
    max-width: 100%;
    height: auto;
}

/* Responsive grid adjustments */
@media (max-width: 1280px) {
    .lg\:grid-cols-6 {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 1024px) {
    .lg\:grid-cols-6 {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Filter section enhancements */
.filter-active {
    position: relative;
}

.filter-active::after {
    content: '';
    position: absolute;
    top: -4px;
    right: -4px;
    width: 8px;
    height: 8px;
    background-color: #3b82f6;
    border-radius: 50%;
    border: 2px solid white;
}

.dark .filter-active::after {
    border-color: #1e293b;
}

/* Stat card hover effects */
.stat-card {
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.dark .stat-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
}

/* Empty state styling */
.empty-state {
    padding: 3rem 1rem;
    text-align: center;
}

.empty-state svg {
    margin: 0 auto 1rem;
    opacity: 0.5;
}

/* Tooltip styling for charts */
.chartjs-tooltip {
    opacity: 1;
    position: absolute;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    border-radius: 6px;
    pointer-events: none;
    transform: translate(-50%, -100%);
    transition: all 0.1s ease;
    padding: 8px 12px;
    font-size: 13px;
}

.dark .chartjs-tooltip {
    background: rgba(15, 23, 42, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Rank badge with medal icon */
.rank-badge {
    position: relative;
    overflow: hidden;
}

.rank-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.4),
        transparent
    );
    animation: rank-shine 3s infinite;
}

@keyframes rank-shine {
    0% {
        left: -100%;
    }
    50%, 100% {
        left: 100%;
    }
}

/* Commission status badges */
.status-pending {
    background-color: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.status-approved {
    background-color: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.status-paid {
    background-color: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.dark .status-pending {
    background-color: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

.dark .status-approved {
    background-color: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.dark .status-paid {
    background-color: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

/* Performance optimizations */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Commission level indicator */
.level-indicator {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.level-indicator::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
}

.level-1::before {
    background: #3b82f6;
    box-shadow: 0 0 8px #3b82f6;
}

.level-2::before {
    background: #10b981;
    box-shadow: 0 0 8px #10b981;
}

.level-3::before {
    background: #f59e0b;
    box-shadow: 0 0 8px #f59e0b;
}

.level-4::before {
    background: #8b5cf6;
    box-shadow: 0 0 8px #8b5cf6;
}

.level-5::before {
    background: #ec4899;
    box-shadow: 0 0 8px #ec4899;
}

/* Top earner highlight */
.top-earner-row {
    position: relative;
}

.top-earner-row::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 3px;
    height: 100%;
    background: linear-gradient(180deg, #f59e0b, #d97706);
}

/* Commission type badge */
.commission-type-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Earnings animation */
@keyframes earnings-pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.earnings-amount {
    animation: earnings-pulse 2s ease-in-out infinite;
}

/* Chart legend improvements */
.chart-legend-item {
    display: inline-flex;
    align-items: center;
    margin-right: 1rem;
    font-size: 0.875rem;
}

.chart-legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    margin-right: 0.5rem;
}

/* Responsive table improvements */
@media (max-width: 640px) {
    table thead {
        display: none;
    }

    table, table tbody, table tr, table td {
        display: block;
        width: 100%;
    }

    table tr {
        margin-bottom: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
    }

    .dark table tr {
        border-color: #334155;
    }

    table td {
        text-align: right;
        padding: 0.5rem 0;
        position: relative;
        padding-left: 50%;
    }

    table td::before {
        content: attr(data-label);
        position: absolute;
        left: 0;
        width: 45%;
        padding-right: 10px;
        text-align: left;
        font-weight: 600;
    }
}

/* Accessibility improvements */
.skip-to-content {
    position: absolute;
    top: -40px;
    left: 0;
    background: #3b82f6;
    color: white;
    padding: 8px;
    text-decoration: none;
    z-index: 100;
}

.skip-to-content:focus {
    top: 0;
}

/* Percentage indicator with animation */
.percentage-indicator {
    position: relative;
    display: inline-block;
}

.percentage-bar {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 4px;
}

.percentage-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    border-radius: 2px;
    transition: width 1s ease-out;
}

/* Table row animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

tbody tr {
    animation: slideIn 0.3s ease-out;
    animation-fill-mode: both;
}

tbody tr:nth-child(1) { animation-delay: 0.05s; }
tbody tr:nth-child(2) { animation-delay: 0.1s; }
tbody tr:nth-child(3) { animation-delay: 0.15s; }
tbody tr:nth-child(4) { animation-delay: 0.2s; }
tbody tr:nth-child(5) { animation-delay: 0.25s; }
tbody tr:nth-child(6) { animation-delay: 0.3s; }
tbody tr:nth-child(7) { animation-delay: 0.35s; }
tbody tr:nth-child(8) { animation-delay: 0.4s; }
tbody tr:nth-child(9) { animation-delay: 0.45s; }
tbody tr:nth-child(10) { animation-delay: 0.5s; }

/* Medal icon styling */
.medal-icon {
    filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.5));
}

/* Commission card styling */
.commission-card {
    position: relative;
    transition: all 0.3s ease;
}

.commission-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.1);
}

.dark .commission-card:hover {
    box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.4);
}

/* Grid layout improvements */
@media (max-width: 1024px) {
    .lg\:grid-cols-5 {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .lg\:grid-cols-5 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
}
</style>

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
            total_stakes: 0,
            total_staked_amount: 0,
            total_rewards_paid: 0,
            active_stakes: 0,
            total_current_balance: 0,
            average_stake: 0
        })
    },
    charts: {
        type: Object,
        required: true,
        default: () => ({
            pool_wise: [],
            staking_trend: [],
            status_distribution: [],
            rewards_timeline: [],
            apy_performance: []
        })
    },
    filters: {
        type: Object,
        default: () => ({
            period: 'month',
            date_from: null,
            date_to: null,
            pool_id: null,
            status: null
        })
    },
    staking_pools: {
        type: Array,
        default: () => []
    },
    status_options: {
        type: Array,
        default: () => [
            { value: 'active', label: 'Active' },
            { value: 'completed', label: 'Completed' },
            { value: 'cancelled', label: 'Cancelled' }
        ]
    }
});

const { currencySymbol } = useSettings();
const { showToast } = useToast();

const isLoading = ref(false);
const selectedPeriod = ref(props.filters?.period || 'month');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const selectedPoolId = ref(props.filters?.pool_id || '');
const selectedStatus = ref(props.filters?.status || '');
const showCustomDateRange = ref(selectedPeriod.value === 'custom');

// Chart instances
let poolWiseChart = null;
let stakingTrendChart = null;
let statusDistributionChart = null;
let rewardsTimelineChart = null;
let apyPerformanceChart = null;

const periodOptions = [
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'This Week' },
    { value: 'month', label: 'This Month' },
    { value: 'year', label: 'This Year' },
    { value: 'custom', label: 'Custom Range' }
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

    if (selectedPoolId.value) {
        params.pool_id = selectedPoolId.value;
    }

    if (selectedStatus.value) {
        params.status = selectedStatus.value;
    }

    router.get('/admin/reports/staking', params, {
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
    selectedPoolId.value = '';
    selectedStatus.value = '';
    showCustomDateRange.value = false;
    applyFilters();
};

const exportReport = () => {
    const params = new URLSearchParams();
    params.append('report_type', 'staking');
    params.append('period', selectedPeriod.value);

    if (selectedPeriod.value === 'custom' && dateFrom.value && dateTo.value) {
        params.append('date_from', dateFrom.value);
        params.append('date_to', dateTo.value);
    }

    if (selectedPoolId.value) {
        params.append('pool_id', selectedPoolId.value);
    }

    if (selectedStatus.value) {
        params.append('status', selectedStatus.value);
    }

    window.location.href = `/admin/reports/export?${params.toString()}`;
    showToast('Exporting staking report...', 'info');
};

const totalAPY = computed(() => {
    if (props.stats.total_staked_amount === 0) return 0;
    return ((props.stats.total_rewards_paid / props.stats.total_staked_amount) * 100).toFixed(2);
});

const averageReward = computed(() => {
    if (props.stats.total_stakes === 0) return 0;
    return (props.stats.total_rewards_paid / props.stats.total_stakes).toFixed(2);
});

const hasActiveFilters = computed(() => {
    return selectedPoolId.value || selectedStatus.value || dateFrom.value || dateTo.value;
});

// Initialize all charts
const initializeCharts = () => {
    createPoolWiseChart();
    createStakingTrendChart();
    createStatusDistributionChart();
    createRewardsTimelineChart();
    createAPYPerformanceChart();
};

// Pool-wise Performance Chart
const createPoolWiseChart = () => {
    const ctx = document.getElementById('poolWiseChart');
    if (!ctx) return;

    if (poolWiseChart) {
        poolWiseChart.destroy();
    }

    const data = props.charts.pool_wise || [];

    poolWiseChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.pool_name),
            datasets: [
                {
                    label: 'Total Staked',
                    data: data.map(item => item.total_staked || 0),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: '#3b82f6',
                    borderWidth: 2
                },
                {
                    label: 'Total Rewards',
                    data: data.map(item => item.total_rewards || 0),
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

// Staking Trend Chart
const createStakingTrendChart = () => {
    const ctx = document.getElementById('stakingTrendChart');
    if (!ctx) return;

    if (stakingTrendChart) {
        stakingTrendChart.destroy();
    }

    const data = props.charts.staking_trend || [];

    stakingTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.date),
            datasets: [
                {
                    label: 'Amount Staked',
                    data: data.map(item => item.staked || 0),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Rewards Paid',
                    data: data.map(item => item.rewards || 0),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Stake Count',
                    data: data.map(item => item.count || 0),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
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
                            if (context.dataset.label === 'Stake Count') {
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
        type: 'pie',
        data: {
            labels: data.map(item => item.name),
            datasets: [{
                data: data.map(item => item.value),
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderColor: [
                    '#10b981',
                    '#3b82f6',
                    '#ef4444'
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

// Rewards Timeline Chart
const createRewardsTimelineChart = () => {
    const ctx = document.getElementById('rewardsTimelineChart');
    if (!ctx) return;

    if (rewardsTimelineChart) {
        rewardsTimelineChart.destroy();
    }

    const data = props.charts.rewards_timeline || [];

    rewardsTimelineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.date),
            datasets: [{
                label: 'Daily Rewards',
                data: data.map(item => item.rewards || 0),
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rewards: ' + currencySymbol.value + formatNumber(context.parsed.y);
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

// APY Performance Chart
const createAPYPerformanceChart = () => {
    const ctx = document.getElementById('apyPerformanceChart');
    if (!ctx) return;

    if (apyPerformanceChart) {
        apyPerformanceChart.destroy();
    }

    const data = props.charts.apy_performance || [];

    apyPerformanceChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: data.map(item => item.pool_name),
            datasets: [
                {
                    label: 'Expected APY',
                    data: data.map(item => item.expected_apy || 0),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#3b82f6'
                },
                {
                    label: 'Actual Return',
                    data: data.map(item => item.actual_return || 0),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#10b981'
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
                            return context.dataset.label + ': ' + context.parsed.r.toFixed(2) + '%';
                        }
                    }
                }
            },
            scales: {
                r: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
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
        title="Staking Report"
        page-section="Reports"
    >
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Staking Report
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Comprehensive staking analytics and pool performance metrics
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
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
                            Staking Pool
                        </label>
                        <select
                            v-model="selectedPoolId"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">All Pools</option>
                            <option
                                v-for="pool in staking_pools"
                                :key="pool.value"
                                :value="pool.value"
                            >
                                {{ pool.label }}
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Stakes -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Stakes
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ stats.total_stakes }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            All time count
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Staked Amount -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Staked
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.total_staked_amount) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Locked capital
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Rewards Paid -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Rewards Paid
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.total_rewards_paid) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            APY: {{ totalAPY }}%
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Stakes -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Active Stakes
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ stats.active_stakes }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Currently locked
                        </p>
                    </div>
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <svg class="h-8 w-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Current Balance -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Current Balance
                        </p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.total_current_balance) }}
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Average Stake -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Average Stake
                        </p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatNumber(stats.average_stake) }}
                        </p>
                    </div>
                    <div class="p-3 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Average Reward -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Average Reward
                        </p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatNumber(averageReward) }}
                        </p>
                    </div>
                    <div class="p-3 bg-teal-100 dark:bg-teal-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Pool-wise Performance Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Pool-wise Performance
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Staking amount and rewards comparison by pool
                    </p>
                </div>
                <div class="h-80">
                    <canvas id="poolWiseChart"></canvas>
                </div>
            </div>

            <!-- Status Distribution Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Status Distribution
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Breakdown of stakes by status
                    </p>
                </div>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="statusDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Staking Trend Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Staking Trend Over Time
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Track staking amounts, rewards, and count over time
                </p>
            </div>
            <div class="h-80">
                <canvas id="stakingTrendChart"></canvas>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Rewards Timeline Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Rewards Timeline
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Daily rewards distribution over time
                    </p>
                </div>
                <div class="h-80">
                    <canvas id="rewardsTimelineChart"></canvas>
                </div>
            </div>

            <!-- APY Performance Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        APY Performance Comparison
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Expected vs actual APY returns by pool
                    </p>
                </div>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="apyPerformanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Pool Performance Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Detailed Pool Performance
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Pool Name
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                APY Rate
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Count
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total Staked
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total Rewards
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Avg Stake
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        <tr
                            v-for="pool in charts.pool_wise"
                            :key="pool.pool_name"
                            class="hover:bg-gray-50 dark:hover:bg-slate-700"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-2 w-2 rounded-full bg-blue-500 mr-2"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ pool.pool_name }}
                                        </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300"
                                    >
                                        {{ pool.apy_rate }}%
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ pool.count }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                        {{ currencySymbol }}{{ formatNumber(pool.total_staked) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        {{ currencySymbol }}{{ formatNumber(pool.total_rewards) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ currencySymbol }}{{ formatNumber(pool.avg_stake) }}
                                    </span>
                            </td>
                        </tr>
                        <tr v-if="charts.pool_wise.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                No data available for the selected period
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
    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }

    .grid-cols-1.md\:grid-cols-3 {
        grid-template-columns: 1fr;
    }

    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-5 {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-4 {
        grid-template-columns: 1fr;
    }
}

.h-80 {
    position: relative;
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

.gradient-green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.gradient-purple {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.gradient-orange {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
    .lg\:grid-cols-5 {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 1024px) {
    .lg\:grid-cols-5 {
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

/* APY Badge colors */
.apy-badge-high {
    background-color: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.apy-badge-medium {
    background-color: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.apy-badge-low {
    background-color: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.dark .apy-badge-high {
    background-color: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

.dark .apy-badge-medium {
    background-color: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.dark .apy-badge-low {
    background-color: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
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

/* Staking specific styles */
.stake-locked-icon {
    animation: lock-pulse 2s ease-in-out infinite;
}

@keyframes lock-pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* Reward indicator */
.reward-indicator {
    position: relative;
    overflow: hidden;
}

.reward-indicator::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    animation: shimmer-reward 3s infinite;
}

@keyframes shimmer-reward {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

/* Pool card styling */
.pool-card {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.pool-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, #3b82f6, #10b981);
}

.pool-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.15);
}

.dark .pool-card:hover {
    box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.5);
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

/* Status indicators */
.status-active {
    color: #10b981;
}

.status-completed {
    color: #3b82f6;
}

.status-cancelled {
    color: #ef4444;
}

/* Tooltip arrow */
.tooltip-arrow {
    position: absolute;
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid rgba(0, 0, 0, 0.8);
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
}
</style>

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
            total_deposits: 0,
            total_withdrawals: 0,
            total_investments: 0,
            total_earnings_paid: 0,
            total_commissions: 0,
            net_balance: 0
        })
    },
    charts: {
        type: Object,
        required: true,
        default: () => ({
            daily_transactions: [],
            revenue_expense: {},
            monthly_comparison: [],
            transaction_distribution: [],
            cash_flow_timeline: []
        })
    },
    filters: {
        type: Object,
        default: () => ({
            period: 'month',
            date_from: null,
            date_to: null
        })
    }
});

const { currencySymbol } = useSettings();
const { showToast } = useToast();

const isLoading = ref(false);
const selectedPeriod = ref(props.filters?.period || 'month');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const showCustomDateRange = ref(selectedPeriod.value === 'custom');

// Chart instances
let dailyTransactionsChart = null;
let revenueExpenseChart = null;
let monthlyComparisonChart = null;
let transactionDistributionChart = null;
let cashFlowTimelineChart = null;

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

    router.get('/admin/reports/financial', params, {
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

const exportReport = () => {
    const params = new URLSearchParams();
    params.append('report_type', 'financial');
    params.append('period', selectedPeriod.value);

    if (selectedPeriod.value === 'custom' && dateFrom.value && dateTo.value) {
        params.append('date_from', dateFrom.value);
        params.append('date_to', dateTo.value);
    }

    window.location.href = `/admin/reports/export?${params.toString()}`;
    showToast('Exporting financial report...', 'info');
};

const totalRevenue = computed(() => {
    return props.stats.total_deposits + props.stats.total_investments;
});

const totalExpenses = computed(() => {
    return props.stats.total_withdrawals + props.stats.total_earnings_paid + props.stats.total_commissions;
});

const profitMargin = computed(() => {
    if (totalRevenue.value === 0) return 0;
    return ((props.stats.net_balance / totalRevenue.value) * 100).toFixed(2);
});

// Initialize all charts
const initializeCharts = () => {
    createDailyTransactionsChart();
    createRevenueExpenseChart();
    createMonthlyComparisonChart();
    createTransactionDistributionChart();
    createCashFlowTimelineChart();
};

// Daily Transactions Chart
const createDailyTransactionsChart = () => {
    const ctx = document.getElementById('dailyTransactionsChart');
    if (!ctx) return;

    if (dailyTransactionsChart) {
        dailyTransactionsChart.destroy();
    }

    const data = props.charts.daily_transactions || [];

    dailyTransactionsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.date),
            datasets: [
                {
                    label: 'Deposits',
                    data: data.map(item => item.deposit || 0),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Withdrawals',
                    data: data.map(item => item.withdrawal || 0),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Investments',
                    data: data.map(item => item.stake || 0),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
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
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
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
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
};

// Revenue vs Expense Chart
const createRevenueExpenseChart = () => {
    const ctx = document.getElementById('revenueExpenseChart');
    if (!ctx) return;

    if (revenueExpenseChart) {
        revenueExpenseChart.destroy();
    }

    const data = props.charts.revenue_expense || { revenue: {}, expenses: {} };

    revenueExpenseChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Revenue', 'Expenses'],
            datasets: [{
                data: [
                    (data.revenue.deposits || 0) + (data.revenue.investments || 0),
                    (data.expenses.withdrawals || 0) + (data.expenses.earnings_paid || 0) + (data.expenses.commissions || 0)
                ],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderColor: [
                    '#10b981',
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
                            return context.label + ': ' + currencySymbol.value + formatNumber(context.parsed);
                        }
                    }
                }
            }
        }
    });
};

// Monthly Comparison Chart
const createMonthlyComparisonChart = () => {
    const ctx = document.getElementById('monthlyComparisonChart');
    if (!ctx) return;

    if (monthlyComparisonChart) {
        monthlyComparisonChart.destroy();
    }

    const data = props.charts.monthly_comparison || [];

    monthlyComparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.period),
            datasets: [{
                label: 'Deposits',
                data: data.map(item => item.deposits || 0),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: '#3b82f6',
                borderWidth: 2
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
                            return 'Deposits: ' + currencySymbol.value + formatNumber(context.parsed.y);
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

// Transaction Distribution Chart
const createTransactionDistributionChart = () => {
    const ctx = document.getElementById('transactionDistributionChart');
    if (!ctx) return;

    if (transactionDistributionChart) {
        transactionDistributionChart.destroy();
    }

    const data = props.charts.transaction_distribution || [];

    transactionDistributionChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(item => item.name),
            datasets: [{
                data: data.map(item => item.value),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)'
                ],
                borderColor: [
                    '#3b82f6',
                    '#10b981',
                    '#ef4444',
                    '#f59e0b',
                    '#8b5cf6',
                    '#ec4899'
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

// Cash Flow Timeline Chart
const createCashFlowTimelineChart = () => {
    const ctx = document.getElementById('cashFlowTimelineChart');
    if (!ctx) return;

    if (cashFlowTimelineChart) {
        cashFlowTimelineChart.destroy();
    }

    const data = props.charts.cash_flow_timeline || [];

    cashFlowTimelineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.date),
            datasets: [{
                label: 'Net Cash Flow',
                data: data.map(item => item.net_flow || 0),
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
                            const value = context.parsed.y;
                            const sign = value >= 0 ? '+' : '';
                            return 'Cash Flow: ' + sign + currencySymbol.value + formatNumber(Math.abs(value));
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            const sign = value >= 0 ? '+' : '-';
                            return sign + currencySymbol.value + formatMoney(Math.abs(value));
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
        title="Financial Report"
        page-section="Reports"
    >
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Financial Report
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Comprehensive financial overview and analytics
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

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 mb-6">
            <div class="p-6">
                <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                    <div class="flex-1">
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

                    <div v-if="showCustomDateRange" class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Start Date
                        </label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        >
                    </div>

                    <div v-if="showCustomDateRange" class="flex-1">
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
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Deposits
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.total_deposits) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Revenue stream
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Withdrawals
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.total_withdrawals) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Outgoing funds
                        </p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Investments
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.total_investments) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Active capital
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Net Balance
                        </p>
                        <p class="text-2xl font-bold mt-1" :class="stats.net_balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                            {{ stats.net_balance >= 0 ? '+' : '' }}{{ currencySymbol }}{{ formatMoney(stats.net_balance) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Profit margin: {{ profitMargin }}%
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Earnings Paid
                        </p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.total_earnings_paid) }}
                        </p>
                    </div>
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Commissions Paid
                        </p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(stats.total_commissions) }}
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Expenses
                        </p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatMoney(totalExpenses) }}
                        </p>
                    </div>
                    <div class="p-3 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Daily Transactions
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Track daily deposits, withdrawals, and investments
                    </p>
                </div>
                <div class="h-80">
                    <canvas id="dailyTransactionsChart"></canvas>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Revenue vs Expenses
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Comparison of total revenue and expenses
                    </p>
                </div>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="revenueExpenseChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Monthly Deposits Trend
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Historical deposit trends over the past months
                    </p>
                </div>
                <div class="h-80">
                    <canvas id="monthlyComparisonChart"></canvas>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Transaction Distribution
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Breakdown of transaction types by volume
                    </p>
                </div>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="transactionDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Cash Flow Timeline
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Net cash flow over time (positive indicates profit, negative indicates loss)
                </p>
            </div>
            <div class="h-80">
                <canvas id="cashFlowTimelineChart"></canvas>
            </div>
        </div>

        <!-- Summary Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Financial Summary
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Category
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Amount
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-2 w-2 rounded-full bg-green-500 mr-2"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Total Deposits</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        +{{ currencySymbol }}{{ formatNumber(stats.total_deposits) }}
                                    </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-2 w-2 rounded-full bg-blue-500 mr-2"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Total Investments</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                        +{{ currencySymbol }}{{ formatNumber(stats.total_investments) }}
                                    </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-2 w-2 rounded-full bg-red-500 mr-2"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Total Withdrawals</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                        -{{ currencySymbol }}{{ formatNumber(stats.total_withdrawals) }}
                                    </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-2 w-2 rounded-full bg-orange-500 mr-2"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Earnings Paid</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">
                                        -{{ currencySymbol }}{{ formatNumber(stats.total_earnings_paid) }}
                                    </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-2 w-2 rounded-full bg-indigo-500 mr-2"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Commissions Paid</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                        -{{ currencySymbol }}{{ formatNumber(stats.total_commissions) }}
                                    </span>
                            </td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-slate-900 font-semibold border-t-2 border-gray-300 dark:border-slate-600">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">Net Balance</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-bold" :class="stats.net_balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        {{ stats.net_balance >= 0 ? '+' : '' }}{{ currencySymbol }}{{ formatNumber(stats.net_balance) }}
                                    </span>
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
}

@media (max-width: 640px) {
    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-4 {
        grid-template-columns: 1fr;
    }
}

.h-80 {
    position: relative;
}

.overflow-x-auto {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
    .overflow-x-auto table {
        min-width: 600px;
    }
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.transition-colors {
    transition-property: color, background-color, border-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

.bg-white.dark\:bg-slate-800:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease-in-out;
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

.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background-color: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.badge-danger {
    background-color: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.badge-info {
    background-color: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.badge-warning {
    background-color: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.dark .badge-success {
    background-color: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

.dark .badge-danger {
    background-color: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

.dark .badge-info {
    background-color: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.dark .badge-warning {
    background-color: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

tbody tr {
    transition: background-color 0.15s ease-in-out;
}

.gradient-green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.gradient-red {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.gradient-blue {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.gradient-purple {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
</style>

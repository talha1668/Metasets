<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import { useSettings } from "@/composables/useSettings.js";
import { useToast } from "@/composables/useToast.js";

const props = defineProps({
    investment: {
        type: Object,
        required: true
    }
});

const { currencySymbol } = useSettings();
const { showToast } = useToast();

const isProcessing = ref(false);
const showActionModal = ref(false);
const actionType = ref('');
const cancelReason = ref('');

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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

const formatShortDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    }).format(date);
};

const formatStatus = (status) => {
    switch (status) {
        case 'pending': return 'Pending';
        case 'active': return 'Active';
        case 'completed': return 'Completed';
        case 'cancelled': return 'Cancelled';
        default: return status || 'Unknown';
    }
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-700',
        'active': 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700',
        'completed': 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
        'cancelled': 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700'
    };
    return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-700';
};

const getStatusDotClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-500',
        'active': 'bg-green-500',
        'completed': 'bg-blue-500',
        'cancelled': 'bg-red-500'
    };
    return classes[status] || 'bg-gray-500';
};

const progressPercentage = computed(() => {
    if (!props.investment.total_returns_expected || props.investment.total_returns_expected === 0) return 0;
    return Math.round((props.investment.returns_paid / props.investment.total_returns_expected) * 100);
});

const earningsPercentage = computed(() => {
    if (!props.investment.expected_return || props.investment.expected_return === 0) return 0;
    return Math.round((props.investment.earned_amount / props.investment.expected_return) * 100);
});

const remainingReturns = computed(() => {
    return props.investment.total_returns_expected - props.investment.returns_paid;
});

const daysElapsed = computed(() => {
    if (!props.investment.started_at) return 0;
    const start = new Date(props.investment.started_at);
    const now = new Date();
    const diff = Math.floor((now - start) / (1000 * 60 * 60 * 24));
    return diff;
});

const daysRemaining = computed(() => {
    if (!props.investment.maturity_date) return 0;
    const maturity = new Date(props.investment.maturity_date);
    const now = new Date();
    const diff = Math.floor((maturity - now) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 0;
});

const goBack = () => {
    router.get('/admin/investment-logs');
};

const openActionModal = (type) => {
    actionType.value = type;
    cancelReason.value = '';
    showActionModal.value = true;
};

const closeActionModal = () => {
    showActionModal.value = false;
    actionType.value = '';
    cancelReason.value = '';
};

const confirmAction = () => {
    if (actionType.value === 'cancel' && !cancelReason.value.trim()) {
        showToast('Please provide a reason for cancellation', 'error');
        return;
    }

    isProcessing.value = true;

    const endpoint = `/admin/investment-logs/${props.investment.id}/${actionType.value}`;
    const data = actionType.value === 'cancel' ? { reason: cancelReason.value } : {};

    router.post(endpoint, data, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeActionModal();
            showToast(`Investment ${actionType.value}d successfully`, 'success');
        },
        onError: (errors) => {
            showToast(`Failed to ${actionType.value} investment`, 'error');
        },
        onFinish: () => isProcessing.value = false
    });
};

const parseFeatures = (features) => {
    if (!features) return [];
    if (Array.isArray(features)) return features;
    if (typeof features === 'string') {
        try {
            return JSON.parse(features);
        } catch {
            return [];
        }
    }
    return [];
};

const viewUser = () => {
    router.get(`/admin/users/${props.investment.user_id}`);
};

const viewPlan = () => {
    router.get(`/admin/investment-plans/${props.investment.plan_id}/edit`);
};
</script>

<template>
    <AdminLayout
        title="Investment Details"
        page-section="Investments"
    >
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <button
                            class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                            @click="goBack"
                        >
                            <svg
                                class="w-5 h-5 mr-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                />
                            </svg>
                            Back to Investment Logs
                        </button>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Investment Details
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Complete information about investment #{{ investment.investment_id }}
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                    <span
                        class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-lg items-center border"
                        :class="getStatusClass(investment.status)"
                    >
                        <span
                            class="w-2 h-2 rounded-full mr-2"
                            :class="getStatusDotClass(investment.status)"
                        />
                        {{ formatStatus(investment.status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div v-if="['pending', 'active'].includes(investment.status)" class="mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        v-if="investment.status === 'pending'"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200"
                        @click="openActionModal('approve')"
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
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        Approve Investment
                    </button>

                    <button
                        v-if="investment.status === 'active'"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
                        @click="openActionModal('complete')"
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
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        Mark as Completed
                    </button>

                    <button
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200"
                        @click="openActionModal('cancel')"
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
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                        Cancel Investment
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Investment Overview -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Investment Overview
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Investment ID</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ investment.investment_id }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Investment Amount</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ currencySymbol }}{{ formatNumber(investment.amount) }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Expected Return</label>
                            <p class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400">
                                {{ currencySymbol }}{{ formatNumber(investment.expected_return) }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Earned Amount</label>
                            <p class="mt-1 text-lg font-semibold text-blue-600 dark:text-blue-400">
                                {{ currencySymbol }}{{ formatNumber(investment.earned_amount) }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Withdrawn Amount</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ currencySymbol }}{{ formatNumber(investment.withdrawn_amount) }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Remaining Principal</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ currencySymbol }}{{ formatNumber(investment.remaining_principal) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Progress Information -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Investment Progress
                    </h2>

                    <!-- Returns Progress -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Returns Paid</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ investment.returns_paid }} / {{ investment.total_returns_expected }} ({{ progressPercentage }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-3">
                            <div
                                class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300"
                                :style="{ width: `${progressPercentage}%` }"
                            ></div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ remainingReturns }} returns remaining
                        </p>
                    </div>

                    <!-- Earnings Progress -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Earnings Progress</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ currencySymbol }}{{ formatNumber(investment.earned_amount) }} / {{ currencySymbol }}{{ formatNumber(investment.expected_return) }} ({{ earningsPercentage }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-3">
                            <div
                                class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300"
                                :style="{ width: `${earningsPercentage}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-slate-700">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ daysElapsed }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Days Elapsed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ investment.plan?.duration_days || 0 }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Duration</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                {{ daysRemaining }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Days Remaining</div>
                        </div>
                    </div>
                </div>

                <!-- Investment Plan Details -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Investment Plan
                        </h2>
                        <button
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                            @click="viewPlan"
                        >
                            View Plan Details
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Plan Name</label>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                                {{ investment.plan?.name || 'N/A' }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Interest Rate</label>
                                <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                                    {{ investment.plan?.interest_rate || 0 }}%
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Interest Type</label>
                                <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white capitalize">
                                    {{ investment.plan?.interest_type || 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Return Type</label>
                                <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white capitalize">
                                    {{ investment.plan?.return_type || 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Principal Return</label>
                                <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white capitalize">
                                    {{ investment.plan?.principal_return === 'yes' ? 'Returned' : investment.plan?.principal_return === 'no' ? 'Not Returned' : 'After Maturity' }}
                                </p>
                            </div>
                        </div>
                        <div v-if="investment.plan?.description">
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Description</label>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {{ investment.plan.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Investment Settings -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Investment Settings
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto Reinvest</span>
                            <span class="text-sm font-semibold" :class="investment.auto_reinvest ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                                {{ investment.auto_reinvest ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div v-if="investment.auto_reinvest" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Reinvest Percentage</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ investment.reinvest_percentage }}%
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Return Amount</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ currencySymbol }}{{ formatNumber(investment.return_amount) }}
                            </span>
                        </div>
                        <div v-if="investment.reinvested_amount > 0" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Reinvested Amount</span>
                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                {{ currencySymbol }}{{ formatNumber(investment.reinvested_amount) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- AI & Additional Info -->
                <div v-if="investment.ai_prediction_data || investment.ai_confidence_score" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        AI Insights
                    </h2>
                    <div class="space-y-4">
                        <div v-if="investment.ai_confidence_score" class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">AI Confidence Score</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-32 bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                                    <div
                                        class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 rounded-full"
                                        :style="{ width: `${investment.ai_confidence_score}%` }"
                                    ></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ investment.ai_confidence_score }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="investment.notes" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Notes
                    </h2>
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ investment.notes }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Investor
                        </h2>
                    </div>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white text-2xl font-bold mb-3">
                            {{ investment.user?.name?.charAt(0).toUpperCase() || '?' }}
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ investment.user?.name || 'N/A' }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ investment.user?.email || 'N/A' }}
                        </p>
                        <div v-if="investment.user?.uid" class="mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-300">
                                UID: {{ investment.user.uid }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Timeline
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Created</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(investment.created_at) }}</p>
                            </div>
                        </div>

                        <div v-if="investment.approved_at" class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Approved</p>
                                <p class="text-xstext-gray-500 dark:text-gray-400">{{ formatDate(investment.approved_at) }}</p>
                            </div>
                        </div>

                        <div v-if="investment.started_at" class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Started</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(investment.started_at) }}</p>
                            </div>
                        </div>

                        <div v-if="investment.next_return_at" class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Next Return</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(investment.next_return_at) }}</p>
                            </div>
                        </div>

                        <div v-if="investment.maturity_date" class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/30">
                                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Maturity Date</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(investment.maturity_date) }}</p>
                            </div>
                        </div>

                        <div v-if="investment.completed_at" class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Completed</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(investment.completed_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Additional Info
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-slate-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Source</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                {{ investment.source || 'Web' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-slate-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Returns</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ investment.total_returns_expected || 0 }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-slate-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Returns Paid</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ investment.returns_paid || 0 }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Last Updated</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatShortDate(investment.updated_at) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl shadow-sm p-6 text-white">
                    <h3 class="text-lg font-semibold mb-4">Quick Stats</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-100">ROI</span>
                            <span class="text-lg font-bold">
                                {{ investment.amount > 0 ? ((investment.expected_return / investment.amount - 1) * 100).toFixed(2) : 0 }}%
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-100">Daily Return</span>
                            <span class="text-lg font-bold">
                                {{ currencySymbol }}{{ formatNumber(investment.return_amount) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-100">Completion</span>
                            <span class="text-lg font-bold">{{ progressPercentage }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Modal -->
        <div
            v-if="showActionModal"
            class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
            @click="closeActionModal"
        >
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 ease-out"
                @click.stop
            >
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full mb-4"
                         :class="{
                             'bg-green-100 dark:bg-green-900/30': actionType === 'approve',
                             'bg-blue-100 dark:bg-blue-900/30': actionType === 'complete',
                             'bg-red-100 dark:bg-red-900/30': actionType === 'cancel'
                         }"
                    >
                        <svg
                            class="w-6 h-6"
                            :class="{
                                'text-green-600 dark:text-green-400': actionType === 'approve',
                                'text-blue-600 dark:text-blue-400': actionType === 'complete',
                                'text-red-600 dark:text-red-400': actionType === 'cancel'
                            }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                v-if="actionType === 'approve'"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                            <path
                                v-else-if="actionType === 'complete'"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                            <path
                                v-else
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white text-center mb-2">
                        {{ actionType === 'approve' ? 'Approve Investment' : actionType === 'complete' ? 'Complete Investment' : 'Cancel Investment' }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-center text-sm mb-4">
                        {{ actionType === 'approve' ? 'Are you sure you want to approve this investment?' :
                        actionType === 'complete' ? 'Are you sure you want to mark this investment as completed?' :
                            'Are you sure you want to cancel this investment? This action cannot be undone.' }}
                    </p>

                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4 mb-4">
                        <div class="text-sm space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Investment ID:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ investment.investment_id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Amount:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ currencySymbol }}{{ formatNumber(investment.amount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">User:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ investment.user?.name }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="actionType === 'cancel'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Cancellation Reason <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="cancelReason"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:focus:border-red-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                            placeholder="Enter reason for cancellation..."
                        ></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row-reverse sm:space-x-reverse sm:space-x-3 space-y-3 sm:space-y-0">
                        <button
                            :disabled="isProcessing"
                            class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :class="{
                                'bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white focus:ring-green-500': actionType === 'approve',
                                'bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white focus:ring-blue-500': actionType === 'complete',
                                'bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white focus:ring-red-500': actionType === 'cancel'
                            }"
                            @click="confirmAction"
                        >
                            <svg
                                v-if="isProcessing"
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                />
                            </svg>
                            {{ isProcessing ? 'Processing...' : actionType === 'approve' ? 'Approve Investment' : actionType === 'complete' ? 'Complete Investment' : 'Cancel Investment' }}
                        </button>
                        <button
                            class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-white hover:bg-gray-50 dark:bg-slate-600 dark:hover:bg-slate-500 text-gray-700 dark:text-gray-300 font-medium rounded-lg border border-gray-300 dark:border-slate-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            @click="closeActionModal"
                        >
                            Cancel
                        </button>
                    </div>
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

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}
</style>

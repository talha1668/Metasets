<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import { useSettings } from "@/composables/useSettings.js";
import { useToast } from "@/composables/useToast.js";

const props = defineProps({
    stake: {
        type: Object,
        required: true,
        validator: (value) => value && typeof value === 'object'
    }
});

const { currencySymbol } = useSettings();
const { showToast } = useToast();
const isProcessing = ref(false);
const showActionModal = ref(false);
const actionType = ref('');
const cancelReason = ref('');
const rewardAmount = ref('');
const rewardNote = ref('');

const formatNumber = (value) => {
    if (value === null || value === undefined) return '0.00';
    const num = parseFloat(value) || 0;

    if (num < 0.01 && num > 0) {
        return num.toString();
    }

    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8
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

const formatStatus = (status) => {
    switch (status) {
        case 'active': return 'Active';
        case 'completed': return 'Completed';
        case 'cancelled': return 'Cancelled';
        default: return status || 'Unknown';
    }
};

const getStatusClass = (status) => {
    const classes = {
        'active': 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700',
        'completed': 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
        'cancelled': 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700'
    };
    return classes[status] || 'bg-gray-400 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-700';
};

const isLocked = computed(() => {
    if (!props.stake.unlock_at) return false;
    return new Date(props.stake.unlock_at) > new Date();
});

const daysUntilUnlock = computed(() => {
    if (!props.stake.unlock_at) return 0;
    const unlockDate = new Date(props.stake.unlock_at);
    const now = new Date();
    const diffTime = unlockDate - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 0;
});

const profitPercentage = computed(() => {
    if (!props.stake.stake_amount || props.stake.stake_amount === 0) return 0;
    return ((props.stake.total_rewards / props.stake.stake_amount) * 100).toFixed(2);
});

const openActionModal = (type) => {
    actionType.value = type;
    cancelReason.value = '';
    rewardAmount.value = '';
    rewardNote.value = '';
    showActionModal.value = true;
};

const closeActionModal = () => {
    showActionModal.value = false;
    actionType.value = '';
    cancelReason.value = '';
    rewardAmount.value = '';
    rewardNote.value = '';
};

const confirmAction = () => {
    if (isProcessing.value) return;

    if (actionType.value === 'cancel' && !cancelReason.value.trim()) {
        showToast('Please provide a reason for cancellation', 'error');
        return;
    }

    if (actionType.value === 'addRewards') {
        if (!rewardAmount.value || parseFloat(rewardAmount.value) <= 0) {
            showToast('Please enter a valid reward amount', 'error');
            return;
        }
    }

    isProcessing.value = true;

    const endpoint = `/admin/stakes/${props.stake.id}/${actionType.value === 'addRewards' ? 'add-rewards' : actionType.value}`;
    const data = actionType.value === 'cancel'
        ? { reason: cancelReason.value }
        : actionType.value === 'addRewards'
            ? { reward_amount: rewardAmount.value, note: rewardNote.value }
            : {};

    router.post(endpoint, data, {
        preserveState: false,
        onSuccess: () => {
            closeActionModal();
            const actionText = actionType.value === 'addRewards' ? 'Rewards added' : `Stake ${actionType.value}d`;
            showToast(`${actionText} successfully`, 'success');
        },
        onError: (errors) => {
            const actionText = actionType.value === 'addRewards' ? 'add rewards to' : actionType.value;
            showToast(`Failed to ${actionText} stake`, 'error');
        },
        onFinish: () => isProcessing.value = false
    });
};

const goBack = () => {
    router.get('/admin/stakes');
};
</script>

<template>
    <AdminLayout
        title="Stake Details"
        page-section="Staking"
    >
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-3">
                    <button
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors duration-200"
                        @click="goBack"
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
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        Back
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Stake Details
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Stake ID: {{ stake.stake_id }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                    <span
                        class="px-3 py-1.5 inline-flex text-sm leading-5 font-semibold rounded-full items-center border"
                        :class="getStatusClass(stake.status)"
                    >
                        {{ formatStatus(stake.status) }}
                    </span>

                    <div v-if="stake.status === 'active'" class="flex space-x-2">
                        <button
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                            @click="openActionModal('addRewards')"
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
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                            Add Rewards
                        </button>

                        <button
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                            @click="openActionModal('complete')"
                        >
                            Complete
                        </button>

                        <button
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                            @click="openActionModal('cancel')"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Stake Amount
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatNumber(stake.stake_amount) }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg
                            class="h-6 w-6 text-blue-600 dark:text-blue-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Current Balance
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ currencySymbol }}{{ formatNumber(stake.current_balance) }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg
                            class="h-6 w-6 text-purple-600 dark:text-purple-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Rewards
                        </p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                            {{ currencySymbol }}{{ formatNumber(stake.total_rewards) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            +{{ profitPercentage }}% profit
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg
                            class="h-6 w-6 text-green-600 dark:text-green-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            APY Rate
                        </p>
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                            {{ formatNumber(stake.apy_rate) }}%
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Stake Information -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Stake Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Stake ID</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ stake.stake_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1">
                                    <span
                                        class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border"
                                        :class="getStatusClass(stake.status)"
                                    >
                                        {{ formatStatus(stake.status) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Staked Amount</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ currencySymbol }}{{ formatNumber(stake.stake_amount) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Balance</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ currencySymbol }}{{ formatNumber(stake.current_balance) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Rewards Earned</dt>
                                <dd class="mt-1 text-sm font-semibold text-green-600 dark:text-green-400">
                                    {{ currencySymbol }}{{ formatNumber(stake.total_rewards) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">APY Rate</dt>
                                <dd class="mt-1 text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ formatNumber(stake.apy_rate) }}%
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Auto Compound</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    <span v-if="stake.auto_compound" class="text-green-600 dark:text-green-400 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Enabled
                                    </span>
                                    <span v-else class="text-gray-500 dark:text-gray-400">
                                        Disabled
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Lock Status</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    <span v-if="isLocked" class="text-orange-600 dark:text-orange-400 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Locked ({{ daysUntilUnlock }} days)
                                    </span>
                                    <span v-else class="text-green-600 dark:text-green-400 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z"/>
                                        </svg>
                                        Unlocked
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Timeline
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Staked At</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ formatDate(stake.staked_at) }}
                                </dd>
                            </div>
                            <div v-if="stake.unlock_at">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Unlock At</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ formatDate(stake.unlock_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ formatDate(stake.created_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ formatDate(stake.updated_at) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- User Information -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            User Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="h-16 w-16 flex-shrink-0">
                                <div
                                    v-if="stake.user?.avatar"
                                    class="h-full w-full rounded-full overflow-hidden"
                                >
                                    <img
                                        :src="stake.user.avatar"
                                        :alt="stake.user.name"
                                        class="h-full w-full object-cover"
                                    >
                                </div>
                                <div
                                    v-else
                                    class="h-full w-full rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl"
                                >
                                    {{ stake.user?.name?.charAt(0).toUpperCase() || '?' }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                    {{ stake.user?.name || 'N/A' }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                    {{ stake.user?.email || 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <dl class="space-y-3">
                            <div v-if="stake.user?.uid">
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">User ID</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ stake.user.uid }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Pool Information -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Pool Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Pool Name</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ stake.pool?.name || 'N/A' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Token Symbol</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ stake.pool?.token_symbol || 'N/A' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">APY Rate</dt>
                                <dd class="mt-1 text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ formatNumber(stake.pool?.apy_rate) }}%
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Lock Duration</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ stake.pool?.lock_days || 0 }} days
                                </dd>
                            </div>
                            <div v-if="stake.pool?.description">
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ stake.pool.description }}
                                </dd>
                            </div>
                        </dl>
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
                             'bg-green-100 dark:bg-green-900/30': actionType === 'addRewards',
                             'bg-blue-100 dark:bg-blue-900/30': actionType === 'complete',
                             'bg-red-100 dark:bg-red-900/30': actionType === 'cancel'
                         }"
                    >
                        <svg
                            class="w-6 h-6"
                            :class="{
                                'text-green-600 dark:text-green-400': actionType === 'addRewards',
                                'text-blue-600 dark:text-blue-400': actionType === 'complete',
                                'text-red-600 dark:text-red-400': actionType === 'cancel'
                            }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                v-if="actionType === 'addRewards'"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
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
                        {{ actionType === 'addRewards' ? 'Add Rewards' : actionType === 'complete' ? 'Complete Stake' : 'Cancel Stake' }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-center text-sm mb-4">
                        {{ actionType === 'addRewards' ? 'Add manual rewards to this active stake' :
                        actionType === 'complete' ? 'Are you sure you want to mark this stake as completed?' :
                            'Are you sure you want to cancel this stake? This action cannot be undone.' }}
                    </p>

                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4 mb-4">
                        <div class="text-sm space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Stake ID:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ stake.stake_id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Stake Amount:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ currencySymbol }}{{ formatNumber(stake.stake_amount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Current Balance:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ currencySymbol }}{{ formatNumber(stake.current_balance) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Total Rewards:</span>
                                <span class="font-medium text-green-600 dark:text-green-400">{{ currencySymbol }}{{ formatNumber(stake.total_rewards) }}</span>
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

                    <div v-if="actionType === 'addRewards'" class="mb-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Reward Amount <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                    {{ currencySymbol }}
                                </span>
                                <input
                                    v-model="rewardAmount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="0.00"
                                >
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Note (Optional)
                            </label>
                            <textarea
                                v-model="rewardNote"
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                placeholder="Add a note about this reward..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row-reverse sm:space-x-reverse sm:space-x-3 space-y-3 sm:space-y-0">
                        <button
                            :disabled="isProcessing"
                            class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :class="{
                                'bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white focus:ring-green-500': actionType === 'addRewards',
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
                            {{ isProcessing ? 'Processing...' : actionType === 'addRewards' ? 'Add Rewards' : actionType === 'complete' ? 'Complete Stake' : 'Cancel Stake' }}
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

@media (max-width: 1024px) {
    .lg\:col-span-2 {
        grid-column: span 1;
    }
}
</style>

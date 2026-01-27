<script setup>
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";

const props = defineProps({
    stats: {
        type: Object,
        required: true
    },
    recent_activity: {
        type: Object,
        required: true
    },
    chart_data: {
        type: Object,
        required: true
    }
});

const page = usePage();
const currencySymbol = computed(() => page.props.currencySymbol || '$');

const formatNumber = (value) => {
    if (value === null || value === undefined) return '0';
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('en-US').format(num);
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return `${currencySymbol.value}0.00`;
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(num).replace('$', currencySymbol.value);
};

const formatMoney = (value) => {
    if (!value || isNaN(value)) return '0.00';
    const num = Math.abs(parseFloat(value));

    if (num >= 1e9) return (num / 1e9).toFixed(2) + 'B';
    if (num >= 1e6) return (num / 1e6).toFixed(2) + 'M';
    if (num >= 1e3) return (num / 1e3).toFixed(2) + 'K';
    return num.toFixed(2);
};

const getStatusColor = (status) => {
    const colors = {
        'active': 'text-green-600 bg-green-100 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700',
        'pending': 'text-yellow-600 bg-yellow-100 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-700',
        'approved': 'text-green-600 bg-green-100 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700',
        'rejected': 'text-red-600 bg-red-100 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700',
        'completed': 'text-green-600 bg-green-100 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700',
        'suspended': 'text-orange-600 bg-orange-100 border-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:border-orange-700',
        'banned': 'text-red-600 bg-red-100 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700',
        'cancelled': 'text-gray-600 bg-gray-100 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-700',
        'reviewing': 'text-blue-600 bg-blue-100 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
    };
    return colors[status] || 'text-gray-600 bg-gray-100 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-700';
};

const getStatusDotClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-500',
        'approved': 'bg-green-500',
        'rejected': 'bg-red-500',
        'active': 'bg-green-500',
        'completed': 'bg-green-500',
        'suspended': 'bg-orange-500',
        'banned': 'bg-red-500',
        'cancelled': 'bg-gray-500',
        'reviewing': 'bg-blue-500'
    };
    return classes[status] || 'bg-gray-500';
};

const getStatusText = (status) => {
    const statusMap = {
        'pending': 'Pending',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'completed': 'Completed',
        'active': 'Active',
        'suspended': 'Suspended',
        'banned': 'Banned',
        'cancelled': 'Cancelled',
        'reviewing': 'Reviewing'
    };
    return statusMap[status] || status;
};

const getInitials = (name) => {
    if (!name || typeof name !== 'string') return '?';
    return name.split(' ').map(n => n.charAt(0)).join('').toUpperCase().slice(0, 2);
};

const getRelativeTime = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)}d ago`;
        return `${Math.floor(diffInSeconds / 2592000)}mo ago`;
    } catch (error) {
        return '';
    }
};

const statCards = computed(() => [
    {
        title: 'Total Users',
        value: formatNumber(props.stats.users?.total),
        change: `+${props.stats.users?.new_today || 0} today`,
        color: 'blue',
        link: '/admin/users',
        icon: 'users'
    },
    {
        title: 'Total Balance',
        value: formatCurrency(props.stats.financial?.total_balance),
        change: formatCurrency(props.stats.financial?.deposits_today) + ' today',
        color: 'green',
        link: '/admin/wallets',
        icon: 'wallet'
    },
    {
        title: 'Active Investments',
        value: formatNumber(props.stats.investment?.active_investments),
        change: formatCurrency(props.stats.investment?.active_invested),
        color: 'purple',
        link: '/admin/investment-logs',
        icon: 'trending'
    },
    {
        title: 'Pending Requests',
        value: formatNumber((props.stats.financial?.pending_deposits_count || 0) + (props.stats.financial?.pending_withdrawals_count || 0)),
        change: `${props.stats.financial?.pending_deposits_count || 0} deposits, ${props.stats.financial?.pending_withdrawals_count || 0} withdrawals`,
        color: 'yellow',
        link: '/admin/deposits',
        icon: 'clock'
    },
]);
</script>

<template>
    <AdminLayout title="Dashboard" page-section="Overview">
        <div class="space-y-6">
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Dashboard Overview
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Monitor your platform's performance and key metrics
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Link
                    v-for="(card, index) in statCards"
                    :key="index"
                    :href="card.link"
                    class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow duration-200"
                >
                    <div class="flex items-center">
                        <div class="p-2 rounded-lg" :class="`bg-${card.color}-100 dark:bg-${card.color}-900/30`">
                            <svg
                                v-if="card.icon === 'users'"
                                class="h-6 w-6"
                                :class="`text-${card.color}-600 dark:text-${card.color}-400`"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            <svg
                                v-else-if="card.icon === 'wallet'"
                                class="h-6 w-6"
                                :class="`text-${card.color}-600 dark:text-${card.color}-400`"
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
                            <svg
                                v-else-if="card.icon === 'trending'"
                                class="h-6 w-6"
                                :class="`text-${card.color}-600 dark:text-${card.color}-400`"
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
                            <svg
                                v-else-if="card.icon === 'clock'"
                                class="h-6 w-6"
                                :class="`text-${card.color}-600 dark:text-${card.color}-400`"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ card.title }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ card.value }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ card.change }}
                    </div>
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        User Statistics
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Users</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(stats.users?.total) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active Users</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ formatNumber(stats.users?.active) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Suspended</span>
                            <span class="font-semibold text-orange-600 dark:text-orange-400">{{ formatNumber(stats.users?.suspended) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Verified</span>
                            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ formatNumber(stats.users?.verified) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Pending KYC</span>
                            <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ formatNumber(stats.users?.pending_kyc) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">New This Week</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(stats.users?.new_week) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Financial Overview
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Deposits</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ currencySymbol }}{{ formatMoney(stats.financial?.total_deposits) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Withdrawals</span>
                            <span class="font-semibold text-red-600 dark:text-red-400">{{ currencySymbol }}{{ formatMoney(stats.financial?.total_withdrawals) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Pending Deposits</span>
                            <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ currencySymbol }}{{ formatMoney(stats.financial?.pending_deposits) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Pending Withdrawals</span>
                            <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ currencySymbol }}{{ formatMoney(stats.financial?.pending_withdrawals) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Earnings</span>
                            <span class="font-semibold text-purple-600 dark:text-purple-400">{{ currencySymbol }}{{ formatMoney(stats.financial?.total_earnings) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active Wallets</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(stats.financial?.active_wallets) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Investment Overview
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Investments</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(stats.investment?.total_investments) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ formatNumber(stats.investment?.active_investments) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Completed</span>
                            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ formatNumber(stats.investment?.completed_investments) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Invested</span>
                            <span class="font-semibold text-purple-600 dark:text-purple-400">{{ currencySymbol }}{{ formatMoney(stats.investment?.total_invested) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Earned</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ currencySymbol }}{{ formatMoney(stats.investment?.total_earned) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active Plans</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(stats.investment?.active_plans) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Staking Overview
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Stakes</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(stats.staking?.total_stakes) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active Stakes</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ formatNumber(stats.staking?.active_stakes) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Staked</span>
                            <span class="font-semibold text-purple-600 dark:text-purple-400">{{ currencySymbol }}{{ formatMoney(stats.staking?.total_staked) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Rewards Paid</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ currencySymbol }}{{ formatMoney(stats.staking?.total_rewards_paid) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active Pools</span>
                            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ formatNumber(stats.staking?.active_pools) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Average APY</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ Number(stats.staking?.average_apy || 0).toFixed(2) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Referral Program Statistics
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Referrals</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats.referral?.total_referrals) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Active Referrals</div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatNumber(stats.referral?.total_active_referrals) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Commissions</div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ currencySymbol }}{{ formatMoney(stats.referral?.total_commission_amount) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Paid Commissions</div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ currencySymbol }}{{ formatMoney(stats.referral?.paid_commission_amount) }}</div>
                    </div>
                </div>
            </div>

            <div
                v-if="recent_activity.users?.length > 0 || recent_activity.deposits?.length > 0 || recent_activity.withdrawals?.length > 0 || recent_activity.investments?.length > 0"
                class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700"
            >
                <div class="border-b border-gray-200 dark:border-slate-700 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Recent Activity
                    </h3>
                </div>

                <div class="p-6 space-y-6">
                    <div v-if="recent_activity.users && recent_activity.users.length > 0">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white">Recent Users</h4>
                            <Link
                                href="/admin/users"
                                class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                View All
                            </Link>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="user in recent_activity.users"
                                :key="user.id"
                                class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition-colors"
                            >
                                <div class="flex items-center space-x-3 flex-1 min-w-0">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <div v-if="user.avatar" class="w-full h-full rounded-full overflow-hidden">
                                            <img :src="user.avatar" :alt="user.name" class="w-full h-full object-cover">
                                        </div>
                                        <div v-else class="w-full h-full rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-sm shadow-md">
                                            {{ getInitials(user.name) }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white truncate text-sm">{{ user.name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</div>
                                    </div>
                                </div>
                                <div class="text-right ml-4 flex-shrink-0">
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs rounded-full border font-medium"
                                        :class="getStatusColor(user.status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1" :class="getStatusDotClass(user.status)" />
                                        {{ getStatusText(user.status) }}
                                    </span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ getRelativeTime(user.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="recent_activity.deposits && recent_activity.deposits.length > 0">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white">Recent Deposits</h4>
                            <Link
                                href="/admin/deposits"
                                class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                View All
                            </Link>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="deposit in recent_activity.deposits"
                                :key="deposit.id"
                                class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition-colors"
                            >
                                <div class="flex items-center space-x-3 flex-1 min-w-0">
                                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white truncate text-sm">{{ deposit.user?.name || 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">TRX: {{ deposit.trx }}</div>
                                    </div>
                                </div>
                                <div class="text-right ml-4 flex-shrink-0">
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ formatCurrency(deposit.amount) }}</div>
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs rounded-full border font-medium mt-1"
                                        :class="getStatusColor(deposit.status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1" :class="getStatusDotClass(deposit.status)" />
                                        {{ getStatusText(deposit.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="recent_activity.withdrawals && recent_activity.withdrawals.length > 0">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white">Recent Withdrawals</h4>
                            <Link
                                href="/admin/withdrawals"
                                class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                View All
                            </Link>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="withdrawal in recent_activity.withdrawals"
                                :key="withdrawal.id"
                                class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition-colors"
                            >
                                <div class="flex items-center space-x-3 flex-1 min-w-0">
                                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white truncate text-sm">{{ withdrawal.user?.name || 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">TRX: {{ withdrawal.trx }}</div>
                                    </div>
                                </div>
                                <div class="text-right ml-4 flex-shrink-0">
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ formatCurrency(withdrawal.amount) }}</div>
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs rounded-full border font-medium mt-1"
                                        :class="getStatusColor(withdrawal.status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1" :class="getStatusDotClass(withdrawal.status)" />
                                        {{ getStatusText(withdrawal.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="recent_activity.investments && recent_activity.investments.length > 0">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white">Recent Investments</h4>
                            <Link
                                href="/admin/investments"
                                class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                View All
                            </Link>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="investment in recent_activity.investments"
                                :key="investment.id"
                                class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition-colors"
                            >
                                <div class="flex items-center space-x-3 flex-1 min-w-0">
                                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white truncate text-sm">{{ investment.user?.name || 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ investment.plan?.name || 'N/A' }} • ID: {{ investment.investment_id }}</div>
                                    </div>
                                </div>
                                <div class="text-right ml-4 flex-shrink-0">
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ formatCurrency(investment.amount) }}</div>
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs rounded-full border font-medium mt-1"
                                        :class="getStatusColor(investment.status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1" :class="getStatusDotClass(investment.status)" />
                                        {{ getStatusText(investment.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@media (max-width: 768px) {
    .grid-cols-1.md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .grid-cols-1.md\:grid-cols-4 {
        grid-template-columns: 1fr;
    }
}
</style>

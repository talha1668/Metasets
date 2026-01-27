<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import { useSettings } from "@/composables/useSettings.js";
import { useToast } from "@/composables/useToast.js";
import NetworkLevel from "@/Components/Admin/NetworkLevel.vue";

const props = defineProps({
    user: {
        type: Object,
        default: null
    },
    network: {
        type: Object,
        default: null
    },
    top_referrers: {
        type: Array,
        default: () => []
    },
    max_levels: {
        type: Number,
        default: 5
    }
});

const { currencySymbol } = useSettings();
const { success, error } = useToast();
const pageProps = usePage();

const isLoading = ref(false);
const searchUserId = ref('');
const selectedMaxLevels = ref(props.max_levels);
const searchResults = ref([]);
const showSearchResults = ref(false);

const formatNumber = (value) => {
    if (!value || isNaN(value)) return '0.00';
    return parseFloat(value).toFixed(2);
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
};

const searchUsers = async () => {
    if (!searchUserId.value.trim() || searchUserId.value.length < 2) {
        searchResults.value = [];
        showSearchResults.value = false;
        return;
    }

    try {
        // You'll need to implement a user search endpoint
        const response = await fetch(`/admin/users/search?q=${encodeURIComponent(searchUserId.value)}`);
        const data = await response.json();
        searchResults.value = data.users || [];
        showSearchResults.value = true;
    } catch (err) {
        console.error('Search error:', err);
        searchResults.value = [];
        showSearchResults.value = false;
    }
};

const selectUser = (user) => {
    searchUserId.value = user.username;
    searchResults.value = [];
    showSearchResults.value = false;
    loadUserNetwork(user.id);
};

const loadUserNetwork = (userId) => {
    if (isLoading.value) return;

    isLoading.value = true;

    router.get('/admin/referrals/network', {
        user_id: userId,
        max_levels: selectedMaxLevels.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
            error('Failed to load referral network');
        }
    });
};

const displayLevel = computed(() => {
    return props.level > 0 ? props.level : 'Root';
});

const clearNetwork = () => {
    searchUserId.value = '';
    selectedMaxLevels.value = 5;

    router.get('/admin/referrals/network', {
        max_levels: 5
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const updateMaxLevels = () => {
    if (props.user) {
        loadUserNetwork(props.user.id);
    }
};

const getNetworkStats = (networkData) => {
    if (!networkData) return { totalUsers: 0, totalLevels: 0 };

    let totalUsers = 0;
    let maxLevel = 0;

    const countUsers = (node, level = 1) => {
        totalUsers++;
        maxLevel = Math.max(maxLevel, level);

        if (node.referrals && node.referrals.length > 0) {
            node.referrals.forEach(referral => {
                countUsers(referral, level + 1);
            });
        }
    };

    countUsers(networkData);

    return { totalUsers: totalUsers - 1, totalLevels: maxLevel - 1 }; // Subtract 1 to exclude root user
};

const networkStats = computed(() => {
    return getNetworkStats(props.network);
});

const getLevelColor = (level) => {
    const colors = {
        1: 'bg-blue-100 border-blue-200 text-blue-800 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-300',
        2: 'bg-green-100 border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300',
        3: 'bg-yellow-100 border-yellow-200 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-300',
        4: 'bg-purple-100 border-purple-200 text-purple-800 dark:bg-purple-900/30 dark:border-purple-700 dark:text-purple-300',
        5: 'bg-pink-100 border-pink-200 text-pink-800 dark:bg-pink-900/30 dark:border-pink-700 dark:text-pink-300',
    };
    return colors[level] || 'bg-gray-100 border-gray-200 text-gray-800 dark:bg-gray-900/30 dark:border-gray-700 dark:text-gray-300';
};

const getAvatarColor = (level) => {
    const colors = {
        1: 'bg-blue-500',
        2: 'bg-green-500',
        3: 'bg-yellow-500',
        4: 'bg-purple-500',
        5: 'bg-pink-500',
    };
    return colors[level] || 'bg-gray-500';
};

const handleUserClick = (user) => {
    loadUserNetwork(user.id);
};

onMounted(() => {
    const flash = pageProps.props.flash;
    if (flash?.success) success(flash.success);
    if (flash?.error) error(flash.error);
});
</script>

<template>
    <AdminLayout
        title="Referral Network"
        page-section="Referrals"
    >
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-4 mb-4">
                        <Link
                            href="/admin/referrals"
                            class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to Referrals
                        </Link>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Referral Network Tree
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Visualize and explore referral relationships and network structures
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Search User
                        </label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="searchUserId"
                                type="text"
                                placeholder="Search by username, email, or name..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                @input="searchUsers"
                                @focus="showSearchResults = searchResults.length > 0"
                            >

                            <div
                                v-if="showSearchResults && searchResults.length > 0"
                                class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                            >
                                <div
                                    v-for="user in searchResults"
                                    :key="user.id"
                                    class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-600 cursor-pointer border-b border-gray-100 dark:border-slate-600 last:border-b-0"
                                    @click="selectUser(user)"
                                >
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                                <span class="text-xs font-medium text-purple-600 dark:text-purple-400">
                                                    {{ user.username?.charAt(0).toUpperCase() || 'U' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ user.username }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ user.email }}
                                            </div>
                                        </div>
                                        <div class="ml-auto text-xs text-gray-500 dark:text-gray-400">
                                            {{ user.referrals_count || 0 }} referrals
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Max Levels -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Max Levels
                        </label>
                        <select
                            v-model="selectedMaxLevels"
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            @change="updateMaxLevels"
                        >
                            <option v-for="level in [1,2,3,4,5,6,7,8,9,10]" :key="level" :value="level">
                                {{ level }} Level{{ level > 1 ? 's' : '' }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-4">
                    <button
                        v-if="user"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors duration-200"
                        @click="clearNetwork"
                    >
                        Clear Network
                    </button>
                </div>
            </div>
        </div>

        <!-- Network Stats (when user is selected) -->
        <div v-if="user && network" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Root User</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ user.username }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Network</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ networkStats.totalUsers }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Network Depth</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ networkStats.totalLevels }} Level{{ networkStats.totalLevels !== 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Tree Visualization -->
        <div v-if="network" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Network Tree</h3>

                <!-- Root User -->
                <div class="flex justify-center mb-8">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full border-4 border-purple-200 dark:border-purple-700">
                            <span class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                {{ network.user.username?.charAt(0).toUpperCase() || 'U' }}
                            </span>
                        </div>
                        <div class="mt-2">
                            <div class="font-medium text-gray-900 dark:text-white">{{ network.user.username }}</div>
                            <div v-if="network.level > 0" class="text-sm text-gray-500 dark:text-gray-400">
                                Level {{ network.level }}
                            </div>
                            <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                                Root User
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ currencySymbol }}{{ formatNumber(network.user.total_invested || 0) }} invested
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Referral Levels -->
                <div v-if="network.referrals && network.referrals.length > 0" class="space-y-8">
                    <NetworkLevel
                        v-for="(levelData, index) in network.referrals"
                        :key="`level-${index}`"
                        :level-data="levelData"
                        :level-number="levelData.level"
                        :currency-symbol="currencySymbol"
                        :format-number="formatNumber"
                        @user-click="handleUserClick"
                    />
                </div>

                <!-- No referrals message -->
                <div v-else class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        No referrals found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        This user hasn't referred anyone yet.
                    </p>
                </div>
            </div>
        </div>

        <!-- Top Referrers (when no specific user is selected) -->
        <div v-else-if="top_referrers && top_referrers.length > 0" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Top Referrers</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div
                        v-for="referrer in top_referrers"
                        :key="referrer.id"
                        class="p-4 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer transition-colors duration-200"
                        @click="loadUserNetwork(referrer.id)"
                    >
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                    <span class="text-sm font-medium text-purple-600 dark:text-purple-400">
                                        {{ referrer.username?.charAt(0).toUpperCase() || 'U' }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ referrer.username }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ referrer.referrals_count }} referral{{ referrer.referrals_count !== 1 ? 's' : '' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ currencySymbol }}{{ formatNumber(referrer.total_referral_bonus || 0) }} earned
                                </div>
                            </div>
                            <div class="ml-2">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    Search for a user to view their network
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Enter a username, email, or name in the search box above to explore their referral network.
                </p>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div
            v-if="isLoading"
            class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
        >
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 text-center">
                <svg class="animate-spin mx-auto h-8 w-8 text-purple-600 mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                <p class="text-gray-600 dark:text-gray-400">Loading network...</p>
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

.grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid-cols-3,
    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .grid-cols-2,
    .grid-cols-3,
    .grid-cols-4 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
}
</style>

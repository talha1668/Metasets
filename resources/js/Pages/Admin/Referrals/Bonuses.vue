<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import AdminPagination from "@/Components/AdminPagination.vue";
import { useSettings } from "@/composables/useSettings.js";
import { useToast } from "@/composables/useToast.js";

const props = defineProps({
    bonuses: {
        type: Array,
        required: true,
        default: () => []
    },
    meta: {
        type: Object,
        default: () => ({
            total: 0,
            current_page: 1,
            per_page: 20,
            last_page: 1
        })
    },
    filters: {
        type: Object,
        default: () => ({
            sort_field: 'created_at',
            sort_direction: 'desc'
        })
    },
    type_options: {
        type: Array,
        default: () => [
            { value: 'milestone', label: 'Milestone' },
            { value: 'achievement', label: 'Achievement' },
            { value: 'loyalty', label: 'Loyalty' }
        ]
    },
    reward_type_options: {
        type: Array,
        default: () => [
            { value: 'fixed_amount', label: 'Fixed Amount' },
            { value: 'percentage', label: 'Percentage' },
            { value: 'upgrade', label: 'Account Upgrade' }
        ]
    }
});

const { currencySymbol } = useSettings();
const { success, error } = useToast();
const pageProps = usePage();

const isLoading = ref(false);
const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedBonus = ref(null);
const processing = ref(false);
const searchTerm = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || '');
const selectedStatus = ref(props.filters?.status || '');

const form = ref({
    name: '',
    description: '',
    type: 'milestone',
    min_active_referrals: '',
    min_team_investment: '',
    time_frame_days: '',
    reward_type: 'fixed_amount',
    reward_amount: '',
    reward_details: '',
    is_active: true,
    is_recurring: false,
    max_claims_per_user: ''
});

const resetForm = () => {
    form.value = {
        name: '',
        description: '',
        type: 'milestone',
        min_active_referrals: '',
        min_team_investment: '',
        time_frame_days: '',
        reward_type: 'fixed_amount',
        reward_amount: '',
        reward_details: '',
        is_active: true,
        is_recurring: false,
        max_claims_per_user: ''
    };
};

const sortField = computed(() => props.filters.sort_field || 'created_at');
const sortDirection = computed(() => props.filters.sort_direction || 'desc');

const hasActiveFilters = computed(() => {
    return !!(searchTerm.value || selectedType.value || selectedStatus.value);
});

const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

const formatNumber = (value) => {
    if (!value || isNaN(value)) return '0.00';
    return parseFloat(value).toFixed(2);
};

const getTypeLabel = (type) => {
    const option = props.type_options.find(opt => opt.value === type);
    return option ? option.label : type;
};

const getRewardTypeLabel = (rewardType) => {
    const option = props.reward_type_options.find(opt => opt.value === rewardType);
    return option ? option.label : rewardType;
};

const getTypeClass = (type) => {
    const classes = {
        'milestone': 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
        'achievement': 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700',
        'loyalty': 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700'
    };
    return classes[type] || 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-700';
};

const getRewardTypeClass = (rewardType) => {
    const classes = {
        'fixed_amount': 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700',
        'percentage': 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:border-orange-700',
        'upgrade': 'bg-violet-100 text-violet-800 border-violet-200 dark:bg-violet-900/30 dark:text-violet-300 dark:border-violet-700'
    };
    return classes[rewardType] || 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-700';
};

const applyFilters = () => {
    if (isLoading.value) return;

    isLoading.value = true;

    const params = {
        page: 1,
        sort_field: sortField.value,
        sort_direction: sortDirection.value
    };

    if (searchTerm.value?.trim()) params.search = searchTerm.value.trim();
    if (selectedType.value) params.type = selectedType.value;
    if (selectedStatus.value) params.status = selectedStatus.value;

    router.get('/admin/referrals/bonuses', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
            error('Failed to apply filters');
        }
    });
};

const clearSearch = () => {
    searchTerm.value = '';
    applyFilters();
};

const clearTypeFilter = () => {
    selectedType.value = '';
    applyFilters();
};

const clearStatusFilter = () => {
    selectedStatus.value = '';
    applyFilters();
};

const clearAllFilters = () => {
    searchTerm.value = '';
    selectedType.value = '';
    selectedStatus.value = '';

    isLoading.value = true;

    router.get('/admin/referrals/bonuses', {
        sort_field: 'created_at',
        sort_direction: 'desc'
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
            error('Failed to clear filters');
        }
    });
};

const handleSort = (field) => {
    if (isLoading.value) return;

    const currentSortField = props.filters.sort_field;
    const currentSortDirection = props.filters.sort_direction;

    let newDirection = 'asc';
    if (currentSortField === field && currentSortDirection === 'asc') {
        newDirection = 'desc';
    }

    isLoading.value = true;

    const params = {
        sort_field: field,
        sort_direction: newDirection,
        page: 1
    };

    if (searchTerm.value?.trim()) params.search = searchTerm.value.trim();
    if (selectedType.value) params.type = selectedType.value;
    if (selectedStatus.value) params.status = selectedStatus.value;

    router.get('/admin/referrals/bonuses', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
            error('Failed to sort bonuses');
        }
    });
};

const changePage = (page) => {
    if (isLoading.value) return;

    isLoading.value = true;

    const params = { page };
    if (searchTerm.value?.trim()) params.search = searchTerm.value.trim();
    if (selectedType.value) params.type = selectedType.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (sortField.value) params.sort_field = sortField.value;
    if (sortDirection.value) params.sort_direction = sortDirection.value;

    router.get('/admin/referrals/bonuses', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
            error('Failed to load page');
        }
    });
};

const openAddModal = () => {
    resetForm();
    showAddModal.value = true;
};

const openEditModal = (bonus) => {
    selectedBonus.value = bonus;
    form.value = {
        name: bonus.name,
        description: bonus.description,
        type: bonus.type,
        min_active_referrals: bonus.min_active_referrals || '',
        min_team_investment: bonus.min_team_investment || '',
        time_frame_days: bonus.time_frame_days || '',
        reward_type: bonus.reward_type,
        reward_amount: bonus.reward_amount || '',
        reward_details: bonus.reward_details || '',
        is_active: bonus.is_active,
        is_recurring: bonus.is_recurring,
        max_claims_per_user: bonus.max_claims_per_user || ''
    };
    showEditModal.value = true;
};

const openDeleteModal = (bonus) => {
    selectedBonus.value = bonus;
    showDeleteModal.value = true;
};

const closeModals = () => {
    showAddModal.value = false;
    showEditModal.value = false;
    showDeleteModal.value = false;
    selectedBonus.value = null;
    resetForm();
};

const validateForm = () => {
    const errors = [];

    if (!form.value.name?.trim()) {
        errors.push('Bonus name is required');
    }

    if (!form.value.description?.trim()) {
        errors.push('Description is required');
    }

    if (form.value.reward_type === 'fixed_amount' && (!form.value.reward_amount || form.value.reward_amount <= 0)) {
        errors.push('Reward amount is required for fixed amount rewards');
    }

    if (form.value.reward_type === 'percentage' && (!form.value.reward_amount || form.value.reward_amount <= 0 || form.value.reward_amount > 100)) {
        errors.push('Reward percentage must be between 0 and 100');
    }

    return errors;
};

const submitForm = () => {
    const validationErrors = validateForm();
    if (validationErrors.length > 0) {
        error(validationErrors[0]);
        return;
    }

    processing.value = true;

    const data = {
        name: form.value.name.trim(),
        description: form.value.description.trim(),
        type: form.value.type,
        min_active_referrals: parseInt(form.value.min_active_referrals) || 0,
        min_team_investment: parseFloat(form.value.min_team_investment) || 0,
        time_frame_days: parseInt(form.value.time_frame_days) || 0,
        reward_type: form.value.reward_type,
        reward_amount: parseFloat(form.value.reward_amount) || 0,
        reward_details: form.value.reward_details?.trim() || '',
        is_active: form.value.is_active,
        is_recurring: form.value.is_recurring,
        max_claims_per_user: parseInt(form.value.max_claims_per_user) || 1
    };

    const url = showEditModal.value
        ? `/admin/referrals/bonuses/${selectedBonus.value.id}`
        : '/admin/referrals/bonuses';

    const method = showEditModal.value ? 'put' : 'post';

    router[method](url, data, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
            success(showEditModal.value ? 'Bonus updated successfully' : 'Bonus created successfully');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            if (Array.isArray(firstError)) {
                error(firstError[0]);
            } else {
                error(firstError || 'An error occurred');
            }
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

const confirmDelete = () => {
    if (!selectedBonus.value) return;

    processing.value = true;

    router.delete(`/admin/referrals/bonuses/${selectedBonus.value.id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
            success('Bonus deleted successfully');
        },
        onError: () => {
            error('Failed to delete bonus');
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

onMounted(() => {
    const flash = pageProps.props.flash;
    if (flash?.success) success(flash.success);
    if (flash?.error) error(flash.error);
});
</script>

<template>
    <AdminLayout
        title="Referral Bonuses"
        page-section="Referrals"
    >
        <!-- Header -->
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
                        Referral Bonuses & Rewards
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage milestone bonuses, achievements, and loyalty rewards
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button
                        class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                        @click="openAddModal"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Bonus
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 mb-6">
            <div class="p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="searchTerm"
                                type="text"
                                placeholder="Search bonuses by name or description..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                @input="debouncedSearch"
                            >
                            <button
                                v-if="searchTerm"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                @click="clearSearch"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="min-w-[140px]">
                            <select
                                v-model="selectedType"
                                class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                @change="applyFilters"
                            >
                                <option value="">All Types</option>
                                <option
                                    v-for="type in type_options"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <div class="min-w-[140px]">
                            <select
                                v-model="selectedStatus"
                                class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                @change="applyFilters"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button
                            v-if="hasActiveFilters"
                            class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors duration-200"
                            @click="clearAllFilters"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Active Filters -->
                <div
                    v-if="hasActiveFilters"
                    class="mt-4 flex flex-wrap items-center gap-2"
                >
                    <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>

                    <span
                        v-if="searchTerm"
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300"
                    >
                        Search: "{{ searchTerm }}"
                        <button
                            class="ml-2 text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-200"
                            @click="clearSearch"
                        >
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>

                    <span
                        v-if="selectedType"
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                    >
                        Type: {{ getTypeLabel(selectedType) }}
                        <button
                            class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                            @click="clearTypeFilter"
                        >
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>

                    <span
                        v-if="selectedStatus"
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                    >
                        Status: {{ selectedStatus === 'active' ? 'Active' : 'Inactive' }}
                        <button
                            class="ml-2 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200"
                            @click="clearStatusFilter"
                        >
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                </div>
            </div>
        </div>

        <!-- Bonuses Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300"
                            @click="handleSort('name')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>BONUS</span>
                                <svg
                                    v-if="sortField === 'name'"
                                    class="w-4 h-4"
                                    :class="sortDirection === 'asc' ? 'transform rotate-180' : ''"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300"
                            @click="handleSort('type')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>TYPE</span>
                                <svg
                                    v-if="sortField === 'type'"
                                    class="w-4 h-4"
                                    :class="sortDirection === 'asc' ? 'transform rotate-180' : ''"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            REQUIREMENTS
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300"
                            @click="handleSort('reward_amount')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>REWARD</span>
                                <svg
                                    v-if="sortField === 'reward_amount'"
                                    class="w-4 h-4"
                                    :class="sortDirection === 'asc' ? 'transform rotate-180' : ''"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            CLAIMS
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            STATUS
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ACTIONS
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    <tr v-if="!isLoading && bonuses.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    No bonuses found
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    No bonuses match your current filters or none have been created yet.
                                </p>
                                <div class="mt-6">
                                    <button
                                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700"
                                        @click="openAddModal"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Add First Bonus
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="isLoading">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">Loading bonuses...</span>
                            </div>
                        </td>
                    </tr>

                    <tr
                        v-for="bonus in bonuses"
                        :key="bonus.id"
                        class="hover:bg-gray-50 dark:hover:bg-slate-700"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <div
                                        class="h-full w-full rounded-lg flex items-center justify-center text-white font-bold text-sm bg-purple-500"
                                    >
                                        <svg v-if="bonus.type === 'milestone'" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                        <svg v-else-if="bonus.type === 'achievement'" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ bonus.name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                        {{ bonus.description }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border"
                                    :class="getTypeClass(bonus.type)"
                                >
                                    {{ getTypeLabel(bonus.type) }}
                                </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                <div v-if="bonus.min_active_referrals > 0">
                                    {{ bonus.min_active_referrals }} active referrals
                                </div>
                                <div v-if="bonus.min_team_investment > 0">
                                    {{ currencySymbol }}{{ formatNumber(bonus.min_team_investment) }} team investment
                                </div>
                                <div v-if="bonus.time_frame_days > 0">
                                    {{ bonus.time_frame_days }} days timeframe
                                </div>
                                <div v-if="bonus.min_active_referrals === 0 && bonus.min_team_investment === 0 && bonus.time_frame_days === 0" class="text-gray-500 dark:text-gray-400">
                                    No requirements
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                <div class="font-semibold">
                                        <span v-if="bonus.reward_type === 'fixed_amount'">
                                            {{ currencySymbol }}{{ formatNumber(bonus.reward_amount) }}
                                        </span>
                                    <span v-else-if="bonus.reward_type === 'percentage'">
                                            {{ formatNumber(bonus.reward_amount) }}%
                                        </span>
                                    <span v-else>
                                            {{ bonus.reward_details || 'Account Upgrade' }}
                                        </span>
                                </div>
                                <span
                                    class="px-1.5 py-0.5 text-xs rounded-full border"
                                    :class="getRewardTypeClass(bonus.reward_type)"
                                >
                                        {{ getRewardTypeLabel(bonus.reward_type) }}
                                    </span>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                <div class="font-medium">
                                    {{ bonus.achievements_count || 0 }} claims
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="bonus.is_recurring">Recurring</span>
                                    <span v-else>Max {{ bonus.max_claims_per_user || 1 }} per user</span>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="bonus.is_active
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'"
                                >
                                    {{ bonus.is_active ? 'Active' : 'Inactive' }}
                                </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <button
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors duration-200"
                                    @click="openEditModal(bonus)"
                                >
                                    <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>

                                <button
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors duration-200"
                                    @click="openDeleteModal(bonus)"
                                >
                                    <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <AdminPagination
            v-if="meta.total > 0"
            :current-page="meta.current_page"
            :last-page="meta.last_page"
            :total="meta.total"
            :per-page="meta.per_page"
            item-name="bonuses"
            @page-change="changePage"
        />

        <!-- Add/Edit Modal -->
        <div
            v-if="showAddModal || showEditModal"
            class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
            @click="closeModals"
        >
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 transform transition-all duration-300 ease-out max-h-[90vh] overflow-y-auto"
                @click.stop
            >
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        {{ showEditModal ? 'Edit Referral Bonus' : 'Add Referral Bonus' }}
                    </h3>

                    <form @submit.prevent="submitForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="md:col-span-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Basic Information</h4>
                            </div>

                            <!-- Name -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Bonus Name *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="e.g., 10 Referral Milestone"
                                >
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description *
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="Describe what this bonus is for and how users can earn it"
                                />
                            </div>

                            <!-- Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Bonus Type *
                                </label>
                                <select
                                    v-model="form.type"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                >
                                    <option
                                        v-for="option in type_options"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Time Frame -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Time Frame (Days)
                                </label>
                                <input
                                    v-model="form.time_frame_days"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0 = No time limit"
                                >
                            </div>

                            <!-- Requirements -->
                            <div class="md:col-span-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3 mt-4">Requirements</h4>
                            </div>

                            <!-- Min Active Referrals -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Minimum Active Referrals
                                </label>
                                <input
                                    v-model="form.min_active_referrals"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0"
                                >
                            </div>

                            <!-- Min Team Investment -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Minimum Team Investment ({{ currencySymbol }})
                                </label>
                                <input
                                    v-model="form.min_team_investment"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0.00"
                                >
                            </div>

                            <!-- Reward Settings -->
                            <div class="md:col-span-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3 mt-4">Reward Settings</h4>
                            </div>

                            <!-- Reward Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Reward Type *
                                </label>
                                <select
                                    v-model="form.reward_type"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                >
                                    <option
                                        v-for="option in reward_type_options"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Reward Amount -->
                            <div v-if="form.reward_type !== 'upgrade'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Reward Amount *
                                    <span v-if="form.reward_type === 'fixed_amount'">({{ currencySymbol }})</span>
                                    <span v-if="form.reward_type === 'percentage'">(%)</span>
                                </label>
                                <input
                                    v-model="form.reward_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="form.reward_type === 'percentage' ? 100 : undefined"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    :placeholder="form.reward_type === 'percentage' ? '10.00' : '50.00'"
                                >
                            </div>

                            <!-- Reward Details -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Reward Details
                                    <span v-if="form.reward_type === 'upgrade'">*</span>
                                </label>
                                <input
                                    v-model="form.reward_details"
                                    type="text"
                                    :required="form.reward_type === 'upgrade'"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    :placeholder="form.reward_type === 'upgrade' ? 'e.g., VIP Account Upgrade' : 'Optional additional details'"
                                >
                            </div>

                            <!-- Additional Settings -->
                            <div class="md:col-span-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3 mt-4">Additional Settings</h4>
                            </div>

                            <!-- Max Claims Per User -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Max Claims Per User
                                </label>
                                <input
                                    v-model="form.max_claims_per_user"
                                    type="number"
                                    min="1"
                                    :disabled="form.is_recurring"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-slate-600 disabled:cursor-not-allowed"
                                    placeholder="1"
                                >
                            </div>

                            <!-- Checkboxes -->
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                                    >
                                    <label class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        Active
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        v-model="form.is_recurring"
                                        type="checkbox"
                                        class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                                    >
                                    <label class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        Recurring Bonus
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-slate-600">
                            <button
                                type="button"
                                class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors duration-200"
                                @click="closeModals"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="px-6 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center"
                            >
                                <svg
                                    v-if="processing"
                                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                {{ processing ? 'Saving...' : (showEditModal ? 'Update Bonus' : 'Create Bonus') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
            @click="closeModals"
        >
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 ease-out"
                @click.stop
            >
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white text-center mb-2">
                        Delete Referral Bonus
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-center text-sm mb-4">
                        Are you sure you want to delete this bonus? This action cannot be undone and will affect users who haven't claimed it yet.
                    </p>

                    <div
                        v-if="selectedBonus"
                        class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4 mb-4"
                    >
                        <div class="text-sm space-y-2">
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ selectedBonus.name }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                Type: {{ getTypeLabel(selectedBonus.type) }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                Claims: {{ selectedBonus.achievements_count || 0 }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                Status: {{ selectedBonus.is_active ? 'Active' : 'Inactive' }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row-reverse sm:space-x-reverse sm:space-x-3 space-y-3 sm:space-y-0">
                        <button
                            :disabled="processing"
                            class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white font-medium rounded-lg transition-colors duration-200"
                            @click="confirmDelete"
                        >
                            <svg
                                v-if="processing"
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            {{ processing ? 'Deleting...' : 'Delete Bonus' }}
                        </button>
                        <button
                            class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-white hover:bg-gray-50 dark:bg-slate-600 dark:hover:bg-slate-500 text-gray-700 dark:text-gray-300 font-medium rounded-lg border border-gray-300 dark:border-slate-500 transition-colors duration-200"
                            @click="closeModals"
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
</style>

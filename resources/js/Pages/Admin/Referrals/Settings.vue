<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import AdminPagination from "@/Components/AdminPagination.vue";
import { useSettings } from "@/composables/useSettings.js";
import { useToast } from "@/composables/useToast.js";

const props = defineProps({
    settings: {
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
            sort_field: 'level',
            sort_direction: 'asc'
        })
    },
    commission_type_options: {
        type: Array,
        default: () => [
            { value: 'percentage', label: 'Percentage' },
            { value: 'fixed', label: 'Fixed Amount' }
        ]
    },
    applies_to_options: {
        type: Array,
        default: () => [
            { value: 'deposit', label: 'Deposit Only' },
            { value: 'profit', label: 'Profit Only' },
            { value: 'both', label: 'Both Deposit & Profit' }
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
const selectedSetting = ref(null);
const processing = ref(false);
const errors = computed(() => pageProps.props.errors || {});

const form = ref({
    level: '',
    commission_rate: '',
    commission_type: 'percentage',
    fixed_amount: '',
    applies_to: 'deposit',
    min_referrals_required: '',
    min_investment_required: '',
    is_active: true
});

const resetForm = () => {
    form.value = {
        level: '',
        commission_rate: '',
        commission_type: 'percentage',
        fixed_amount: '',
        applies_to: 'deposit',
        min_referrals_required: '',
        min_investment_required: '',
        is_active: true
    };
};

const sortField = computed(() => props.filters.sort_field || 'level');
const sortDirection = computed(() => props.filters.sort_direction || 'asc');

const formatNumber = (value) => {
    if (!value || isNaN(value)) return '0.00';
    return parseFloat(value).toFixed(2);
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

    router.get('/admin/referrals/settings', {
        sort_field: field,
        sort_direction: newDirection,
        page: 1
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
            error('Failed to sort settings');
        }
    });
};

const changePage = (page) => {
    if (isLoading.value) return;

    isLoading.value = true;

    const params = { page };
    if (sortField.value) params.sort_field = sortField.value;
    if (sortDirection.value) params.sort_direction = sortDirection.value;

    router.get('/admin/referrals/settings', params, {
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

const openEditModal = (setting) => {
    selectedSetting.value = setting;
    form.value = {
        level: setting.level,
        commission_rate: setting.commission_rate,
        commission_type: setting.commission_type,
        fixed_amount: setting.fixed_amount || '',
        applies_to: setting.applies_to,
        min_referrals_required: setting.min_referrals_required || '',
        min_investment_required: setting.min_investment_required || '',
        is_active: setting.is_active
    };
    showEditModal.value = true;
};

const openDeleteModal = (setting) => {
    selectedSetting.value = setting;
    showDeleteModal.value = true;
};

const closeModals = () => {
    showAddModal.value = false;
    showEditModal.value = false;
    showDeleteModal.value = false;
    selectedSetting.value = null;
    resetForm();
};

const validateForm = () => {
    const errors = [];

    if (!form.value.level || form.value.level < 1) {
        errors.push('Level must be at least 1');
    }

    if (!form.value.commission_rate || form.value.commission_rate <= 0) {
        errors.push('Commission rate must be greater than 0');
    }

    if (form.value.commission_type === 'fixed' && (!form.value.fixed_amount || form.value.fixed_amount <= 0)) {
        errors.push('Fixed amount is required when commission type is fixed');
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
        level: parseInt(form.value.level),
        commission_rate: parseFloat(form.value.commission_rate),
        commission_type: form.value.commission_type,
        fixed_amount: form.value.commission_type === 'fixed' ? parseFloat(form.value.fixed_amount) : 0,
        applies_to: form.value.applies_to,
        min_referrals_required: parseInt(form.value.min_referrals_required) || 0,
        min_investment_required: parseFloat(form.value.min_investment_required) || 0,
        is_active: form.value.is_active
    };

    const url = showEditModal.value
        ? `/admin/referrals/settings/${selectedSetting.value.id}`
        : '/admin/referrals/settings';

    const method = showEditModal.value ? 'put' : 'post';

    router[method](url, data, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
            success(showEditModal.value ? 'Setting updated successfully' : 'Setting created successfully');
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
    if (!selectedSetting.value) return;

    processing.value = true;

    router.delete(`/admin/referrals/settings/${selectedSetting.value.id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
            success('Setting deleted successfully');
        },
        onError: () => {
            error('Failed to delete setting');
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

const getCommissionTypeLabel = (type) => {
    const option = props.commission_type_options.find(opt => opt.value === type);
    return option ? option.label : type;
};

const getAppliesToLabel = (appliesTo) => {
    const option = props.applies_to_options.find(opt => opt.value === appliesTo);
    return option ? option.label : appliesTo;
};

onMounted(() => {
    const flash = pageProps.props.flash;
    if (flash?.success) success(flash.success);
    if (flash?.error) error(flash.error);
});
</script>

<template>
    <AdminLayout
        title="Referral Settings"
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
                        Referral Level Settings
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Configure commission rates and requirements for each referral level
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                        @click="openAddModal"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Level
                    </button>
                </div>
            </div>
        </div>

        <!-- Settings Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300"
                            @click="handleSort('level')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>LEVEL</span>
                                <svg
                                    v-if="sortField === 'level'"
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
                            @click="handleSort('commission_rate')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>COMMISSION</span>
                                <svg
                                    v-if="sortField === 'commission_rate'"
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
                            TYPE
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            APPLIES TO
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            REQUIREMENTS
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
                    <tr v-if="!isLoading && settings.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    No referral levels configured
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Get started by creating your first referral level setting.
                                </p>
                                <div class="mt-6">
                                    <button
                                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                        @click="openAddModal"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Add First Level
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="isLoading">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">Loading settings...</span>
                            </div>
                        </td>
                    </tr>

                    <tr
                        v-for="setting in settings"
                        :key="setting.id"
                        class="hover:bg-gray-50 dark:hover:bg-slate-700"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <div class="h-full w-full bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                        {{ setting.level }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        Level {{ setting.level }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Referral Tier
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                <div class="font-semibold">
                                    {{ formatNumber(setting.commission_rate) }}%
                                </div>
                                <div v-if="setting.commission_type === 'fixed'" class="text-xs text-gray-500 dark:text-gray-400">
                                    + {{ currencySymbol }}{{ formatNumber(setting.fixed_amount) }} fixed
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ getCommissionTypeLabel(setting.commission_type) }}
                                </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                    {{ getAppliesToLabel(setting.applies_to) }}
                                </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                <div v-if="setting.min_referrals_required > 0">
                                    {{ setting.min_referrals_required }} referrals min
                                </div>
                                <div v-if="setting.min_investment_required > 0">
                                    {{ currencySymbol }}{{ formatNumber(setting.min_investment_required) }} investment min
                                </div>
                                <div v-if="setting.min_referrals_required === 0 && setting.min_investment_required === 0" class="text-gray-500 dark:text-gray-400">
                                    No requirements
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="setting.is_active
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'"
                                >
                                    {{ setting.is_active ? 'Active' : 'Inactive' }}
                                </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <button
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors duration-200"
                                    @click="openEditModal(setting)"
                                >
                                    <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>

                                <button
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors duration-200"
                                    @click="openDeleteModal(setting)"
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
            item-name="settings"
            @page-change="changePage"
        />

        <!-- Add/Edit Modal -->
        <div
            v-if="showAddModal || showEditModal"
            class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
            @click="closeModals"
        >
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 ease-out"
                @click.stop
            >
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        {{ showEditModal ? 'Edit Referral Level' : 'Add Referral Level' }}
                    </h3>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <!-- Level -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Level *
                            </label>
                            <input
                                v-model="form.level"
                                type="number"
                                min="1"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="5.00"
                            >
                        </div>


                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Commission Rate (%) *
                            </label>
                            <input
                                v-model="form.commission_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="10.00"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Commission Type *
                            </label>
                            <select
                                v-model="form.commission_type"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                                <option
                                    v-for="option in commission_type_options"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>


                        <div v-if="form.commission_type === 'fixed'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Fixed Amount ({{ currencySymbol }}) *
                            </label>
                            <input
                                v-model="form.fixed_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="5.00"
                            >
                        </div>

                        <!-- Applies To -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Applies To *
                            </label>
                            <select
                                v-model="form.applies_to"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                                <option
                                    v-for="option in applies_to_options"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Min Referrals Required -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Minimum Referrals Required
                            </label>
                            <input
                                v-model="form.min_referrals_required"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="0"
                            >
                        </div>

                        <!-- Min Investment Required -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Minimum Investment Required ({{ currencySymbol }})
                            </label>
                            <input
                                v-model="form.min_investment_required"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="0.00"
                            >
                        </div>

                        <!-- Is Active -->
                        <div class="flex items-center">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <label class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                Active
                            </label>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors duration-200"
                                @click="closeModals"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center"
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
                                {{ processing ? 'Saving...' : (showEditModal ? 'Update Level' : 'Create Level') }}
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
                        Delete Referral Level
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-center text-sm mb-4">
                        Are you sure you want to delete this referral level? This action cannot be undone.
                    </p>

                    <div
                        v-if="selectedSetting"
                        class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4 mb-4"
                    >
                        <div class="text-sm space-y-2">
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                Level {{ selectedSetting.level }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                Commission: {{ selectedSetting.commission_rate }}%
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                Type: {{ getCommissionTypeLabel(selectedSetting.commission_type) }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                Status: {{ selectedSetting.is_active ? 'Active' : 'Inactive' }}
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
                            {{ processing ? 'Deleting...' : 'Delete Level' }}
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

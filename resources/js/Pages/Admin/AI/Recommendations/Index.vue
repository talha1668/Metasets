<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import AdminPagination from "@/Components/AdminPagination.vue";
import { useSettings } from "@/composables/useSettings.js";
import { useToast } from "@/composables/useToast.js";

const props = defineProps({
    recommendations: {
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
    stats: {
        type: Object,
        default: () => ({
            total_recommendations: 0,
            active_recommendations: 0,
            applied_recommendations: 0,
            dismissed_recommendations: 0,
            high_priority_count: 0,
            average_confidence: 0,
            effectiveness_rate: 0,
            total_users_with_recommendations: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    filter_options: {
        type: Object,
        default: () => ({
            types: [],
            priorities: [],
            statuses: []
        })
    }
});

const { currencySymbol } = useSettings();
const { showToast } = useToast();

// State
const isLoading = ref(false);
const isProcessing = ref(false);
const error = ref(null);
const searchTerm = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || '');
const selectedPriority = ref(props.filters?.priority || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedUserId = ref(props.filters?.user_id || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const selectedRecommendations = ref([]);
const showBulkActions = ref(false);
const showGenerateModal = ref(false);
const showDetailModal = ref(false);
const selectedRecommendation = ref(null);

const generateForm = ref({
    user_id: '',
    type: 'all'
});

// Computed
const currentPage = computed(() => props.meta.current_page || 1);
const lastPage = computed(() => props.meta.last_page || 1);
const sortField = computed(() => props.filters.sort_field || 'created_at');
const sortDirection = computed(() => props.filters.sort_direction || 'desc');

const hasActiveFilters = computed(() => {
    return !!(searchTerm.value || selectedType.value || selectedPriority.value ||
        selectedStatus.value || selectedUserId.value || dateFrom.value || dateTo.value);
});

const allSelected = computed({
    get: () => props.recommendations.length > 0 &&
        selectedRecommendations.value.length === props.recommendations.length,
    set: (value) => {
        if (value) {
            selectedRecommendations.value = props.recommendations.map(r => r.id);
        } else {
            selectedRecommendations.value = [];
        }
    }
});

const someSelected = computed(() => {
    return selectedRecommendations.value.length > 0 &&
        selectedRecommendations.value.length < props.recommendations.length;
});

// Methods
const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

const applyFilters = () => {
    if (isLoading.value) return;

    isLoading.value = true;
    error.value = null;

    const params = {
        page: 1,
        sort_field: sortField.value,
        sort_direction: sortDirection.value
    };

    if (searchTerm.value?.trim()) params.search = searchTerm.value.trim();
    if (selectedType.value) params.type = selectedType.value;
    if (selectedPriority.value) params.priority = selectedPriority.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedUserId.value) params.user_id = selectedUserId.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    router.get('/admin/ai/recommendations', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => isLoading.value = false,
        onError: (errors) => {
            isLoading.value = false;
            error.value = 'Failed to apply filters. Please try again.';
            showToast('Failed to apply filters', 'error');
        }
    });
};

const clearAllFilters = () => {
    searchTerm.value = '';
    selectedType.value = '';
    selectedPriority.value = '';
    selectedStatus.value = '';
    selectedUserId.value = '';
    dateFrom.value = '';
    dateTo.value = '';

    isLoading.value = true;
    error.value = null;

    router.get('/admin/ai/recommendations', {
        sort_field: 'created_at',
        sort_direction: 'desc'
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
            showToast('Filters cleared successfully', 'success');
        },
        onError: () => {
            isLoading.value = false;
            error.value = 'Failed to clear filters. Please try again.';
            showToast('Failed to clear filters', 'error');
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
    error.value = null;

    const params = {
        sort_field: field,
        sort_direction: newDirection,
        page: 1
    };

    // Preserve current filters
    if (searchTerm.value?.trim()) params.search = searchTerm.value.trim();
    if (selectedType.value) params.type = selectedType.value;
    if (selectedPriority.value) params.priority = selectedPriority.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedUserId.value) params.user_id = selectedUserId.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    router.get('/admin/ai/recommendations', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => isLoading.value = false,
        onError: (errors) => {
            isLoading.value = false;
            error.value = 'Failed to sort recommendations. Please try again.';
            showToast('Failed to sort recommendations', 'error');
        }
    });
};

const changePage = (page) => {
    if (isLoading.value || page === currentPage.value) return;

    isLoading.value = true;
    error.value = null;

    const params = { page };
    if (searchTerm.value?.trim()) params.search = searchTerm.value.trim();
    if (selectedType.value) params.type = selectedType.value;
    if (selectedPriority.value) params.priority = selectedPriority.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedUserId.value) params.user_id = selectedUserId.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;
    if (sortField.value) params.sort_field = sortField.value;
    if (sortDirection.value) params.sort_direction = sortDirection.value;

    router.get('/admin/ai/recommendations', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => isLoading.value = false,
        onError: (errors) => {
            isLoading.value = false;
            error.value = 'Failed to load page. Please try again.';
            showToast('Failed to load page', 'error');
        }
    });
};

const openGenerateModal = () => {
    generateForm.value = {
        user_id: '',
        type: 'all'
    };
    showGenerateModal.value = true;
};

const generateRecommendations = () => {
    if (isProcessing.value) return;

    isProcessing.value = true;

    router.post('/admin/ai/recommendations/generate', generateForm.value, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showGenerateModal.value = false;
            showToast('AI recommendations generated successfully', 'success');
        },
        onError: (errors) => {
            const errorMessage = errors.error || 'Failed to generate recommendations';
            showToast(errorMessage, 'error');
        },
        onFinish: () => isProcessing.value = false
    });
};

const handleBulkAction = (action) => {
    if (selectedRecommendations.value.length === 0) {
        showToast('Please select recommendations first', 'warning');
        return;
    }

    if (isProcessing.value) return;

    const actionLabels = {
        activate: 'activate',
        dismiss: 'dismiss',
        mark_applied: 'mark as applied',
        delete: 'delete'
    };

    const confirmMessage = `Are you sure you want to ${actionLabels[action]} ${selectedRecommendations.value.length} recommendation(s)?`;

    if (!confirm(confirmMessage)) return;

    isProcessing.value = true;

    router.post('/admin/ai/recommendations/bulk-update', {
        recommendation_ids: selectedRecommendations.value,
        action: action
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            selectedRecommendations.value = [];
            showBulkActions.value = false;
            showToast(`Recommendations ${actionLabels[action]}d successfully`, 'success');
        },
        onError: (errors) => {
            const errorMessage = errors.error || `Failed to ${actionLabels[action]} recommendations`;
            showToast(errorMessage, 'error');
        },
        onFinish: () => isProcessing.value = false
    });
};

const viewRecommendationDetails = (recommendation) => {
    selectedRecommendation.value = recommendation;
    showDetailModal.value = true;
};

const getTypeLabel = (type) => {
    const typeLabels = {
        investment_plan: 'Investment Plan',
        reinvestment: 'Reinvestment'
    };
    return typeLabels[type] || type;
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700',
        dismissed: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700',
        applied: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700'
    };
    return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-700';
};

const getPriorityClass = (priority) => {
    const classes = {
        high: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700',
        medium: 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-700',
        low: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-700'
    };
    return classes[priority] || classes.low;
};

const getConfidenceClass = (confidence) => {
    if (confidence >= 80) return 'text-green-600 dark:text-green-400';
    if (confidence >= 60) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatNumber = (value) => {
    if (!value || isNaN(value)) return '0.00';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

// Watch for selection changes
watch(selectedRecommendations, (newVal) => {
    showBulkActions.value = newVal.length > 0;
}, { deep: true });

onMounted(() => {
    const page = usePage();
    const flash = page.props.flash;
    if (flash?.success) showToast(flash.success, 'success');
    if (flash?.error) {
        error.value = flash.error;
        showToast(flash.error, 'error');
    }
    if (flash?.warning) showToast(flash.warning, 'warning');
    if (flash?.info) showToast(flash.info, 'info');
});
</script>

<template>
    <AdminLayout
        title="AI Recommendations"
        page-section="AI Analytics"
    >
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        AI Recommendations
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        AI-powered investment recommendations
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors duration-200"
                        @click="openGenerateModal"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Generate AI Recommendations
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Recommendations
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ props.stats.total_recommendations }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Active
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ props.stats.active_recommendations }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Effectiveness Rate
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ formatNumber(props.stats.effectiveness_rate) }}%
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Avg Confidence
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ formatNumber(props.stats.average_confidence) }}%
                        </p>
                    </div>
                </div>
            </div>
        </div>

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
                                placeholder="Search recommendations..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                @input="debouncedSearch"
                            >
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <select
                            v-model="selectedType"
                            class="px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            @change="applyFilters"
                        >
                            <option value="">All Types</option>
                            <option v-for="type in filter_options.types" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>

                        <select
                            v-model="selectedPriority"
                            class="px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            @change="applyFilters"
                        >
                            <option value="">All Priorities</option>
                            <option v-for="priority in filter_options.priorities" :key="priority.value" :value="priority.value">
                                {{ priority.label }}
                            </option>
                        </select>

                        <select
                            v-model="selectedStatus"
                            class="px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            @change="applyFilters"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="status in filter_options.statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>

                        <input
                            v-model="dateFrom"
                            type="date"
                            class="px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            @change="applyFilters"
                        >

                        <input
                            v-model="dateTo"
                            type="date"
                            class="px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            @change="applyFilters"
                        >

                        <button
                            v-if="hasActiveFilters"
                            class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 focus:ring-2 focus:ring-blue-500 transition-colors duration-200"
                            @click="clearAllFilters"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showBulkActions" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 mb-6">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ selectedRecommendations.length }} recommendation(s) selected
                    </span>
                    <div class="flex space-x-2">
                        <button
                            class="px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50"
                            @click="handleBulkAction('activate')"
                            :disabled="isProcessing"
                        >
                            Activate
                        </button>
                        <button
                            class="px-3 py-1.5 text-xs font-medium text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50"
                            @click="handleBulkAction('mark_applied')"
                            :disabled="isProcessing"
                        >
                            Mark Applied
                        </button>
                        <button
                            class="px-3 py-1.5 text-xs font-medium text-yellow-700 dark:text-yellow-300 bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50"
                            @click="handleBulkAction('dismiss')"
                            :disabled="isProcessing"
                        >
                            Dismiss
                        </button>
                        <button
                            class="px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50"
                            @click="handleBulkAction('delete')"
                            :disabled="isProcessing"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input
                                type="checkbox"
                                v-model="allSelected"
                                :indeterminate="someSelected"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            >
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300"
                            @click="handleSort('title')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>RECOMMENDATION</span>
                                <svg
                                    v-if="sortField === 'title'"
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
                            USER
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            TYPE
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300"
                            @click="handleSort('priority')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>PRIORITY</span>
                                <svg
                                    v-if="sortField === 'priority'"
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
                            @click="handleSort('confidence_score')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>CONFIDENCE</span>
                                <svg
                                    v-if="sortField === 'confidence_score'"
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
                            @click="handleSort('status')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>STATUS</span>
                                <svg
                                    v-if="sortField === 'status'"
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
                            @click="handleSort('created_at')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>DATE</span>
                                <svg
                                    v-if="sortField === 'created_at'"
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
                            ACTIONS
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    <tr v-if="!isLoading && recommendations.length === 0">
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    No AI recommendations found
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    No recommendations match your current filters.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="isLoading">
                        <td colspan="9" class="px-6 py-12 text-center">
                            <div class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">Loading recommendations...</span>
                            </div>
                        </td>
                    </tr>

                    <tr
                        v-for="recommendation in recommendations"
                        :key="recommendation.id"
                        class="hover:bg-gray-50 dark:hover:bg-slate-700"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input
                                type="checkbox"
                                :value="recommendation.id"
                                v-model="selectedRecommendations"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            >
                        </td>

                        <td class="px-6 py-4">
                            <div class="max-w-xs">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ recommendation.title }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                    {{ recommendation.description }}
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ recommendation.user?.name || 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ recommendation.user?.email || 'N/A' }}
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ getTypeLabel(recommendation.type) }}
                                </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border"
                                    :class="getPriorityClass(recommendation.priority)"
                                >
                                    {{ recommendation.priority?.charAt(0).toUpperCase() + recommendation.priority?.slice(1) || 'N/A' }}
                                </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <div
                                        class="text-sm font-medium"
                                        :class="getConfidenceClass(recommendation.confidence_score)"
                                    >
                                        {{ formatNumber(recommendation.confidence_score) }}%
                                    </div>
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5 mt-1">
                                        <div
                                            class="h-1.5 rounded-full"
                                            :class="getConfidenceClass(recommendation.confidence_score).replace('text-', 'bg-')"
                                            :style="{ width: `${recommendation.confidence_score}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border"
                                    :class="getStatusClass(recommendation.status)"
                                >
                                    {{ recommendation.status?.charAt(0).toUpperCase() + recommendation.status?.slice(1) || 'N/A' }}
                                </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ formatDate(recommendation.created_at) }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <button
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 focus:ring-2 focus:ring-indigo-500 transition-colors duration-200"
                                @click="viewRecommendationDetails(recommendation)"
                            >
                                <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View Details
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <AdminPagination
            v-if="meta.total > 0"
            :current-page="currentPage"
            :last-page="lastPage"
            :total="meta.total"
            :per-page="20"
            item-name="recommendations"
            @page-change="changePage"
        />

        <!-- Generate Recommendations Modal -->
        <div
            v-if="showGenerateModal"
            class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
            @click="showGenerateModal = false"
        >
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 ease-out"
                @click.stop
            >
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 dark:bg-blue-900/30 rounded-full mb-4">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white text-center mb-4">
                        Generate AI Recommendations
                    </h3>

                    <form @submit.prevent="generateRecommendations" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                User ID (Optional)
                            </label>
                            <input
                                v-model="generateForm.user_id"
                                type="number"
                                placeholder="Leave empty for all users"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Recommendation Type
                            </label>
                            <select
                                v-model="generateForm.type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="all">All Types</option>
                                <option value="investment_plan">Investment Plan</option>
                                <option value="reinvestment">Reinvestment</option>
                            </select>
                        </div>

                        <div class="flex flex-col sm:flex-row-reverse sm:space-x-reverse sm:space-x-3 space-y-3 sm:space-y-0 mt-6">
                            <button
                                type="submit"
                                :disabled="isProcessing"
                                class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                <svg
                                    v-if="isProcessing"
                                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                {{ isProcessing ? 'Generating...' : 'Generate Recommendations' }}
                            </button>
                            <button
                                type="button"
                                class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-white hover:bg-gray-50 dark:bg-slate-600 dark:hover:bg-slate-500 text-gray-700 dark:text-gray-300 font-medium rounded-lg border border-gray-300 dark:border-slate-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                                @click="showGenerateModal = false"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recommendation Details Modal -->
        <div
            v-if="showDetailModal && selectedRecommendation"
            class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
            @click="showDetailModal = false"
        >
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto transform transition-all duration-300 ease-out"
                @click.stop
            >
                <div class="p-6">
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                {{ selectedRecommendation.title }}
                            </h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ selectedRecommendation.user?.username || 'N/A' }}
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ formatDate(selectedRecommendation.created_at) }}
                                </span>
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium border"
                                    :class="getStatusClass(selectedRecommendation.status)"
                                >
                                    {{ selectedRecommendation.status?.charAt(0).toUpperCase() + selectedRecommendation.status?.slice(1) }}
                                </span>
                            </div>
                        </div>
                        <button
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            @click="showDetailModal = false"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Type</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ getTypeLabel(selectedRecommendation.type) }}
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Priority</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ selectedRecommendation.priority?.charAt(0).toUpperCase() + selectedRecommendation.priority?.slice(1) }}
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Confidence Score</div>
                            <div class="flex items-center">
                                <div
                                    class="text-lg font-bold"
                                    :class="getConfidenceClass(selectedRecommendation.confidence_score)"
                                >
                                    {{ formatNumber(selectedRecommendation.confidence_score) }}%
                                </div>
                                <div class="ml-2 flex-1">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            class="h-2 rounded-full"
                                            :class="getConfidenceClass(selectedRecommendation.confidence_score).replace('text-', 'bg-')"
                                            :style="{ width: `${selectedRecommendation.confidence_score}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Description</h4>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            {{ selectedRecommendation.description }}
                        </p>
                    </div>

                    <div v-if="selectedRecommendation.recommendation_data" class="mb-6">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Recommendation Details</h4>
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                            <pre class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap overflow-x-auto">{{ JSON.stringify(selectedRecommendation.recommendation_data, null, 2) }}</pre>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white hover:bg-gray-50 dark:bg-slate-600 dark:hover:bg-slate-500 border border-gray-300 dark:border-slate-500 rounded-lg transition-colors duration-200"
                            @click="showDetailModal = false"
                        >
                            Close
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

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@media (max-width: 768px) {
    .grid-cols-1.md\:grid-cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .grid-cols-1.md\:grid-cols-4 {
        grid-template-columns: 1fr;
    }
}
</style>

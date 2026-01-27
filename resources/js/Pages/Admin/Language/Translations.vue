<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminPagination from "@/Components/AdminPagination.vue";
import { useToast } from "@/composables/useToast.js";
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";

const props = defineProps({
    language: {
        type: Object,
        required: true
    },
    translations: {
        type: Object,
        required: true
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, translated: 0, missing: 0 })
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    components: {
        type: Array,
        default: () => []
    }
});

const { showToast } = useToast();
const isLoading = ref(false);
const searchTerm = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');
const selectedComponent = ref(props.filters.component || 'general');
const showAddModal = ref(false);
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const editingTranslation = ref(null);

const newTranslationForm = ref({
    key: '',
    value: ''
});

// Computed
const statusOptions = [
    { value: 'all', label: 'All Translations' },
    { value: 'translated', label: 'Translated' },
    { value: 'untranslated', label: 'Missing Translation' }
];

// Use components from controller instead of hardcoded list
const componentOptions = computed(() => {
    return props.components && props.components.length > 0 ? props.components : [
        { value: 'general', label: 'General & Common' },
        { value: 'hero', label: 'Hero & Landing' },
        { value: 'dashboard', label: 'Dashboard' },
        { value: 'navigation', label: 'Navigation' },
        { value: 'transactions', label: 'Transactions' },
        { value: 'earnings', label: 'Earnings' },
        { value: 'wallet', label: 'Wallet' },
        { value: 'deposits', label: 'Deposits & Withdrawals' },
        { value: 'investment', label: 'Investment & Staking' },
        { value: 'referrals', label: 'Referrals & Network' },
        { value: 'ai_recommendations', label: 'AI Recommendations' },
        { value: 'support', label: 'Support & Tickets' },
        { value: 'authentication', label: 'Authentication' },
        { value: 'profile', label: 'Profile & Settings' },
        { value: 'kyc', label: 'KYC Verification' },
        { value: 'wallet_modal', label: 'Wallet Modal' },
        { value: 'wallet_connection', label: 'Wallet Connection' },
        { value: 'legal', label: 'Legal Pages' },
        { value: 'footer', label: 'Footer' },
        { value: 'dynamic', label: 'Dynamic Content' },
    ];
});

// Calculate stats from translations data if not provided by controller
const calculatedStats = computed(() => {
    // If stats prop is provided and has data, use it
    if (props.stats && props.stats.total > 0) {
        return props.stats;
    }

    // Otherwise calculate from translations
    const total = props.translations?.total || 0;
    const data = props.translations?.data || [];

    if (data.length === 0) {
        return { total: 0, translated: 0, missing: 0 };
    }

    const translated = data.filter(t => t.value && t.value.trim() !== '').length;
    const missing = data.filter(t => !t.value || t.value.trim() === '').length;

    return {
        total: total,
        translated: translated,
        missing: missing
    };
});

const formatKey = (key) => {
    return key.split('.').pop().replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getStatusColor = (translation) => {
    if (translation.value && translation.value.trim() !== '') {
        return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-300';
    }
    return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-300';
};

const getStatusText = (translation) => {
    return (translation.value && translation.value.trim() !== '') ? 'Translated' : 'Missing';
};

const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

const applyFilters = () => {
    if (isLoading.value) return;
    isLoading.value = true;
    const params = {
        page: 1,
        search: searchTerm.value.trim() || undefined,
        status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
        component: selectedComponent.value !== 'general' ? selectedComponent.value : undefined
    };

    router.get(`/admin/languages/${props.language.id}/translations`, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
            showToast('Failed to apply filters', 'error');
        }
    });
};

const clearFilters = () => {
    searchTerm.value = '';
    selectedStatus.value = 'all';
    selectedComponent.value = 'general';

    router.get(`/admin/languages/${props.language.id}/translations`, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            showToast('Filters cleared', 'success');
        }
    });
};

const changePage = (page) => {
    if (isLoading.value) return;

    isLoading.value = true;

    const params = {
        page,
        search: searchTerm.value.trim() || undefined,
        status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
        component: selectedComponent.value !== 'general' ? selectedComponent.value : undefined
    };

    router.get(`/admin/languages/${props.language.id}/translations`, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
            showToast('Failed to change page', 'error');
        }
    });
};

const updateTranslation = (translation) => {
    if (!translation.value || translation.value.trim() === '') {
        showToast('Translation value cannot be empty', 'error');
        return;
    }

    router.put(`/admin/languages/${props.language.id}/translations/${translation.id}`, {
        value: translation.value
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            editingTranslation.value = null;
            showToast('Translation updated successfully', 'success');
        },
        onError: () => {
            showToast('Failed to update translation', 'error');
        }
    });
};

const addTranslation = () => {
    if (!newTranslationForm.value.key || newTranslationForm.value.key.trim() === '') {
        showToast('Translation key is required', 'error');
        return;
    }

    router.post(`/admin/languages/${props.language.id}/translations`, {
        key: newTranslationForm.value.key,
        value: newTranslationForm.value.value,
        is_dynamic: 1
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeAddModal();
            showToast('Translation added successfully', 'success');
            newTranslationForm.value = { key: '', value: '' };
        },
        onError: () => {
            showToast('Failed to add translation', 'error');
        }
    });
};

const openDeleteModal = (translation) => {
    deleteTarget.value = translation;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteTarget.value = null;
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;

    router.delete(`/admin/languages/${props.language.id}/translations/${deleteTarget.value.id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            showToast('Translation deleted successfully', 'success');
        },
        onError: () => {
            showToast('Failed to delete translation', 'error');
        }
    });
};

const closeAddModal = () => {
    showAddModal.value = false;
    newTranslationForm.value = { key: '', value: '' };
};
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ language.name }} Translations
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage translations for {{ language.native_name }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Translation
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Translations</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ calculatedStats.total }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Translated</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ calculatedStats.translated }}</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Missing</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ calculatedStats.missing }}</p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Search
                        </label>
                        <input
                            v-model="searchTerm"
                            @input="debouncedSearch"
                            type="text"
                            placeholder="Search translations..."
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 p-2.5 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status
                        </label>
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 p-2.5 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Component
                        </label>
                        <select
                            v-model="selectedComponent"
                            @change="applyFilters"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 p-2.5 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option v-for="option in componentOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button
                        @click="clearFilters"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors duration-200"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Key
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Translation
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    <tr v-if="!translations.data || translations.data.length === 0">
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            No translations found
                        </td>
                    </tr>
                    <tr v-for="translation in translations.data" :key="translation.id" class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatKey(translation.key) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                {{ translation.key }}
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div v-if="editingTranslation === translation.id">
                                <input
                                    v-model="translation.value"
                                    @keyup.enter="updateTranslation(translation)"
                                    @keyup.esc="editingTranslation = null"
                                    type="text"
                                    class="w-full rounded border border-gray-300 dark:border-slate-600 p-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    autofocus
                                />
                                <div class="mt-2 flex gap-2">
                                    <button
                                        @click="updateTranslation(translation)"
                                        class="text-xs px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700"
                                    >
                                        Save
                                    </button>
                                    <button
                                        @click="editingTranslation = null"
                                        class="text-xs px-3 py-1 bg-gray-300 dark:bg-slate-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-400 dark:hover:bg-slate-500"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-900 dark:text-gray-100">
                                {{ translation.value || '—' }}
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getStatusColor(translation)" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                {{ getStatusText(translation) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    @click="editingTranslation = translation.id"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="openDeleteModal(translation)"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <AdminPagination
            v-if="translations.data && translations.data.length > 0"
            :current-page="translations.current_page"
            :last-page="translations.last_page"
            :total="translations.total"
            :per-page="translations.per_page"
            item-name="translations"
            @page-change="changePage"
        />

        <div v-if="showAddModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50" @click="closeAddModal">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 ease-out" @click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Add Translation</h3>
                        </div>
                        <button @click="closeAddModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="addTranslation">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Translation Key *
                                </label>
                                <input
                                    v-model="newTranslationForm.key"
                                    type="text"
                                    placeholder="e.g., welcome_message"
                                    class="w-full rounded-lg border border-gray-300 dark:border-slate-600 p-3 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ language.name }} Translation
                                </label>
                                <textarea
                                    v-model="newTranslationForm.value"
                                    placeholder="Enter translation value..."
                                    class="w-full rounded-lg border border-gray-300 dark:border-slate-600 p-3 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                    rows="3"
                                />
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button
                                type="button"
                                @click="closeAddModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors duration-200"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200"
                            >
                                Add Translation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4 z-50" @click="closeDeleteModal">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 ease-out" @click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Delete Translation</h3>
                        </div>
                        <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mb-6">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Are you sure you want to delete this translation? This action cannot be undone.
                        </p>
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                {{ formatKey(deleteTarget?.key) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono mb-2">
                                {{ deleteTarget?.key }}
                            </div>
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                "{{ deleteTarget?.value || 'No translation' }}"
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            @click="closeDeleteModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors duration-200"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmDelete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors duration-200"
                        >
                            Delete Translation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

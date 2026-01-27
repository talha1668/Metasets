<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import { useSettings } from "@/composables/useSettings.js";
import { useToast } from "@/composables/useToast.js";

const props = defineProps({
    pool: {
        type: Object,
        default: null
    },
    status_options: {
        type: Array,
        default: () => [
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' }
        ]
    }
});

const pageProps = usePage();
const { currencySymbol } = useSettings();
const { success, error } = useToast();

const processing = ref(false);
const errors = computed(() => pageProps.props.errors || {});
const flash = computed(() => pageProps.props.flash);
const isEditing = computed(() => !!props.pool);

const form = ref({
    name: '',
    token_symbol: '',
    description: '',
    min_stake_amount: 0,
    max_stake_amount: null,
    apy_rate: 0,
    lock_days: 30,
    total_pool_size: 0,
    auto_compound: false,
    status: 'active'
});

const formatNumber = (num) => {
    if (!num || isNaN(num)) return '0.00';
    const numValue = parseFloat(num);

    if (numValue < 0.01 && numValue > 0) {
        return numValue.toString();
    }

    return numValue.toFixed(2);
};

const initializeForm = () => {
    if (props.pool) {
        form.value = {
            name: props.pool.name || '',
            token_symbol: props.pool.token_symbol || '',
            description: props.pool.description || '',
            min_stake_amount: parseFloat(props.pool.min_stake_amount) || 0,
            max_stake_amount: props.pool.max_stake_amount ? parseFloat(props.pool.max_stake_amount) : null,
            apy_rate: parseFloat(props.pool.apy_rate) || 0,
            lock_days: parseInt(props.pool.lock_days) || 30,
            total_pool_size: parseFloat(props.pool.total_pool_size) || 0,
            auto_compound: props.pool.auto_compound || false,
            status: props.pool.status || 'active'
        };
    }
};

const validateForm = () => {
    const errors = [];

    if (!form.value.name.trim()) {
        errors.push('Pool name is required');
    }

    if (!form.value.token_symbol.trim()) {
        errors.push('Token symbol is required');
    }

    if (!form.value.description.trim()) {
        errors.push('Description is required');
    }

    if (parseFloat(form.value.min_stake_amount) < 0) {
        errors.push('Minimum stake amount cannot be negative');
    }

    if (form.value.max_stake_amount && parseFloat(form.value.max_stake_amount) <= 0) {
        errors.push('Maximum stake amount must be greater than 0');
    }

    if (form.value.max_stake_amount && parseFloat(form.value.min_stake_amount) >= parseFloat(form.value.max_stake_amount)) {
        errors.push('Minimum stake amount must be less than maximum stake amount');
    }

    if (parseFloat(form.value.apy_rate) <= 0) {
        errors.push('APY rate must be greater than 0');
    }

    if (parseInt(form.value.lock_days) <= 0) {
        errors.push('Lock period must be greater than 0 days');
    }

    if (parseFloat(form.value.total_pool_size) < 0) {
        errors.push('Total pool size cannot be negative');
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
    const formData = new FormData();

    Object.keys(form.value).forEach(key => {
        let value = form.value[key];

        if (typeof value === 'boolean') {
            value = value ? '1' : '0';
        }

        if (value === null || value === '') {
            return;
        }

        formData.append(key, value);
    });

    if (isEditing.value) {
        formData.append('_method', 'PUT');
    }

    const url = isEditing.value
        ? `/admin/staking-pools/${props.pool.id}`
        : '/admin/staking-pools';

    router.post(url, formData, {
        forceFormData: true,
        onSuccess: () => {
            setTimeout(() => {
                router.visit('/admin/staking-pools');
            }, 1000);
        },
        onError: (errors) => {
            processing.value = false;
            const firstError = Object.values(errors)[0];
            if (Array.isArray(firstError)) {
                error(firstError[0]);
            } else {
                error(firstError || 'An error occurred');
            }
        }
    });
};

watch(flash, (newFlash) => {
    if (newFlash?.success) {
        success(newFlash.success);
    } else if (newFlash?.error) {
        error(newFlash.error);
    }
}, { immediate: true });

onMounted(() => {
    initializeForm();
    if (flash.value?.success) {
        success(flash.value.success);
    } else if (flash.value?.error) {
        error(flash.value.error);
    }
});
</script>

<template>
    <AdminLayout
        :title="`${isEditing ? 'Edit' : 'Create'} Staking Pool`"
        page-section="Staking"
    >
        <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <div class="flex items-center gap-4 mb-4">
                    <Link
                        href="/admin/staking-pools"
                        class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg
                            class="w-4 h-4 mr-1"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                        Back to Pools
                    </Link>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ isEditing ? 'Edit Staking Pool' : 'Create Staking Pool' }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ isEditing ? 'Update staking pool configuration and settings' : 'Set up a new staking pool for users' }}
                </p>
            </div>

            <form
                class="space-y-6"
                @submit.prevent="submitForm"
            >
                <!-- Basic Information -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Basic Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Pool Name *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="e.g., Bitcoin Staking Pool"
                            >
                            <p
                                v-if="errors.name"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Token Symbol *
                            </label>
                            <input
                                v-model="form.token_symbol"
                                type="text"
                                required
                                maxlength="10"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="e.g., BTC, ETH, USDT"
                            >
                            <p
                                v-if="errors.token_symbol"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.token_symbol }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Status *
                            </label>
                            <select
                                v-model="form.status"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                                <option
                                    v-for="status in status_options"
                                    :key="status.value"
                                    :value="status.value"
                                >
                                    {{ status.label }}
                                </option>
                            </select>
                            <p
                                v-if="errors.status"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.status }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description *
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="Describe the staking pool details and benefits"
                            />
                            <p
                                v-if="errors.description"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Staking Configuration -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Staking Configuration
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Minimum Stake Amount *
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                    {{ currencySymbol }}
                                </span>
                                <input
                                    v-model="form.min_stake_amount"
                                    type="number"
                                    step="0.00000001"
                                    min="0"
                                    required
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0.00"
                                >
                            </div>
                            <p
                                v-if="errors.min_stake_amount"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.min_stake_amount }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Maximum Stake Amount (Optional)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                    {{ currencySymbol }}
                                </span>
                                <input
                                    v-model="form.max_stake_amount"
                                    type="number"
                                    step="0.00000001"
                                    min="0"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0.00 (Leave empty for unlimited)"
                                >
                            </div>
                            <p
                                v-if="errors.max_stake_amount"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.max_stake_amount }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                APY Rate * (%)
                            </label>
                            <input
                                v-model="form.apy_rate"
                                type="number"
                                step="0.0001"
                                min="0"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="0.00"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Annual Percentage Yield
                            </p>
                            <p
                                v-if="errors.apy_rate"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.apy_rate }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Lock Period * (Days)
                            </label>
                            <input
                                v-model="form.lock_days"
                                type="number"
                                min="1"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="30"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Duration tokens are locked
                            </p>
                            <p
                                v-if="errors.lock_days"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.lock_days }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Total Pool Size
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                    {{ currencySymbol }}
                                </span>
                                <input
                                    v-model="form.total_pool_size"
                                    type="number"
                                    step="0.00000001"
                                    min="0"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0.00 (0 = unlimited)"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Maximum capacity of the pool
                            </p>
                            <p
                                v-if="errors.total_pool_size"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.total_pool_size }}
                            </p>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Auto-Compound Rewards
                            </label>
                            <div class="flex items-center h-8">
                                <button
                                    type="button"
                                    class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    :class="form.auto_compound ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                    @click="form.auto_compound = !form.auto_compound"
                                >
                                    <span
                                        class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform shadow-lg"
                                        :class="form.auto_compound ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ form.auto_compound ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Automatically reinvest earned rewards
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <Link
                        href="/admin/staking-pools"
                        class="px-6 py-3 border border-gray-300 dark:border-slate-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors duration-200"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200 flex items-center"
                    >
                        <svg
                            v-if="processing"
                            class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
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
                                d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        {{ processing ? 'Saving...' : (isEditing ? 'Update Pool' : 'Create Pool') }}
                    </button>
                </div>
            </form>
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

button:not(:disabled):hover {
    transform: translateY(-1px);
    transition: all 0.2s ease-in-out;
}

button:focus,
input:focus,
select:focus,
textarea:focus {
    outline: none;
}

::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.5);
}

@media (max-width: 768px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

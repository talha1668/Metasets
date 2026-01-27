<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from "@/Layouts/AdminLayout/AdminLayout.vue";
import { useSettings } from "@/composables/useSettings.js";
import { useToast } from "@/composables/useToast.js";

const props = defineProps({
    plan: {
        type: Object,
        default: null
    },
    status_options: {
        type: Array,
        default: () => [
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' },
            { value: 'coming_soon', label: 'Coming Soon' }
        ]
    },
    interest_types: {
        type: Array,
        default: () => [
            { value: 'fixed', label: 'Fixed' },
            { value: 'compound', label: 'Compound' }
        ]
    },
    return_types: {
        type: Array,
        default: () => [
            { value: 'hourly', label: 'Hourly' },
            { value: 'daily', label: 'Daily' },
            { value: 'weekly', label: 'Weekly' },
            { value: 'monthly', label: 'Monthly' }
        ]
    },
    principal_return_options: {
        type: Array,
        default: () => [
            { value: 'yes', label: 'Yes' },
            { value: 'no', label: 'No' },
            { value: 'after_maturity', label: 'After Maturity' }
        ]
    },
    risk_levels: {
        type: Array,
        default: () => [
            { value: 'low', label: 'Low' },
            { value: 'medium', label: 'Medium' },
            { value: 'high', label: 'High' }
        ]
    }
});

const pageProps = usePage();
const { currencySymbol } = useSettings();
const { success, error } = useToast();

const processing = ref(false);
const errors = computed(() => pageProps.props.errors || {});
const flash = computed(() => pageProps.props.flash);
const isEditing = computed(() => !!props.plan);

const form = ref({
    name: '',
    description: '',
    features: [],
    min_amount: 0,
    max_amount: 0,
    interest_rate: 0,
    interest_type: 'fixed',
    return_type: 'daily',
    duration_days: 30,
    principal_return: 'yes',
    auto_reinvest_available: false,
    auto_reinvest_rate: 0,
    partial_withdrawal_allowed: false,
    early_withdrawal_fee: 0,
    max_investors: 0,
    total_cap: 0,
    risk_level: 'medium',
    ai_recommendation_score: [],
    success_rate: 0,
    color_scheme: '#3b82f6',
    badges: [],
    sort_order: 0,
    featured: false,
    status: 'active',
    starts_at: '',
    ends_at: ''
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
    if (props.plan) {
        form.value = {
            name: props.plan.name || '',
            description: props.plan.description || '',
            features: [],
            min_amount: parseFloat(props.plan.min_amount) || 0,
            max_amount: parseFloat(props.plan.max_amount) || 0,
            interest_rate: parseFloat(props.plan.interest_rate) || 0,
            interest_type: props.plan.interest_type || 'fixed',
            return_type: props.plan.return_type || 'daily',
            duration_days: parseInt(props.plan.duration_days) || 30,
            principal_return: props.plan.principal_return || 'yes',
            auto_reinvest_available: props.plan.auto_reinvest_available || false,
            auto_reinvest_rate: parseFloat(props.plan.auto_reinvest_rate) || 0,
            partial_withdrawal_allowed: props.plan.partial_withdrawal_allowed || false,
            early_withdrawal_fee: parseFloat(props.plan.early_withdrawal_fee) || 0,
            max_investors: parseInt(props.plan.max_investors) || 0,
            total_cap: parseFloat(props.plan.total_cap) || 0,
            risk_level: props.plan.risk_level || 'medium',
            ai_recommendation_score: [],
            success_rate: parseFloat(props.plan.success_rate) || 0,
            color_scheme: props.plan.color_scheme || '#3b82f6',
            badges: [],
            sort_order: parseInt(props.plan.sort_order) || 0,
            featured: props.plan.featured || false,
            status: props.plan.status || 'active',
            starts_at: props.plan.starts_at ? props.plan.starts_at.split('T')[0] : '',
            ends_at: props.plan.ends_at ? props.plan.ends_at.split('T')[0] : ''
        };

        if (props.plan.features) {
            try {
                const features = typeof props.plan.features === 'string'
                    ? JSON.parse(props.plan.features)
                    : props.plan.features;

                form.value.features = Array.isArray(features)
                    ? features.map(f => ({ text: f }))
                    : Object.entries(features).map(([key, value]) => ({ text: `${key}: ${value}` }));
            } catch (e) {
                form.value.features = [];
            }
        }

        if (props.plan.ai_recommendation_score) {
            try {
                const aiScore = typeof props.plan.ai_recommendation_score === 'string'
                    ? JSON.parse(props.plan.ai_recommendation_score)
                    : props.plan.ai_recommendation_score;

                form.value.ai_recommendation_score = Array.isArray(aiScore)
                    ? aiScore.map(s => ({ key: s.key || '', value: s.value || '' }))
                    : Object.entries(aiScore).map(([key, value]) => ({ key, value }));
            } catch (e) {
                form.value.ai_recommendation_score = [];
            }
        }

        if (props.plan.badges) {
            try {
                const badges = typeof props.plan.badges === 'string'
                    ? JSON.parse(props.plan.badges)
                    : props.plan.badges;

                form.value.badges = Array.isArray(badges)
                    ? badges.map(b => ({ text: b }))
                    : Object.entries(badges).map(([key, value]) => ({ text: `${key}: ${value}` }));
            } catch (e) {
                form.value.badges = [];
            }
        }
    }
};

const addFeature = () => {
    form.value.features.push({ text: '' });
};

const removeFeature = (index) => {
    form.value.features.splice(index, 1);
};

const addBadge = () => {
    form.value.badges.push({ text: '' });
};

const removeBadge = (index) => {
    form.value.badges.splice(index, 1);
};

const addAIScore = () => {
    form.value.ai_recommendation_score.push({ key: '', value: '' });
};

const removeAIScore = (index) => {
    form.value.ai_recommendation_score.splice(index, 1);
};

const validateForm = () => {
    const errors = [];

    if (!form.value.name.trim()) {
        errors.push('Plan name is required');
    }

    if (!form.value.description.trim()) {
        errors.push('Description is required');
    }

    if (parseFloat(form.value.min_amount) < 0) {
        errors.push('Minimum amount cannot be negative');
    }

    if (parseFloat(form.value.max_amount) <= 0) {
        errors.push('Maximum amount must be greater than 0');
    }

    if (parseFloat(form.value.min_amount) >= parseFloat(form.value.max_amount)) {
        errors.push('Minimum amount must be less than maximum amount');
    }

    if (parseFloat(form.value.interest_rate) <= 0) {
        errors.push('Interest rate must be greater than 0');
    }

    if (parseInt(form.value.duration_days) <= 0) {
        errors.push('Duration must be greater than 0 days');
    }

    if (form.value.starts_at && form.value.ends_at) {
        if (new Date(form.value.starts_at) >= new Date(form.value.ends_at)) {
            errors.push('End date must be after start date');
        }
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

    // Add all basic form fields
    Object.keys(form.value).forEach(key => {
        if (['features', 'badges', 'ai_recommendation_score'].includes(key)) return;

        let value = form.value[key];

        // Handle boolean values
        if (typeof value === 'boolean') {
            value = value ? '1' : '0';
        }

        // Handle empty strings for optional fields
        if (value === '' && ['starts_at', 'ends_at'].includes(key)) {
            return; // Skip empty date fields
        }

        formData.append(key, value);
    });

    // Handle features
    if (form.value.features.length > 0) {
        const featuresArray = form.value.features
            .filter(f => f.text && f.text.trim())
            .map(f => f.text.trim());
        if (featuresArray.length > 0) {
            formData.append('features', JSON.stringify(featuresArray));
        }
    }

    // Handle badges
    if (form.value.badges.length > 0) {
        const badgesArray = form.value.badges
            .filter(b => b.text && b.text.trim())
            .map(b => b.text.trim());
        if (badgesArray.length > 0) {
            formData.append('badges', JSON.stringify(badgesArray));
        }
    }

    // Handle AI recommendation score
    if (form.value.ai_recommendation_score.length > 0) {
        const aiScoreObj = {};
        form.value.ai_recommendation_score.forEach(score => {
            if (score.key && score.value) {
                aiScoreObj[score.key] = score.value;
            }
        });
        if (Object.keys(aiScoreObj).length > 0) {
            formData.append('ai_recommendation_score', JSON.stringify(aiScoreObj));
        }
    }

    if (isEditing.value) {
        formData.append('_method', 'PUT');
    }

    const url = isEditing.value
        ? `/admin/investment-plans/${props.plan.id}`
        : '/admin/investment-plans';

    router.post(url, formData, {
        forceFormData: true,
        onSuccess: () => {
            setTimeout(() => {
                router.visit('/admin/investment-plans');
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
        :title="`${isEditing ? 'Edit' : 'Create'} Investment Plan`"
        page-section="Investments"
    >
        <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <div class="flex items-center gap-4 mb-4">
                    <Link
                        href="/admin/investment-plans"
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
                        Back to Plans
                    </Link>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ isEditing ? 'Edit Investment Plan' : 'Create Investment Plan' }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ isEditing ? 'Update investment plan configuration and settings' : 'Set up a new investment plan for users' }}
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
                                Plan Name *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="e.g., Gold Investment Plan"
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

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Risk Level *
                            </label>
                            <select
                                v-model="form.risk_level"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                                <option
                                    v-for="risk in risk_levels"
                                    :key="risk.value"
                                    :value="risk.value"
                                >
                                    {{ risk.label }}
                                </option>
                            </select>
                            <p
                                v-if="errors.risk_level"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.risk_level }}
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
                                placeholder="Describe the investment plan details and benefits"
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

                <!-- Investment Details -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Investment Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Minimum Amount *
                            </label>
                            <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">
                  {{ currencySymbol }}
                </span>
                                <input
                                    v-model="form.min_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0.00"
                                >
                            </div>
                            <p
                                v-if="errors.min_amount"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.min_amount }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Maximum Amount *
                            </label>
                            <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">
                  {{ currencySymbol }}
                </span>
                                <input
                                    v-model="form.max_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0.00"
                                >
                            </div>
                            <p
                                v-if="errors.max_amount"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.max_amount }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Interest Rate * (%)
                            </label>
                            <input
                                v-model="form.interest_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="0.00"
                            >
                            <p
                                v-if="errors.interest_rate"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.interest_rate }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Duration (Days) *
                            </label>
                            <input
                                v-model="form.duration_days"
                                type="number"
                                min="1"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="30"
                            >
                            <p
                                v-if="errors.duration_days"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.duration_days }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Interest Type *
                            </label>
                            <select
                                v-model="form.interest_type"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                                <option
                                    v-for="type in interest_types"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                            <p
                                v-if="errors.interest_type"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.interest_type }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Return Frequency *
                            </label>
                            <select
                                v-model="form.return_type"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                                <option
                                    v-for="type in return_types"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                            <p
                                v-if="errors.return_type"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.return_type }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Principal Return *
                            </label>
                            <select
                                v-model="form.principal_return"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                                <option
                                    v-for="option in principal_return_options"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <p
                                v-if="errors.principal_return"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.principal_return }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Success Rate (%)
                            </label>
                            <input
                                v-model="form.success_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="95.00"
                            >
                            <p
                                v-if="errors.success_rate"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.success_rate }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Advanced Options -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Advanced Options
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Auto-Reinvest Available
                            </label>
                            <div class="flex items-center h-8">
                                <button
                                    type="button"
                                    class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    :class="form.auto_reinvest_available ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                    @click="form.auto_reinvest_available = !form.auto_reinvest_available"
                                >
                                  <span
                                      class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform shadow-lg"
                                      :class="form.auto_reinvest_available ? 'translate-x-6' : 'translate-x-1'"
                                  />
                                </button>
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                  {{ form.auto_reinvest_available ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Auto-Reinvest Rate (%)
                            </label>
                            <input
                                v-model="form.auto_reinvest_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                :disabled="!form.auto_reinvest_available"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-slate-600 disabled:cursor-not-allowed"
                                placeholder="0.00"
                            >
                            <p
                                v-if="errors.auto_reinvest_rate"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.auto_reinvest_rate }}
                            </p>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Partial Withdrawal Allowed
                            </label>
                            <div class="flex items-center h-8">
                                <button
                                    type="button"
                                    class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    :class="form.partial_withdrawal_allowed ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                    @click="form.partial_withdrawal_allowed = !form.partial_withdrawal_allowed"
                                >
                                  <span
                                      class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform shadow-lg"
                                      :class="form.partial_withdrawal_allowed ? 'translate-x-6' : 'translate-x-1'"
                                  />
                                </button>
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                  {{ form.partial_withdrawal_allowed ? 'Allowed' : 'Not Allowed' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Early Withdrawal Fee (%)
                            </label>
                            <input
                                v-model="form.early_withdrawal_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                :disabled="!form.partial_withdrawal_allowed"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-slate-600 disabled:cursor-not-allowed"
                                placeholder="5.00"
                            >
                            <p
                                v-if="errors.early_withdrawal_fee"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.early_withdrawal_fee }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Maximum Investors (0 = unlimited)
                            </label>
                            <input
                                v-model="form.max_investors"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="100"
                            >
                            <p
                                v-if="errors.max_investors"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.max_investors }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Total Investment Cap (0 = unlimited)
                            </label>
                            <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">
                  {{ currencySymbol }}
                </span>
                                <input
                                    v-model="form.total_cap"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0.00"
                                >
                            </div>
                            <p
                                v-if="errors.total_cap"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.total_cap }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Plan Features -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Plan Features
                    </h3>

                    <div class="space-y-4">
                        <div
                            v-for="(feature, index) in form.features"
                            :key="index"
                            class="flex gap-4 items-start"
                        >
                            <div class="flex-1">
                                <input
                                    v-model="feature.text"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="e.g., Daily profit withdrawal"
                                >
                            </div>
                            <button
                                type="button"
                                class="px-3 py-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200"
                                @click="removeFeature(index)"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200"
                            @click="addFeature"
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
                            Add Feature
                        </button>
                    </div>
                </div>

                <!-- Plan Badges -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Plan Badges
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Add badges to highlight special features or promotions.
                    </p>

                    <div class="space-y-4">
                        <div
                            v-for="(badge, index) in form.badges"
                            :key="index"
                            class="flex gap-4 items-start"
                        >
                            <div class="flex-1">
                                <input
                                    v-model="badge.text"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="e.g., Popular, New, Limited Time"
                                >
                            </div>
                            <button
                                type="button"
                                class="px-3 py-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200"
                                @click="removeBadge(index)"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200"
                            @click="addBadge"
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
                            Add Badge
                        </button>
                    </div>
                </div>

                <!-- Display & Configuration -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Display & Configuration
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Color Scheme
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="form.color_scheme"
                                    type="color"
                                    class="h-10 w-16 border border-gray-300 dark:border-slate-600 rounded cursor-pointer"
                                >
                                <input
                                    v-model="form.color_scheme"
                                    type="text"
                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                    placeholder="#3b82f6"
                                >
                            </div>
                            <p
                                v-if="errors.color_scheme"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.color_scheme }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sort Order
                            </label>
                            <input
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                placeholder="0"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Lower numbers appear first
                            </p>
                            <p
                                v-if="errors.sort_order"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.sort_order }}
                            </p>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Featured Plan
                            </label>
                            <div class="flex items-center h-8">
                                <button
                                    type="button"
                                    class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    :class="form.featured ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                    @click="form.featured = !form.featured"
                                >
                  <span
                      class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform shadow-lg"
                      :class="form.featured ? 'translate-x-6' : 'translate-x-1'"
                  />
                                </button>
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ form.featured ? 'Featured' : 'Not Featured' }}
                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Recommendation Score -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        AI Recommendation Score
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Configure AI scoring parameters for this investment plan.
                    </p>

                    <div class="space-y-4">
                        <div
                            v-for="(score, index) in form.ai_recommendation_score"
                            :key="index"
                        >
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Parameter Name
                                    </label>
                                    <input
                                        v-model="score.key"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                        placeholder="e.g., stability_score"
                                    >
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Score Value
                                    </label>
                                    <input
                                        v-model="score.value"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                                        placeholder="e.g., 8.5"
                                    >
                                </div>
                                <div class="flex items-end">
                                    <button
                                        type="button"
                                        class="px-3 py-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200"
                                        @click="removeAIScore(index)"
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200"
                            @click="addAIScore"
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
                            Add AI Parameter
                        </button>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Plan Schedule
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Set start and end dates for this investment plan (optional).
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Start Date
                            </label>
                            <input
                                v-model="form.starts_at"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                            <p
                                v-if="errors.starts_at"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.starts_at }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                End Date
                            </label>
                            <input
                                v-model="form.ends_at"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            >
                            <p
                                v-if="errors.ends_at"
                                class="mt-1 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.ends_at }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <Link
                        href="/admin/investment-plans"
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
                        {{ processing ? 'Saving...' : (isEditing ? 'Update Plan' : 'Create Plan') }}
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

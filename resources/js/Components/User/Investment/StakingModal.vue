<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click="$emit('close')"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="modalTitleId"
        >
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="show"
                    class="bg-gradient-to-br from-slate-900/95 to-slate-800/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl max-w-md w-full"
                    @click.stop
                >
                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-700/50">
                        <div class="flex items-center justify-between gap-3">
                            <h3 :id="modalTitleId" class="text-lg sm:text-xl font-bold text-white">
                                {{ t('stake') }} {{ pool?.token_symbol }}
                            </h3>
                            <button
                                @click="$emit('close')"
                                class="p-2 transition-all duration-200 text-slate-300 hover:text-white rounded-lg hover:bg-slate-700/80 focus:outline-none focus:ring-2 focus:ring-white/20 flex-shrink-0"
                                :aria-label="t('closeModal')"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="handleSubmit" class="p-5 sm:p-6 space-y-5">
                        <div>
                            <label for="staking-amount" class="block text-sm font-medium text-white mb-2">
                                {{ t('stakeAmount') }}
                            </label>
                            <input
                                id="staking-amount"
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                :min="pool?.min_stake_amount"
                                class="w-full px-4 py-3 border border-slate-700/50 rounded-lg text-sm font-medium text-white bg-slate-800/50 transition-all focus:border-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.3);`"
                                :placeholder="t('enterAmount')"
                                :aria-describedby="amountHintId"
                                required
                            >
                            <p :id="amountHintId" class="text-xs font-medium text-slate-400 mt-1.5">
                                {{ t('minStake') }}: {{ currencySymbol }}{{ formatCurrency(pool?.min_stake_amount) }}
                            </p>
                            <p v-if="errors.amount" class="mt-1.5 text-sm font-medium text-red-400">
                                {{ errors.amount }}
                            </p>
                        </div>

                        <div v-if="pool?.auto_compound" class="flex items-start gap-2.5">
                            <input
                                :id="autoCompoundCheckboxId"
                                v-model="form.auto_compound"
                                type="checkbox"
                                class="mt-1 w-4 h-4 rounded focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 cursor-pointer"
                                :style="`color: ${themeColors.primary}; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                :aria-label="t('enableAutoCompound')"
                            >
                            <label :for="autoCompoundCheckboxId" class="flex-1 cursor-pointer">
                                <span class="text-white font-semibold text-sm block mb-0.5">{{ t('enableAutoCompound') }}</span>
                                <span class="text-xs font-medium text-slate-400">{{ t('autoCompoundDescription') }}</span>
                            </label>
                        </div>

                        <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('stakingSummary') }}
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center gap-3">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('apyRate') }}:</span>
                                    <span class="font-semibold text-base" :style="{ color: themeColors.primary }">{{ pool?.apy_rate }}%</span>
                                </div>
                                <div class="flex justify-between items-center gap-3">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('lockPeriod') }}:</span>
                                    <span class="text-white font-semibold text-sm">
                                        {{ pool?.lock_days === 0 ? t('flexible') : `${pool?.lock_days} ${t('days')}` }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center gap-3">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('estimatedAnnualRewards') }}:</span>
                                    <span class="font-semibold text-base" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ calculateAnnualRewards() }}</span>
                                </div>
                                <div v-if="pool?.lock_days > 0" class="flex justify-between items-center gap-3">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('unlockDate') }}:</span>
                                    <span class="text-white font-semibold text-sm text-right">{{ calculateUnlockDate() }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="errors.general" class="bg-red-500/10 border border-red-500/30 rounded-lg p-3">
                            <div class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm font-medium text-red-400">{{ errors.general }}</p>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="localProcessing || !isValidAmount"
                            class="w-full py-3 px-5 rounded-lg font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 shadow-lg hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                            :style="`background: ${localProcessing || !isValidAmount ? '#1e293b' : `linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})`}; color: ${localProcessing || !isValidAmount ? '#64748b' : '#0f172a'}; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                            :aria-label="localProcessing ? t('processingStaking') : t('confirmStaking')"
                        >
                            <span v-if="localProcessing" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                {{ t('processing') }}...
                            </span>
                            <span v-else>{{ t('confirmStaking') }}</span>
                        </button>

                        <p v-if="!isValidAmount && form.amount" class="text-sm font-medium text-red-400 text-center">
                            {{ t('amountBelowMinimum') }}
                        </p>
                    </form>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    show: { type: Boolean, default: false },
    pool: { type: Object, default: null },
    form: { type: Object, required: true },
    processing: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'submit'])

const page = usePage()
const { t } = useTranslation()
const { success, error } = useToast()

const localProcessing = ref(false)
const errors = ref({
    amount: '',
    general: ''
})

const primaryColor = computed(() => page.props.primaryColor || '#86efac')
const currencySymbol = computed(() => page.props.currencySymbol || '$')

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') return { r: 134, g: 239, b: 172 }
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 }
}

const rgbToHex = (r, g, b) => "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex)
    return rgbToHex(
        Math.min(255, Math.max(0, rgb.r + amount)),
        Math.min(255, Math.max(0, rgb.g + amount)),
        Math.min(255, Math.max(0, rgb.b + amount))
    )
}

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryLight: adjustColor(primaryColor.value, 40),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}))

const modalTitleId = computed(() => `staking-modal-title-${props.pool?.id || 'default'}`)
const amountHintId = computed(() => `amount-hint-${props.pool?.id || 'default'}`)
const autoCompoundCheckboxId = computed(() => `auto-compound-${props.pool?.id || 'default'}`)

const isValidAmount = computed(() => {
    if (!props.form.amount || !props.pool) return false
    const amount = parseFloat(props.form.amount)
    return amount >= props.pool.min_stake_amount
})

const validateForm = () => {
    errors.value = { amount: '', general: '' }

    if (!props.form.amount) {
        errors.value.amount = t('amountRequired')
        return false
    }

    if (!isValidAmount.value) {
        errors.value.amount = t('amountBelowMinimum')
        return false
    }

    return true
}

const handleSubmit = () => {
    if (!validateForm()) return

    localProcessing.value = true
    errors.value = { amount: '', general: '' }

    router.post('/user/stake', {
        pool_id: props.pool.id,
        amount: parseFloat(props.form.amount),
        auto_compound: props.form.auto_compound || false
    }, {
        preserveScroll: true,
        onSuccess: () => {
            localProcessing.value = false
            success(t('stakingSuccessful'))
            emit('close')
            props.form.amount = ''
            props.form.auto_compound = false
        },
        onError: (responseErrors) => {
            localProcessing.value = false

            if (responseErrors.amount) {
                errors.value.amount = responseErrors.amount
                error(responseErrors.amount)
            }

            if (responseErrors.pool_id) {
                errors.value.general = responseErrors.pool_id
                error(responseErrors.pool_id)
            }

            if (responseErrors.error) {
                errors.value.general = responseErrors.error
                error(responseErrors.error)
            }

            if (responseErrors.message) {
                errors.value.general = responseErrors.message
                error(responseErrors.message)
            }

            if (!errors.value.amount && !errors.value.general) {
                errors.value.general = t('stakingFailed')
                error(t('stakingFailed'))
            }
        },
        onFinish: () => {
            localProcessing.value = false
        }
    })
}

const calculateAnnualRewards = () => {
    if (!props.pool || !props.form.amount) return '0.00'
    const annual = (parseFloat(props.form.amount) * props.pool.apy_rate) / 100
    return formatCurrency(annual)
}

const calculateUnlockDate = () => {
    if (!props.pool || props.pool.lock_days === 0) return t('notApplicable')
    const unlockDate = new Date()
    unlockDate.setDate(unlockDate.getDate() + props.pool.lock_days)
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    }).format(unlockDate)
}

const formatCurrency = (amount) => {
    const value = parseFloat(amount) || 0
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value)
}

watch(() => props.show, (newVal) => {
    if (!newVal) {
        errors.value = { amount: '', general: '' }
    }
})
</script>

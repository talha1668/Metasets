<template>
    <section class="flex justify-center animate-fade-in-up animation-delay-300">
        <div class="bg-gradient-to-br from-slate-900/70 to-slate-800/50 backdrop-blur-xl border border-slate-700/30 rounded-xl p-1.5 shadow-2xl">
            <div class="flex items-center gap-1.5" role="tablist" :aria-label="t('investmentStakingToggle')">
                <button
                    @click="$emit('update:activeType', 'investment')"
                    :class="['px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900', activeType === 'investment' ? 'text-slate-900 shadow-lg' : 'text-slate-300 hover:text-white hover:bg-slate-800/30']"
                    :style="activeType === 'investment' ? `background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); box-shadow: 0 0 0 2px rgba(${themeColors.primaryRgb}, 0.2); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : ''"
                    :aria-pressed="activeType === 'investment'"
                    :aria-label="t('switchToInvestmentPlans')"
                    role="tab"
                    :aria-selected="activeType === 'investment'"
                >
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        {{ t('investmentPlans') }}
                    </span>
                </button>

                <button
                    @click="$emit('update:activeType', 'staking')"
                    :class="['px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900', activeType === 'staking' ? 'text-slate-900 shadow-lg' : 'text-slate-300 hover:text-white hover:bg-slate-800/30']"
                    :style="activeType === 'staking' ? `background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); box-shadow: 0 0 0 2px rgba(${themeColors.primaryRgb}, 0.2); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : ''"
                    :aria-pressed="activeType === 'staking'"
                    :aria-label="t('switchToStakingPools')"
                    role="tab"
                    :aria-selected="activeType === 'staking'"
                >
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                        </svg>
                        {{ t('stakingPools') }}
                    </span>
                </button>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'

defineProps({
    activeType: {
        type: String,
        required: true,
        validator: (value) => ['investment', 'staking'].includes(value)
    }
})

defineEmits(['update:activeType'])

const page = usePage()
const { t } = useTranslation()

const primaryColor = computed(() => page.props.primaryColor || '#86efac')

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
</script>

<template>
    <div
        class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2 rounded-full backdrop-blur-xl border animate-fade-in-up"
        :style="trustBadgeStyle"
    >
        <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full flex items-center justify-center" :style="badgeIconStyle">
            <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"/>
            </svg>
        </div>

        <span class="text-white font-semibold text-xs sm:text-sm tracking-wide">
            {{ t('trustedBy') }} {{ stats.partners }}+ {{ t('organizations') }}
        </span>

        <div class="w-1.5 h-1.5 rounded-full animate-pulse" :style="{ backgroundColor: themeColors.primary }"></div>
    </div>
</template>

<script setup>
import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import {useTranslation} from '@/composables/useTranslation'

defineProps({
    stats: {
        type: Object,
        required: true
    }
})

const page = usePage()
const {t} = useTranslation()

const primaryColor = computed(() => page.props.primaryColor || '#86efac')
const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') {
        return {r: 134, g: 239, b: 172}
    }
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : {r: 134, g: 239, b: 172}
}

const rgbToHex = (r, g, b) => {
    return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)
}

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex)
    const newR = Math.min(255, Math.max(0, rgb.r + amount))
    const newG = Math.min(255, Math.max(0, rgb.g + amount))
    const newB = Math.min(255, Math.max(0, rgb.b + amount))
    return rgbToHex(newR, newG, newB)
}

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryLight: adjustColor(primaryColor.value, 40),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}))

const trustBadgeStyle = computed(() => ({
    background: `rgba(${themeColors.value.primaryRgb}, 0.1)`,
    borderColor: `rgba(${themeColors.value.primaryRgb}, 0.3)`,
    backdropFilter: 'blur(20px)'
}))

const badgeIconStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primaryDark} 0%, ${themeColors.value.primary} 100%)`
}))
</script>

<style scoped>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out forwards;
}

.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in-up,
    .animate-pulse {
        animation: none !important;
    }
}
</style>

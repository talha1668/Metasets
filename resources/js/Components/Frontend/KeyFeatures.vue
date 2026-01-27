<template>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 lg:gap-6 animate-fade-in animation-delay-1800">
        <div
            v-for="feature in features"
            :key="feature.key"
            class="text-center sm:text-left p-3 sm:p-4 rounded-lg sm:rounded-xl backdrop-blur-sm border transition-all duration-300 group hover:scale-[1.02]"
            :class="feature.colSpan"
            :style="featureCardStyle">
            <div
                class="w-8 h-8 sm:w-10 sm:h-10 mx-auto sm:mx-0 mb-2 sm:mb-3 rounded-md sm:rounded-lg flex items-center justify-center transition-all group-hover:scale-110"
                :style="featureIconStyle">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path :d="feature.iconPath"/>
                </svg>
            </div>
            <h3 class="text-white font-bold text-sm sm:text-base mb-1.5">{{ t(feature.titleKey) }}</h3>
            <p class="text-white/70 text-xs sm:text-sm leading-relaxed font-normal">{{ t(feature.descKey) }}</p>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import {useTranslation} from '@/composables/useTranslation'

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

const featureIconStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`
}))

const featureCardStyle = computed(() => ({
    borderColor: 'rgba(255, 255, 255, 0.1)',
    background: `rgba(${themeColors.value.primaryRgb}, 0.02)`
}))

const features = [
    {
        key: 'assetSwapping',
        titleKey: 'featureAssetSwappingTitle',
        descKey: 'featureAssetSwappingDesc',
        iconPath: 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
        colSpan: ''
    },
    {
        key: 'sharedOwnership',
        titleKey: 'featureSharedOwnershipTitle',
        descKey: 'featureSharedOwnershipDesc',
        iconPath: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20a3 3 0 01-3-3v-2a3 3 0 013-3h3a3 3 0 013 3v2a3 3 0 01-3 3m0 0H7m0 0v-2a3 3 0 013-3v0a3 3 0 013 3v2',
        colSpan: ''
    },
    {
        key: 'carbonIntegrity',
        titleKey: 'featureCarbonIntegrityTitle',
        descKey: 'featureCarbonIntegrityDesc',
        iconPath: 'M9 12a3 3 0 11-6 0 3 3 0 016 0zM21 12a3 3 0 11-6 0 3 3 0 016 0zM12 21a9 9 0 100-18 9 9 0 000 18z',
        colSpan: 'sm:col-span-3 lg:col-span-1'
    }
]
</script>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.8s ease-out forwards;
    opacity: 0;
}

.animation-delay-1800 {
    animation-delay: 1.8s;
}

.backdrop-blur-sm {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.group:hover {
    border-color: rgba(255, 255, 255, 0.2) !important;
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }

    .transition-all {
        transition: none !important;
    }
}
</style>

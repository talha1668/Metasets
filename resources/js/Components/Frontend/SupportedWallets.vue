<template>
    <div class="animate-fade-in animation-delay-1500">
        <p class="text-white/60 text-xs sm:text-sm font-semibold mb-2 sm:mb-3 uppercase tracking-wide">
            {{ t('supportedWeb3Wallets') }}</p>
        <div class="grid grid-cols-4 sm:flex sm:items-center gap-3 sm:gap-4">
            <div v-for="wallet in wallets" :key="wallet.name"
                 class="group cursor-pointer flex flex-col items-center"
                 @click="$emit('connectWallet', wallet)">
                <div
                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-md sm:rounded-lg border backdrop-blur-sm p-1.5 sm:p-2 transition-all duration-300 group-hover:scale-110 group-active:scale-95"
                    :style="walletIconStyle">
                    <img :src="wallet.icon" :alt="wallet.name"
                         class="w-full h-full object-contain opacity-70 group-hover:opacity-100 transition-opacity">
                </div>
                <span
                    class="text-[10px] sm:text-xs text-white/60 group-hover:text-white/90 mt-1.5 transition-colors font-medium text-center">{{
                        wallet.name
                    }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import {useTranslation} from '@/composables/useTranslation'

defineProps({
    wallets: {
        type: Array,
        required: true
    }
})

defineEmits(['connectWallet'])

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

const themeColors = computed(() => {
    const rgb = hexToRgb(primaryColor.value)
    return {
        primary: primaryColor.value,
        primaryRgb: `${rgb.r}, ${rgb.g}, ${rgb.b}`
    }
})

const walletIconStyle = computed(() => ({
    borderColor: 'rgba(255, 255, 255, 0.2)',
    background: `rgba(${themeColors.value.primaryRgb}, 0.05)`
}))
</script>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
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

.animation-delay-1500 {
    animation-delay: 1.5s;
}

.backdrop-blur-sm {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.group:hover .w-12,
.group:hover .w-14 {
    border-color: rgba(255, 255, 255, 0.4);
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }

    .transition-all,
    .transition-opacity,
    .transition-colors {
        transition: none !important;
    }
}
</style>

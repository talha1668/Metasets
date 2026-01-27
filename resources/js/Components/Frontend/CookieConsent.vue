<template>
    <Transition name="slide-up">
        <div v-if="showBanner" class="fixed bottom-0 left-0 right-0 z-50 p-4 sm:p-6 backdrop-blur-2xl border-t"
             :style="bannerStyle">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                 :style="iconStyle">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-white font-bold text-base sm:text-lg mb-2">{{
                                        t('weValueYourPrivacy')
                                    }}</h3>
                                <p class="text-white/70 text-sm sm:text-base leading-relaxed max-w-2xl">
                                    {{ t('cookieBannerDescription') }}
                                    <Link href="/cookies"
                                          class="text-white underline hover:text-white/80 transition-colors font-medium ml-1">
                                        {{ t('learnMore') }}
                                    </Link>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <button
                            @click="acceptCookies(false)"
                            class="px-6 py-3 rounded-xl border border-white/20 hover:border-white/40 text-white font-semibold text-sm sm:text-base transition-all hover:scale-105 backdrop-blur-sm"
                            :style="secondaryButtonStyle">
                            {{ t('essentialOnly') }}
                        </button>
                        <button
                            @click="acceptCookies(true)"
                            class="px-6 py-3 rounded-xl text-white font-bold text-sm sm:text-base transition-all hover:scale-105 shadow-lg"
                            :style="primaryButtonStyle">
                            {{ t('acceptAllCookies') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue'
import {Link, usePage} from '@inertiajs/vue3'
import {useTranslation} from "@/composables/useTranslation.js";

const {t} = useTranslation()
const page = usePage()
const showBanner = ref(false)
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

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex)
    const newR = Math.min(255, Math.max(0, rgb.r + amount))
    const newG = Math.min(255, Math.max(0, rgb.g + amount))
    const newB = Math.min(255, Math.max(0, rgb.b + amount))
    return "#" + ((1 << 24) + (newR << 16) + (newG << 8) + newB).toString(16).slice(1)
}

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}))

const bannerStyle = computed(() => ({
    backgroundColor: 'rgba(15, 23, 42, 0.98)',
    borderColor: 'rgba(255, 255, 255, 0.2)'
}))

const iconStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`
}))

const primaryButtonStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`,
    boxShadow: `0 10px 30px rgba(${themeColors.value.primaryRgb}, 0.4)`
}))

const secondaryButtonStyle = computed(() => ({
    backgroundColor: 'rgba(255, 255, 255, 0.05)'
}))

const acceptCookies = (acceptAll) => {
    const consent = {
        essential: true,
        analytics: acceptAll,
        marketing: acceptAll,
        preferences: acceptAll,
        timestamp: new Date().toISOString(),
        version: '1.0'
    }

    localStorage.setItem('cookieConsent', JSON.stringify(consent))
    showBanner.value = false

    if (acceptAll && window.gtag) {
        window.gtag('consent', 'update', {
            analytics_storage: 'granted',
            ad_storage: 'granted',
            functionality_storage: 'granted',
            personalization_storage: 'granted'
        })
    }

    window.dispatchEvent(new CustomEvent('cookieConsentUpdated', {
        detail: consent
    }))

    console.log('Cookie consent saved:', consent)
}

const checkConsent = () => {
    const consent = localStorage.getItem('cookieConsent')

    if (!consent) {
        setTimeout(() => {
            showBanner.value = true
        }, 1000)
    } else {
        const savedConsent = JSON.parse(consent)
        console.log('Existing cookie consent found:', savedConsent)

        if (savedConsent.analytics && window.gtag) {
            window.gtag('consent', 'update', {
                analytics_storage: 'granted',
                ad_storage: savedConsent.marketing ? 'granted' : 'denied'
            })
        }
    }
}

defineExpose({
    showBanner,
    openSettings: () => {
        showBanner.value = true
    }
})

onMounted(() => {
    checkConsent()
})
</script>

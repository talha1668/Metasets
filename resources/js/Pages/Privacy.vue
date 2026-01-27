<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
        <header class="border-b backdrop-blur-xl sticky top-0 z-10" :style="headerStyle">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="iconStyle">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-white font-bold text-lg">{{ appName }}</span>
                </Link>
                <Link href="/" class="text-white/70 hover:text-white transition-colors text-sm font-medium">
                    {{ t('backToHome') }}
                </Link>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
            <div class="mb-8">
                <h1 class="text-4xl sm:text-5xl font-black text-white mb-4">{{ t('privacyPolicy') }}</h1>
                <p class="text-white/60 text-base">{{ t('lastUpdated') }}: {{ formattedDate }}</p>
            </div>

            <div class="space-y-8 text-white/70 leading-relaxed">
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">1. {{ t('informationWeCollect') }}</h2>
                    <p class="mb-4">{{ t('informationWeCollectText') }}</p>
                    <ul class="list-disc list-inside space-y-2 ml-4">
                        <li>{{ t('walletAddresses') }}</li>
                        <li>{{ t('emailAddress') }}</li>
                        <li>{{ t('usageData') }}</li>
                        <li>{{ t('deviceInfo') }}</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">2. {{ t('howWeUseInfo') }}</h2>
                    <p class="mb-4">{{ t('howWeUseInfoText') }}</p>
                    <ul class="list-disc list-inside space-y-2 ml-4">
                        <li>{{ t('provideServices') }}</li>
                        <li>{{ t('processTransactions') }}</li>
                        <li>{{ t('sendNotices') }}</li>
                        <li>{{ t('respondQuestions') }}</li>
                        <li>{{ t('protectFraud') }}</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">3. {{ t('informationSharing') }}</h2>
                    <p>{{ t('informationSharingText') }}</p>
                    <ul class="list-disc list-inside space-y-2 ml-4 mt-4">
                        <li>{{ t('withConsent') }}</li>
                        <li>{{ t('withServiceProviders') }}</li>
                        <li>{{ t('complyLegal') }}</li>
                        <li>{{ t('protectRights') }}</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">4. {{ t('dataSecurity') }}</h2>
                    <p>{{ t('dataSecurityText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">5. {{ t('yourRights') }}</h2>
                    <p class="mb-4">{{ t('yourRightsText') }}</p>
                    <ul class="list-disc list-inside space-y-2 ml-4">
                        <li>{{ t('accessInfo') }}</li>
                        <li>{{ t('correctData') }}</li>
                        <li>{{ t('requestDeletion') }}</li>
                        <li>{{ t('objectProcessing') }}</li>
                        <li>{{ t('dataPortability') }}</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">6. {{ t('cookiesTracking') }}</h2>
                    <p>
                        {{ t('cookiesTrackingText') }}
                        <Link href="/cookies" class="text-green-400 hover:text-green-300 underline font-medium">{{ t('cookiePolicy') }}</Link>.
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">7. {{ t('childrensPrivacy') }}</h2>
                    <p>{{ t('childrensPrivacyText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">8. {{ t('changesToPolicy') }}</h2>
                    <p>{{ t('changesToPolicyText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">9. {{ t('contactUs') }}</h2>
                    <p>{{ t('contactUsText') }}</p>
                    <div class="mt-4 p-4 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-white font-medium">{{ t('email') }}: <a href="mailto:privacy@example.com" class="text-green-400 hover:text-green-300">privacy@example.com</a></p>
                    </div>
                </section>
            </div>
        </main>

        <Footer />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'
import Footer from "@/Components/Frontend/Footer.vue";

const page = usePage()
const { t } = useTranslation()

const appName = computed(() => page.props.appName || 'Web3 Finance')
const primaryColor = computed(() => page.props.primaryColor || '#86efac')
const formattedDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') return { r: 134, g: 239, b: 172 }
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 }
}

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex)
    return "#" + ((1 << 24) + (Math.min(255, Math.max(0, rgb.r + amount)) << 16) + (Math.min(255, Math.max(0, rgb.g + amount)) << 8) + Math.min(255, Math.max(0, rgb.b + amount))).toString(16).slice(1)
}

const iconStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`
}))



const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20)
}))

const headerStyle = computed(() => ({
    backgroundColor: 'rgba(15, 23, 42, 0.8)',
    borderColor: 'rgba(255, 255, 255, 0.1)'
}))
</script>

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
                <h1 class="text-4xl sm:text-5xl font-black text-white mb-4">{{ t('termsOfService') }}</h1>
                <p class="text-white/60 text-base">{{ t('lastUpdated') }}: {{ formattedDate }}</p>
            </div>

            <div class="space-y-8 text-white/70 leading-relaxed">
                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">1. {{ t('acceptanceOfTerms') }}</h2>
                    <p>{{ t('acceptanceOfTermsText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">2. {{ t('descriptionOfService') }}</h2>
                    <p>{{ t('descriptionOfServiceText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">3. {{ t('userResponsibilities') }}</h2>
                    <p class="mb-4">{{ t('userResponsibilitiesText') }}</p>
                    <ul class="list-disc list-inside space-y-2 ml-4">
                        <li>{{ t('provideAccurateInfo') }}</li>
                        <li>{{ t('maintainSecurity') }}</li>
                        <li>{{ t('complyWithLaws') }}</li>
                        <li>{{ t('noFraudulent') }}</li>
                        <li>{{ t('noUnauthorizedAccess') }}</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">4. {{ t('walletConnection') }}</h2>
                    <p>{{ t('walletConnectionText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">5. {{ t('financialRisks') }}</h2>
                    <p class="mb-4">{{ t('financialRisksText') }}</p>
                    <ul class="list-disc list-inside space-y-2 ml-4">
                        <li>{{ t('marketVolatility') }}</li>
                        <li>{{ t('smartContractVulnerabilities') }}</li>
                        <li>{{ t('regulatoryChanges') }}</li>
                        <li>{{ t('technologyFailures') }}</li>
                    </ul>
                    <p class="mt-4 font-semibold text-white">{{ t('acknowledgeRisks') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">6. {{ t('feesAndPayments') }}</h2>
                    <p>{{ t('feesAndPaymentsText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">7. {{ t('intellectualProperty') }}</h2>
                    <p>{{ t('intellectualPropertyText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">8. {{ t('limitationOfLiability') }}</h2>
                    <p>{{ t('limitationOfLiabilityText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">9. {{ t('termination') }}</h2>
                    <p>{{ t('terminationText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">10. {{ t('changesToTerms') }}</h2>
                    <p>{{ t('changesToTermsText') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-white mb-4">11. {{ t('contactInformation') }}</h2>
                    <p>{{ t('contactInformationText') }}</p>
                    <div class="mt-4 p-4 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-white font-medium">{{ t('email') }}: <a href="mailto:legal@example.com" class="text-green-400 hover:text-green-300">legal@example.com</a></p>
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
const formattedDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })

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

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex)
    return "#" + ((1 << 24) + (Math.min(255, Math.max(0, rgb.r + amount)) << 16) + (Math.min(255, Math.max(0, rgb.g + amount)) << 8) + Math.min(255, Math.max(0, rgb.b + amount))).toString(16).slice(1)
}

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20)
}))

const iconStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`
}))

const headerStyle = computed(() => ({
    backgroundColor: 'rgba(15, 23, 42, 0.8)',
    borderColor: 'rgba(255, 255, 255, 0.1)'
}))
</script>

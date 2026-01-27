<template>
    <section
        class="relative min-h-screen flex items-center justify-center overflow-hidden pt-12 pb-6 px-4 sm:pt-16 sm:pb-10 lg:px-6"
    >
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 xl:grid-cols-12 gap-6 lg:gap-10 xl:gap-16 items-center">
                <div class="lg:col-span-7 xl:col-span-7 space-y-4 sm:space-y-6 lg:space-y-8">
                    <TrustBadge :stats="animatedStats"/>
                    <div class="space-y-3 sm:space-y-4 lg:space-y-6">
                        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-black leading-[0.85] tracking-tighter">
                            <span class="text-white block animate-slide-up">
                                {{ t('heroProfessional') }}
                            </span>
                            <span
                                class="block animate-slide-up animation-delay-300 font-black"
                                :style="{ background: primaryGradient, WebkitBackgroundClip: 'text', backgroundClip: 'text', color: 'transparent' }"
                            >
                                {{ t('heroWeb3Finance') }}
                            </span>
                            <span
                                class="text-white/90 text-xl sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl font-semibold block animate-slide-up animation-delay-600">
                                {{ t('heroBeyondBoundaries') }}
                            </span>
                        </h1>

                        <p class="text-sm sm:text-base lg:text-lg leading-relaxed max-w-xl text-white/80 font-light animate-fade-in animation-delay-900">
                            {{ t('heroTagline') }}
                        </p>
                    </div>

                    <WalletConnection
                        :wallet-connected="walletConnected"
                        :wallet-address="walletAddress"
                        :wallet-balance="walletBalance"
                        :connecting="connecting"
                        :copied="copied"
                        @connect-wallet="triggerWalletConnection"
                        @copy-address="copyAddress"
                    />

                    <SupportedWallets
                        :wallets="supportedWebWallets"
                        @connect-wallet="connectSpecificWallet"
                    />

                    <KeyFeatures/>
                </div>

                <div
                    class="lg:col-span-5 xl:col-span-5 relative h-[400px] sm:h-[480px] lg:h-[560px] xl:h-[640px] mt-8 lg:mt-0">
                    <DashboardPreview
                        :portfolio-value="animatedPortfolioValue"
                        :daily-yield="animatedDailyYield"
                        :portfolio-growth="portfolioGrowth"
                        :trend-percentage="trendPercentage"
                        :last-update="lastUpdateTime"
                        :staking-rewards="stakingRewards"
                        :stats="animatedStats"
                    />
                </div>
            </div>
        </div>

        <WalletModal
            :show="showWalletModal"
            @close="closeWalletModal"
            @success="handleWalletSuccess"
        />
    </section>
</template>

<script setup>
import {computed, onMounted, onUnmounted, reactive, ref} from 'vue'
import {usePage} from '@inertiajs/vue3'
import {useTranslation} from '@/composables/useTranslation'
import TrustBadge from "@/Components/Frontend/TrustBadge.vue"
import WalletConnection from "@/Components/Frontend/WalletConnection.vue"
import KeyFeatures from "@/Components/Frontend/KeyFeatures.vue"
import WalletModal from "@/Components/Frontend/WalletModal.vue"
import DashboardPreview from "@/Components/Frontend/DashboardPreview.vue"
import SupportedWallets from "@/Components/Frontend/SupportedWallets.vue"

const props = defineProps({
    cryptoData: {
        type: Array,
        default: () => []
    },
})

const page = usePage()
const {t} = useTranslation()
const authUser = computed(() => page.props.auth?.user || null)
const isAuthenticated = computed(() => !!authUser.value)

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

const primaryGradient = computed(() =>
    `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`
)

const walletConnected = ref(authUser.value?.wallet_connected || false)
const walletAddress = ref(authUser.value?.wallet_address || '')
const walletBalance = ref('0.00')
const showWalletModal = ref(false)
const connecting = ref(false)
const copied = ref(false)

const portfolioValue = ref(247500)
const dailyYield = ref(1230)
const portfolioGrowth = ref(12.5)
const trendPercentage = ref(24.7)
const lastUpdateTime = ref(2)

const animatedPortfolioValue = ref(0)
const animatedDailyYield = ref(0)

const stakingRewards = reactive({
    amount: '2.847',
    apy: '4.2'
})

const animatedStats = reactive({
    tvl: 0,
    users: 0,
    transactions: 0,
    partners: 0
})

const supportedWebWallets = [
    {
        name: 'MetaMask',
        icon: '/assets/metamask.svg'
    },
]

const connectSpecificWallet = async (wallet) => {
    if (isAuthenticated.value) {
        return
    }
    showWalletModal.value = true
}

const triggerWalletConnection = () => {
    if (!isAuthenticated.value) {
        showWalletModal.value = true
    }
}

const closeWalletModal = () => {
    showWalletModal.value = false
}

const handleWalletSuccess = (user) => {
    walletConnected.value = true
    walletAddress.value = user.wallet_address
    showWalletModal.value = false
    fetchWalletBalance(user.wallet_address)
    startRealTimeUpdates()
}

const fetchWalletBalance = async (address) => {
    try {
        if (window.ethereum) {
            const balance = await window.ethereum.request({
                method: 'eth_getBalance',
                params: [address, 'latest']
            })
            const ethBalance = parseInt(balance, 16) / Math.pow(10, 18)
            walletBalance.value = ethBalance.toFixed(4)
        }
    } catch (err) {
        console.error('Error fetching balance:', err)
        walletBalance.value = '0.00'
    }
}

const copyAddress = async () => {
    try {
        await navigator.clipboard.writeText(walletAddress.value)
        copied.value = true
        setTimeout(() => {
            copied.value = false
        }, 2000)
    } catch (err) {
        console.error('Failed to copy address:', err)
    }
}

const animateCounters = () => {
    const targets = {
        tvl: 1250,
        users: 47,
        transactions: 892,
        partners: 150
    }
    const duration = 3000
    const steps = 100
    const stepTime = duration / steps

    Object.keys(targets).forEach(key => {
        const target = targets[key]
        const increment = target / steps
        let current = 0

        const timer = setInterval(() => {
            current += increment
            if (current >= target) {
                animatedStats[key] = target
                clearInterval(timer)
            } else {
                animatedStats[key] = Math.floor(current * 10) / 10
            }
        }, stepTime)
    })
}

const animatePortfolioValues = () => {
    const portfolioTarget = portfolioValue.value
    const yieldTarget = dailyYield.value
    const duration = 2000
    const steps = 60
    const stepTime = duration / steps

    let portfolioCurrent = 0
    let yieldCurrent = 0
    const portfolioIncrement = portfolioTarget / steps
    const yieldIncrement = yieldTarget / steps

    const timer = setInterval(() => {
        portfolioCurrent += portfolioIncrement
        yieldCurrent += yieldIncrement

        if (portfolioCurrent >= portfolioTarget) {
            animatedPortfolioValue.value = portfolioTarget
            animatedDailyYield.value = yieldTarget
            clearInterval(timer)
        } else {
            animatedPortfolioValue.value = Math.floor(portfolioCurrent)
            animatedDailyYield.value = Math.floor(yieldCurrent)
        }
    }, stepTime)
}

const startRealTimeUpdates = () => {
    return setInterval(() => {
        const change = (Math.random() - 0.5) * 100
        portfolioValue.value = Math.max(100000, portfolioValue.value + change)
        animatedPortfolioValue.value = portfolioValue.value

        const yieldChange = (Math.random() - 0.5) * 50
        dailyYield.value = Math.max(500, dailyYield.value + yieldChange)
        animatedDailyYield.value = dailyYield.value

        portfolioGrowth.value = (Math.random() * 20 + 5).toFixed(1)
        trendPercentage.value = (Math.random() * 30 + 10).toFixed(1)
        lastUpdateTime.value = Math.floor(Math.random() * 10) + 1
    }, 5000)
}

let updateIntervalId = null
const checkExistingAuth = () => {
    if (authUser.value && authUser.value.wallet_address) {
        walletConnected.value = true
        walletAddress.value = authUser.value.wallet_address
        fetchWalletBalance(authUser.value.wallet_address)
        updateIntervalId = startRealTimeUpdates()
    }
}

onMounted(() => {
    checkExistingAuth()
    setTimeout(() => {
        animateCounters()
        animatePortfolioValues()
    }, 1000)

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up')
            }
        })
    }, observerOptions)

    document.querySelectorAll('[class*="animate-"]').forEach(el => {
        observer.observe(el)
    })
})

onUnmounted(() => {
    if (updateIntervalId) {
        clearInterval(updateIntervalId)
    }
})
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

@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out forwards;
}

.animate-slide-up {
    animation: slide-up 0.8s ease-out forwards;
    letter-spacing: inherit;
}

.animate-fade-in {
    animation: fade-in 0.8s ease-out forwards;
}

.animation-delay-300 {
    animation-delay: 0.3s;
}

.animation-delay-600 {
    animation-delay: 0.6s;
}

.animation-delay-900 {
    animation-delay: 0.9s;
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in-up,
    .animate-slide-up,
    .animate-fade-in {
        animation: none !important;
    }
}
</style>

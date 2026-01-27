<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white relative overflow-hidden flex flex-col">
        <div class="absolute inset-0">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute inset-0" :style="`background-image: radial-gradient(circle at 1px 1px, rgba(${themeColors.primaryRgb}, 0.05) 1px, transparent 0); background-size: 24px 24px;`"></div>
            </div>
            <div class="absolute top-8 right-8 w-36 h-36 rounded-full blur-3xl animate-pulse-slow" :style="`background: radial-gradient(circle, rgba(${themeColors.primaryRgb}, 0.03) 0%, transparent 100%);`"></div>
            <div class="absolute bottom-8 left-8 w-24 h-24 rounded-full blur-3xl animate-pulse-slow" :style="`background: radial-gradient(circle, rgba(${themeColors.primaryRgb}, 0.02) 0%, transparent 100%); animation-delay: 3s;`"></div>
            <div class="absolute top-1/3 right-1/4 w-20 h-20 rounded-full blur-3xl animate-pulse-slow" :style="`background: radial-gradient(circle, rgba(${themeColors.primaryRgb}, 0.025) 0%, transparent 100%); animation-delay: 6s;`"></div>
        </div>

        <div class="absolute inset-0 pointer-events-none" ref="particleContainer"></div>

        <div class="absolute inset-0 pointer-events-none" ref="networkContainer"></div>

        <HeaderComponent
            :user="user"
            :wallet="wallet"
            :active-nav="activeNav"
            :show-mobile-menu="showMobileMenu"
            @toggle-mobile-menu="toggleMobileMenu"
            @set-active-nav="setActiveNav"
        />

        <main class="relative z-10 flex-grow" role="main">
            <slot />
        </main>

        <Footer />

        <ToastContainer />
    </div>
</template>


<script setup>
import { ref, onMounted, reactive, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import HeaderComponent from "@/Components/User/layout/HeaderComponent.vue";
import ToastContainer from "@/Components/Toast/ToastContainer.vue";
import Footer from "@/Components/Frontend/Footer.vue";

const page = usePage()

const networkContainer = ref(null)
const particleContainer = ref(null)

const showMobileMenu = ref(false)
const activeNav = ref('Dashboard')

const wallet = reactive({
    balance: 247850.50,
    total_invested: 185000.00,
    total_earnings: 62850.50,
    total_referral_bonus: 12450.75,
    dailyChange: 2.4,
    currentAPY: 27.8
})

const user = reactive({
    name: 'Alexander Sterling',
    email: 'alex@example.com',
    referral_code: 'ELITE2024AX',
    total_referrals: 47,
    active_referrals: 32,
    kyc_status: 'approved',
    status: 'active'
})

const primaryColor = computed(() => page.props.primaryColor || '#86efac')
const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') {
        return { r: 134, g: 239, b: 172 };
    }
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 };
};

const rgbToHex = (r, g, b) => {
    return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
};

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex);
    const newR = Math.min(255, Math.max(0, rgb.r + amount));
    const newG = Math.min(255, Math.max(0, rgb.g + amount));
    const newB = Math.min(255, Math.max(0, rgb.b + amount));
    return rgbToHex(newR, newG, newB);
};

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryLight: adjustColor(primaryColor.value, 40),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}));

const toggleMobileMenu = () => {
    showMobileMenu.value = !showMobileMenu.value
}

const setActiveNav = (navItem) => {
    activeNav.value = navItem
}

const createNetworkVisualization = () => {
    if (!networkContainer.value) return

    const nodes = 25
    const connections = []
    for (let i = 0; i < nodes; i++) {
        const node = document.createElement('div')
        node.className = 'absolute w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full opacity-60 animate-pulse-slow'
        node.style.background = themeColors.value.primary
        node.style.left = Math.random() * 100 + '%'
        node.style.top = Math.random() * 100 + '%'
        node.style.animationDelay = Math.random() * 5 + 's'
        node.style.animationDuration = (Math.random() * 3 + 2) + 's'
        node.style.boxShadow = `0 0 ${Math.random() * 20 + 10}px currentColor`

        networkContainer.value.appendChild(node)
        connections.push({
            element: node,
            x: parseFloat(node.style.left),
            y: parseFloat(node.style.top)
        })
    }

    for (let i = 0; i < connections.length; i++) {
        for (let j = i + 1; j < connections.length; j++) {
            const distance = Math.sqrt(
                Math.pow(connections[i].x - connections[j].x, 2) +
                Math.pow(connections[i].y - connections[j].y, 2)
            )

            if (distance < 25 && Math.random() > 0.7) {
                const line = document.createElement('div')
                line.className = 'absolute opacity-20'
                line.style.width = '1px'
                line.style.height = distance + 'vh'
                line.style.background = `linear-gradient(to bottom, ${themeColors.value.primary}, ${themeColors.value.primaryDark})`
                line.style.left = connections[i].x + '%'
                line.style.top = connections[i].y + '%'
                line.style.transformOrigin = 'top'
                line.style.transform = `rotate(${Math.atan2(connections[j].y - connections[i].y, connections[j].x - connections[i].x) * 180 / Math.PI + 90}deg)`

                networkContainer.value.appendChild(line)
            }
        }
    }
}

const createParticleSystem = () => {
    if (!particleContainer.value) return

    for (let i = 0; i < 12; i++) {
        const particle = document.createElement('div')
        particle.className = 'absolute w-1 h-1 rounded-full opacity-30'
        particle.style.background = '#ffffff'
        particle.style.left = Math.random() * 100 + '%'
        particle.style.top = Math.random() * 100 + '%'
        particle.style.animation = `float-particle ${Math.random() * 20 + 10}s linear infinite`
        particle.style.animationDelay = Math.random() * 20 + 's'

        particleContainer.value.appendChild(particle)
    }
}

onMounted(() => {
    createNetworkVisualization()
    createParticleSystem()
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

defineExpose({
    user,
    wallet,
    activeNav,
    setActiveNav
})
</script>

<style scoped>
@keyframes pulse-slow {
    0%, 100% {
        opacity: 0.2;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.005);
    }
}

@keyframes float-particle {
    0% {
        transform: translateY(100vh) translateX(0px) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 0.3;
    }
    90% {
        opacity: 0.3;
    }
    100% {
        transform: translateY(-10vh) translateX(50px) rotate(360deg);
        opacity: 0;
    }
}

.animate-pulse-slow {
    animation: pulse-slow 4s ease-in-out infinite;
}

::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: rgba(30, 41, 59, 0.1);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: v-bind('`linear-gradient(to bottom, rgba(${themeColors.primaryRgb}, 0.5), rgba(${themeColors.primaryRgb}, 0.3))`');
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: v-bind('`linear-gradient(to bottom, rgba(${themeColors.primaryRgb}, 0.7), rgba(${themeColors.primaryRgb}, 0.4))`');
}

@media (prefers-reduced-motion: reduce) {
    .animate-pulse-slow,
    .animate-fade-in-up {
        animation: none !important;
    }
}
</style>

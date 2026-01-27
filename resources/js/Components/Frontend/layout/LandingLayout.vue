<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute inset-0" :style="gradientOverlay"></div>
            <div class="absolute inset-0" :style="starFieldStyle"></div>
            <div class="absolute inset-0" :style="meshGradientStyle"></div>
            <div class="absolute inset-0" :style="noisePattern"></div>
        </div>

        <div class="absolute inset-0 overflow-hidden">
            <div
                class="absolute top-12 right-12 w-48 h-48 sm:w-60 sm:h-60 lg:w-72 lg:h-72 rounded-full animate-float-slow opacity-20 lg:opacity-30"
                :style="orb1Style"
            ></div>
            <div
                class="absolute bottom-16 left-6 w-36 h-36 sm:w-48 sm:h-48 lg:w-60 lg:h-60 rounded-full animate-float-reverse animation-delay-3000 opacity-15 lg:opacity-25"
                :style="orb2Style"
            ></div>
            <div
                class="absolute top-1/3 left-1/5 w-32 h-32 sm:w-40 sm:h-40 lg:w-48 lg:h-48 rounded-full animate-float-diagonal animation-delay-6000 opacity-10 lg:opacity-20"
                :style="orb3Style"
            ></div>
            <div
                class="absolute top-2/3 right-1/4 w-24 h-24 sm:w-32 sm:h-32 lg:w-40 lg:h-40 rounded-full animate-float-slow animation-delay-9000 opacity-15"
                :style="orb4Style"
            ></div>
        </div>

        <div class="absolute inset-0" ref="particleContainer"></div>
        <div class="absolute inset-0" ref="networkContainer"></div>
        <slot/>
    </div>
</template>

<script setup>
import {ref, onMounted, computed} from 'vue'
import {usePage} from '@inertiajs/vue3'

const page = usePage()
const networkContainer = ref(null)
const particleContainer = ref(null)

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

const themeColors = computed(() => {
    const rgb = hexToRgb(primaryColor.value)
    return {
        primary: primaryColor.value,
        primaryDark: adjustColor(primaryColor.value, -20),
        primaryLight: adjustColor(primaryColor.value, 40),
        primaryRgb: `${rgb.r}, ${rgb.g}, ${rgb.b}`
    }
})

const gradientOverlay = computed(() => ({
    background: `radial-gradient(ellipse at center, rgba(${themeColors.value.primaryRgb}, 0.15) 0%, rgba(15, 15, 35, 0.8) 70%)`
}))

const starFieldStyle = computed(() => ({
    background: `
        radial-gradient(2px 2px at 20px 30px, rgba(255,255,255,0.4), transparent),
        radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.3), transparent),
        radial-gradient(1px 1px at 90px 40px, rgba(255,255,255,0.5), transparent),
        radial-gradient(1px 1px at 130px 80px, rgba(255,255,255,0.4), transparent),
        radial-gradient(2px 2px at 160px 30px, rgba(255,255,255,0.3), transparent),
        radial-gradient(1px 1px at 200px 60px, rgba(255,255,255,0.4), transparent)
    `,
    backgroundRepeat: 'repeat',
    backgroundSize: '200px 100px'
}))

const meshGradientStyle = computed(() => ({
    background: `
        linear-gradient(90deg, transparent 24%, rgba(${themeColors.value.primaryRgb}, 0.08) 25%, rgba(${themeColors.value.primaryRgb}, 0.08) 26%, transparent 27%, transparent 74%, rgba(${themeColors.value.primaryRgb}, 0.08) 75%, rgba(${themeColors.value.primaryRgb}, 0.08) 76%, transparent 77%, transparent),
        linear-gradient(rgba(${themeColors.value.primaryRgb}, 0.08) 24%, transparent 25%, transparent 26%, rgba(${themeColors.value.primaryRgb}, 0.08) 27%, rgba(${themeColors.value.primaryRgb}, 0.08) 74%, transparent 75%, transparent 76%, rgba(${themeColors.value.primaryRgb}, 0.08) 77%, rgba(${themeColors.value.primaryRgb}, 0.08))
    `,
    backgroundSize: '50px 50px'
}))

const noisePattern = computed(() => ({
    backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.03'/%3E%3C/svg%3E")`,
    backgroundSize: '100px 100px'
}))

const orb1Style = computed(() => ({
    background: `radial-gradient(circle, rgba(${themeColors.value.primaryRgb}, 0.3) 0%, rgba(${themeColors.value.primaryRgb}, 0.15) 50%, transparent 100%)`,
    filter: 'blur(60px)'
}))

const orb2Style = computed(() => {
    const darkRgb = hexToRgb(themeColors.value.primaryDark)
    return {
        background: `radial-gradient(circle, rgba(${darkRgb.r}, ${darkRgb.g}, ${darkRgb.b}, 0.3) 0%, rgba(${themeColors.value.primaryRgb}, 0.15) 50%, transparent 100%)`,
        filter: 'blur(60px)'
    }
})

const orb3Style = computed(() => ({
    background: `radial-gradient(circle, rgba(${themeColors.value.primaryRgb}, 0.2) 0%, rgba(${themeColors.value.primaryRgb}, 0.1) 50%, transparent 100%)`,
    filter: 'blur(60px)'
}))

const orb4Style = computed(() => ({
    background: `radial-gradient(circle, rgba(${themeColors.value.primaryRgb}, 0.2) 0%, rgba(${themeColors.value.primaryRgb}, 0.1) 50%, transparent 100%)`,
    filter: 'blur(60px)'
}))

const createNetworkVisualization = () => {
    if (!networkContainer.value) return

    const nodes = 35
    const connections = []
    const primaryHex = themeColors.value.primary
    const darkHex = themeColors.value.primaryDark

    for (let i = 0; i < nodes; i++) {
        const node = document.createElement('div')
        node.className = 'absolute w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full opacity-60 animate-pulse'
        node.style.background = Math.random() > 0.5 ? primaryHex : darkHex
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
                line.style.background = `linear-gradient(to bottom, ${primaryHex}, ${darkHex})`
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

    for (let i = 0; i < 15; i++) {
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
})
</script>

<style scoped>
@keyframes float-slow {
    0%, 100% {
        transform: translateY(0px) rotate(0deg) scale(1);
    }
    33% {
        transform: translateY(-20px) rotate(120deg) scale(1.05);
    }
    66% {
        transform: translateY(-10px) rotate(240deg) scale(0.95);
    }
}

@keyframes float-reverse {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(25px) rotate(-180deg);
    }
}

@keyframes float-diagonal {
    0%, 100% {
        transform: translate(0px, 0px) rotate(0deg);
    }
    25% {
        transform: translate(10px, -15px) rotate(90deg);
    }
    50% {
        transform: translate(-5px, -25px) rotate(180deg);
    }
    75% {
        transform: translate(-10px, -10px) rotate(270deg);
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

.animate-float-slow {
    animation: float-slow 25s ease-in-out infinite;
}

.animate-float-reverse {
    animation: float-reverse 20s ease-in-out infinite;
}

.animate-float-diagonal {
    animation: float-diagonal 30s ease-in-out infinite;
}

.animation-delay-3000 {
    animation-delay: 3s;
}

.animation-delay-6000 {
    animation-delay: 6s;
}

.animation-delay-9000 {
    animation-delay: 9s;
}

@media (max-width: 640px) {
    .animate-float-slow,
    .animate-float-reverse,
    .animate-float-diagonal {
        animation-duration: 15s;
    }
}

@media (prefers-reduced-motion: reduce) {
    .animate-float-slow,
    .animate-float-reverse,
    .animate-float-diagonal {
        animation: none !important;
    }
}
</style>

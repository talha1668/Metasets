<template>
    <UserLayout
        :page-title="t('finance')"
        :page-section="t('overview')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('investmentDashboard')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <div class="space-y-6">
                    <section class="text-center animate-fade-in-up">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-3">{{ t('fundManagement') }}</h1>
                        <p class="text-sm sm:text-base font-medium text-slate-400 max-w-xl mx-auto">
                            {{ t('securelyManageFunds') }}</p>
                    </section>

                    <section class="flex justify-center animate-fade-in-up animation-delay-300">
                        <div
                            class="bg-gradient-to-br from-slate-900/70 to-slate-800/50 backdrop-blur-xl border border-slate-700/30 rounded-xl p-1.5 shadow-2xl">
                            <div class="flex items-center gap-1.5" role="tablist" :aria-label="t('fundManagementTabs')">
                                <button
                                    @click="activeTab = 'deposit'"
                                    :class="['px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900', activeTab === 'deposit' ? 'text-slate-900 shadow-lg' : 'text-slate-300 hover:text-white hover:bg-slate-800/30']"
                                    :style="activeTab === 'deposit' ? `background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); box-shadow: 0 0 0 2px rgba(${themeColors.primaryRgb}, 0.2); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : ''"
                                    role="tab"
                                    :aria-selected="activeTab === 'deposit'"
                                    :aria-controls="'deposit-panel'"
                                    :aria-label="t('depositFundsTab')"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                             aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        {{ t('depositFunds') }}
                                    </span>
                                </button>

                                <button
                                    @click="activeTab = 'withdraw'"
                                    :class="['px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900', activeTab === 'withdraw' ? 'text-slate-900 shadow-lg' : 'text-slate-300 hover:text-white hover:bg-slate-800/30']"
                                    :style="activeTab === 'withdraw' ? `background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); box-shadow: 0 0 0 2px rgba(${themeColors.primaryRgb}, 0.2); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);` : ''"
                                    role="tab"
                                    :aria-selected="activeTab === 'withdraw'"
                                    :aria-controls="'withdraw-panel'"
                                    :aria-label="t('withdrawFundsTab')"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                             aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        {{ t('withdrawFunds') }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="animate-fade-in-up animation-delay-600">
                        <DepositComponent
                            v-if="activeTab === 'deposit'"
                            id="deposit-panel"
                            role="tabpanel"
                            :aria-labelledby="t('depositFundsTab')"
                            :deposits="deposits"
                            :payment-gateways="paymentGateways"
                            :wallet-balance="walletBalance"
                        />

                        <WithdrawComponent
                            v-else
                            id="withdraw-panel"
                            role="tabpanel"
                            :aria-labelledby="t('withdrawFundsTab')"
                            :withdrawals="withdrawals"
                            :withdrawal-gateways="withdrawalGateways"
                            :wallet-balance="walletBalance"
                        />
                    </section>
                </div>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';
import {usePage} from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import DepositComponent from "@/Components/User/Finance/DepositComponent.vue";
import WithdrawComponent from "@/Components/User/Finance/WithdrawComponent.vue";
import {useToast} from "@/composables/useToast.js";
import {useTranslation} from '@/composables/useTranslation';

const props = defineProps({
    deposits: {
        type: Object,
        default: () => ({data: [], total: 0, links: []})
    },
    withdrawals: {
        type: Object,
        default: () => ({data: [], total: 0, links: []})
    },
    paymentGateways: {
        type: Array,
        default: () => []
    },
    withdrawalGateways: {
        type: Array,
        default: () => []
    },
    walletBalance: {
        type: [Number, String],
        default: 0
    }
});

const activeTab = ref('deposit');
const {showToast} = useToast();
const {t} = useTranslation();
const page = usePage();

const primaryColor = computed(() => page.props.primaryColor || '#86efac');
const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : {r: 134, g: 239, b: 172};
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

onMounted(() => {
    const flash = page.props.flash;
    if (flash?.success) showToast(flash.success, 'success');
    if (flash?.error) showToast(flash.error, 'error');
    if (flash?.warning) showToast(flash.warning, 'warning');
    if (flash?.info) showToast(flash.info, 'info');
});
</script>

<style scoped>
.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out forwards;
}

.animation-delay-300 {
    animation-delay: 0.3s;
}

.animation-delay-600 {
    animation-delay: 0.6s;
}

.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in-up,
    .transition-all {
        animation: none !important;
        transition: none !important;
    }
}
</style>

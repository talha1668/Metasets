<template>
    <UserLayout
        :page-title="t('investmentStaking')"
        :page-section="t('growth')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('investmentStakingDashboard')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <PageHeader :stats="headerStats"/>
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 -translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-4"
                >
                    <div v-if="showAiRecommendationNotice"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border rounded-lg sm:rounded-xl p-4 sm:p-5 animate-fade-in"
                         :style="`border-color: rgba(${themeColors.primaryRgb}, 0.4);`">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm sm:text-base font-bold text-white mb-1.5 flex items-center gap-2">
                                    {{ t('aiRecommendation') }}
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                          :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); color: ${themeColors.primary};`">
                                        {{ t('smart') }}
                                    </span>
                                </h3>
                                <p class="text-sm font-medium text-slate-300 mb-3">
                                    {{ t('aiRecommendedPlan') }} <strong class="text-white">{{ preSelectedPlan?.name }} </strong>
                                    <span v-if="suggestedAmount">
                                    {{ t('withSuggestedAmount') }}
                                    <strong :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatCurrency(suggestedAmount) }}</strong>
                                </span>
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        @click="scrollToRecommendedPlan"
                                        class="inline-flex items-center px-3 py-2 rounded-lg font-semibold text-sm transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                        :style="`background: ${themeColors.primary}; color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                    >
                                        {{ t('viewPlan') }}
                                        <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <button
                                        @click="dismissAiNotice"
                                        class="inline-flex items-center px-3 py-2 rounded-lg font-semibold text-sm bg-slate-800/60 text-slate-300 hover:bg-slate-800 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                        :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                    >
                                        {{ t('dismiss') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
                <TypeToggle
                    :active-type="activeType"
                    @update:activeType="activeType = $event"
                />
                <section v-if="activeType === 'investment'" class="animate-fade-in-up">
                    <SectionTitle
                        :title="t('investmentPlans')"
                        :subtitle="t('investmentPlansSubtitle')"
                    />
                    <InvestmentPlansGrid
                        ref="investmentPlansRef"
                        :plans="investmentPlans"
                        :highlighted-plan-id="preSelectedPlan?.id"
                        @select-plan="selectInvestmentPlan"
                    />

                    <InvestmentLogs
                        :logs="investmentLogs"
                        :show-modal-prop="false"
                        @view-details="viewInvestmentDetails"
                    />
                </section>
                <section v-if="activeType === 'staking'" class="animate-fade-in-up">
                    <SectionTitle
                        :title="t('stakingPools')"
                        :subtitle="t('stakingPoolsSubtitle')"
                    />
                    <StakingPoolsGrid
                        :pools="stakingPools"
                        @select-pool="selectStakingPool"
                    />

                    <StakingLogs
                        :logs="stakingLogs"
                        @unstake="handleUnstake"
                        @claim-rewards="handleClaimRewards"
                        @view-details="viewStakingDetails"
                    />
                </section>
            </div>
        </div>
        <InvestmentModal
            v-if="showInvestmentModal"
            :show="showInvestmentModal"
            :plan="selectedPlan"
            :form="investmentForm"
            :processing="processing"
            @close="closeModal"
        />
        <StakingModal
            v-if="showStakingModal"
            :show="showStakingModal"
            :pool="selectedPool"
            :form="stakingForm"
            :processing="processing"
            @close="closeModal"
        />
    </UserLayout>
</template>

<script setup>
import {ref, reactive, computed, onMounted, onUnmounted, nextTick} from 'vue';
import {router, usePage} from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import {useToast} from "@/composables/useToast.js";
import {useTranslation} from '@/composables/useTranslation';
import StakingModal from "@/Components/User/Investment/StakingModal.vue";
import InvestmentModal from "@/Components/User/Investment/InvestmentModal.vue";
import StakingPoolsGrid from "@/Components/User/Investment/StakingPoolsGrid.vue";
import SectionTitle from "@/Components/User/Investment/SectionTitle.vue";
import InvestmentPlansGrid from "@/Components/User/Investment/InvestmentPlansGrid.vue";
import TypeToggle from "@/Components/User/Investment/TypeToggle.vue";
import PageHeader from "@/Components/User/Investment/PageHeader.vue";
import InvestmentLogs from "@/Components/User/Investment/InvestmentLogs.vue";
import StakingLogs from "@/Components/User/Investment/StakingLogs.vue";

const props = defineProps({
    investmentPlans: {
        type: Array,
        default: () => [],
        validator: (value) => Array.isArray(value)
    },
    stakingPools: {
        type: Array,
        default: () => [],
        validator: (value) => Array.isArray(value)
    },
    investmentLogs: {
        type: [Array, Object],
        default: () => [],
    },
    stakingLogs: {
        type: [Array, Object],
        default: () => [],
    },
    headerStats: {
        type: Object,
        required: true,
        validator: (value) => value && typeof value === 'object'
    },
    preSelectedPlan: {
        type: Object,
        default: null
    },
    suggestedAmount: {
        type: [Number, String],
        default: null
    }
});

const {showToast} = useToast();
const {t} = useTranslation();
const page = usePage();

const currencySymbol = computed(() => page.props.currencySymbol || '$');
const activeType = ref('investment');
const showInvestmentModal = ref(false);
const showStakingModal = ref(false);
const showInvestmentDetailsModal = ref(false);
const showStakingDetailsModal = ref(false);
const processing = ref(false);
const selectedPlan = ref(null);
const selectedPool = ref(null);
const selectedInvestment = ref(null);
const selectedStake = ref(null);
const showAiRecommendationNotice = ref(false);
const preFilledAmount = ref(null);
const investmentPlansRef = ref(null);

const investmentForm = reactive({
    amount: 0
});

const stakingForm = reactive({
    amount: 0,
    auto_compound: false
});

const primaryColor = computed(() => page.props.primaryColor || '#86efac');

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') return {r: 134, g: 239, b: 172};
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

const selectInvestmentPlan = (plan) => {
    selectedPlan.value = plan;
    if (props.preSelectedPlan && plan.id === props.preSelectedPlan.id && props.suggestedAmount) {
        investmentForm.amount = parseFloat(props.suggestedAmount);
        preFilledAmount.value = parseFloat(props.suggestedAmount);
    } else {
        investmentForm.amount = plan.min_amount;
        preFilledAmount.value = null;
    }

    showInvestmentModal.value = true;
};

const scrollToRecommendedPlan = () => {
    if (activeType.value !== 'investment') {
        activeType.value = 'investment';
    }

    nextTick(() => {
        const planElement = document.getElementById(`investment-plan-${props.preSelectedPlan.id}`);
        if (planElement) {
            planElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            planElement.classList.add('ring-4');
            planElement.style.setProperty('--tw-ring-color', `rgba(${themeColors.value.primaryRgb}, 0.5)`);

            setTimeout(() => {
                planElement.classList.remove('ring-4');
            }, 2000);
        }
    });
};

const dismissAiNotice = () => {
    showAiRecommendationNotice.value = false;
    router.visit('/user/investments', {
        replace: true,
        preserveState: true,
        preserveScroll: true
    });
};

const formatCurrency = (amount) => {
    const number = parseFloat(amount) || 0;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
};

const selectStakingPool = (pool) => {
    selectedPool.value = pool;
    stakingForm.amount = pool.min_stake_amount;
    stakingForm.auto_compound = false;
    showStakingModal.value = true;
};

const closeModal = () => {
    showInvestmentModal.value = false;
    showStakingModal.value = false;
    showInvestmentDetailsModal.value = false;
    showStakingDetailsModal.value = false;
    selectedPlan.value = null;
    selectedPool.value = null;
    preFilledAmount.value = null;

    if (props.preSelectedPlan) {
        router.visit('/user/investments', {
            replace: true,
            preserveState: true,
            preserveScroll: true
        });
    }

    setTimeout(() => {
        selectedInvestment.value = null;
        selectedStake.value = null;
    }, 300);
};

const viewInvestmentDetails = (log) => {
    selectedInvestment.value = log;
    showInvestmentDetailsModal.value = true;
};

const viewStakingDetails = (log) => {
    selectedStake.value = log;
    showStakingDetailsModal.value = true;
};

const handleUnstake = async (log) => {
    showToast(t('unstakingProcessing'), 'info');
};

const handleClaimRewards = async (log) => {
    showToast(t('claimingRewards'), 'info');
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        if (showInvestmentDetailsModal.value || showStakingDetailsModal.value) {
            closeModal();
        } else if (showAiRecommendationNotice.value) {
            dismissAiNotice();
        }
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    if (props.preSelectedPlan) {
        showAiRecommendationNotice.value = true;
        activeType.value = 'investment';

        setTimeout(() => {
            scrollToRecommendedPlan();
        }, 1000);
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
})
</script>

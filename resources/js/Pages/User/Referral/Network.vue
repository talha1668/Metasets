<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NetworkNode from '@/Components/User/NetworkNode.vue';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import {useTranslation} from "@/composables/useTranslation.js";

const { t } = useTranslation();

const props = defineProps({
    network: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total_network: 0,
            active_members: 0,
            total_earnings: 0,
            network_depth: 0
        })
    },
    referralBonuses: {
        type: Array,
        default: () => []
    }
});

const page = usePage();

const primaryColor = computed(() => page.props.primaryColor || '#86efac');
const currencySymbol = computed(() => page.props.currencySymbol || '$');

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') {
        return {r: 134, g: 239, b: 172};
    }
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
const expandedNodes = reactive({});

const showToast = (message, type = 'success') => {
    const toastEvent = new CustomEvent('show-toast', {
        detail: {message, type}
    });
    window.dispatchEvent(toastEvent);
};

const toggleNode = (nodeId) => {
    expandedNodes[nodeId] = !expandedNodes[nodeId];
};

const expandAll = () => {
    const expandRecursively = (nodes) => {
        nodes.forEach(node => {
            expandedNodes[node.id] = true;
            if (node.children && node.children.length > 0) {
                expandRecursively(node.children);
            }
        });
    };
    if (props.network && props.network.length > 0) {
        expandRecursively(props.network);
        showToast(t('allNodesExpanded') || 'All nodes expanded', 'success');
    }
};

const collapseAll = () => {
    Object.keys(expandedNodes).forEach(key => {
        expandedNodes[key] = false;
    });
    showToast(t('allNodesCollapsed') || 'All nodes collapsed', 'info');
};

const formatCurrency = (value) => {
    if (!value || isNaN(value)) return '0.00';
    return parseFloat(value).toFixed(2);
};

onMounted(() => {
    try {
        const flash = page.props.flash;
        if (flash?.success) showToast(flash.success, 'success');
        if (flash?.error) showToast(flash.error, 'error');
        if (flash?.warning) showToast(flash.warning, 'warning');
        if (flash?.info) showToast(flash.info, 'info');
    } catch (error) {
        console.error('Component mount error:', error);
    }
});
</script>

<template>
    <UserLayout
        :page-title="t('network')"
        :page-section="t('referralNetwork')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('networkDashboard')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <section :aria-labelledby="t('networkStatistics')"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                    <h2 class="sr-only">{{ t('networkStatistics') }}</h2>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('totalNetwork')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('totalNetwork') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('totalNetwork')}: ${stats.total_network}`">
                            {{ stats.total_network }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }"
                                  :aria-label="`${t('allLevels')}`">
                                {{ t('allLevels') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('activeMembers')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('activeMembers') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('activeMembers')}: ${stats.active_members}`">
                            {{ stats.active_members }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ t('currentlyActive') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('totalEarnings')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('totalEarnings') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('totalEarnings')}: ${currencySymbol}${formatCurrency(stats.total_earnings)}`">
                            {{ currencySymbol }}{{ formatCurrency(stats.total_earnings) }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ t('fromNetwork') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('networkDepth')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('networkDepth') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('networkDepth')}: ${stats.network_depth}`">
                            {{ stats.network_depth }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ t('maxLevels') }}
                            </span>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('networkHierarchy')"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl hover:border-slate-600/50 transition-all duration-300 shadow-2xl">
                    <div class="p-5 sm:p-6 border-b border-slate-700/40">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-white mb-1.5">{{
                                        t('networkHierarchy')
                                    }}</h3>
                                <p class="text-sm font-medium text-slate-400">{{ t('clickToExpand') }}</p>
                            </div>
                            <div class="flex gap-2.5">
                                <button
                                    @click="expandAll"
                                    class="px-3 sm:px-4 py-2 rounded-lg font-semibold text-xs sm:text-sm transition-all duration-300 hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                    :aria-label="t('expandAll')">
                                    {{ t('expandAll') }}
                                </button>
                                <button
                                    @click="collapseAll"
                                    class="px-3 sm:px-4 py-2 bg-slate-700/50 hover:bg-slate-700 text-white rounded-lg font-semibold text-xs sm:text-sm transition-all duration-300 hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-slate-500/50 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :aria-label="t('collapseAll')">
                                    {{ t('collapseAll') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div v-if="network && network.length > 0" class="space-y-2.5">
                            <NetworkNode
                                v-for="node in network"
                                :key="node.id"
                                :node="node"
                                :level="node.level"
                                :is-expanded="expandedNodes[node.id] || false"
                                :theme-colors="themeColors"
                                :currency-symbol="currencySymbol"
                                :expanded-nodes="expandedNodes"
                                @toggle="toggleNode"
                            />
                        </div>

                        <div v-else class="text-center py-12 sm:py-16">
                            <div class="w-20 h-20 rounded-full mx-auto mb-5 flex items-center justify-center"
                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.1);`">
                                <svg class="w-10 h-10" :style="{ color: themeColors.primary }" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">{{ t('noReferralsYet') }}</h3>
                            <p class="text-slate-400 mb-6 max-w-md mx-auto text-sm">{{ t('startBuildingNetwork') }}</p>
                            <button
                                @click="() => $inertia.visit('/user/referral')"
                                class="px-5 py-2.5 rounded-lg font-semibold text-sm transition-all duration-300 hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`">
                                {{ t('viewReferralCode') }}
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Referral Bonuses Section -->
                <section v-if="referralBonuses && referralBonuses.length > 0"
                         :aria-labelledby="t('referralBonuses')"
                         class="space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white mb-1.5">{{ t('referralBonuses') }}</h3>
                            <p class="text-slate-400 text-sm">{{ t('achieveTheseBonus') }}</p>
                        </div>
                    </div>

                    <!-- Milestone Bonuses -->
                    <div v-if="referralBonuses.filter(b => b.type === 'milestone').length > 0">
                        <h4 class="text-base font-bold text-white mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full"
                                  :style="`background-color: ${themeColors.primary};`"></span>
                            {{ t('milestoneBonuses') }}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                            <div
                                v-for="bonus in referralBonuses.filter(b => b.type === 'milestone')"
                                :key="bonus.id"
                                class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 relative overflow-hidden"
                                :class="bonus.is_claimed ? 'border-green-500/40' : bonus.is_eligible ? `border-[${themeColors.primary}]/40` : 'border-slate-700/40'"
                                role="region"
                                :aria-label="bonus.name">

                                <!-- Claimed Badge -->
                                <div v-if="bonus.is_claimed"
                                     class="absolute top-2.5 right-2.5 px-2.5 py-1 bg-green-500/20 border border-green-500/50 rounded-full text-xs font-bold text-green-500 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                    {{ t('claimed') }}
                                </div>

                                <!-- Eligible Badge -->
                                <div v-else-if="bonus.is_eligible"
                                     class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded-full text-xs font-bold flex items-center gap-1"
                                     :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); border: 1px solid rgba(${themeColors.primaryRgb}, 0.5); color: ${themeColors.primary};`">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    {{ t('eligible') }}
                                </div>

                                <div class="mb-3">
                                    <h5 class="text-sm font-bold text-white mb-1.5">{{ bonus.name }}</h5>
                                    <p class="text-xs text-slate-400">{{ bonus.description }}</p>
                                </div>

                                <!-- Reward Amount -->
                                <div class="mb-3 p-3 rounded-lg"
                                     :style="`background-color: rgba(${themeColors.primaryRgb}, 0.1);`">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-0.5">
                                        {{ t('reward') }}</p>
                                    <p class="text-xl font-bold" :style="{ color: themeColors.primary }">
                                        {{ currencySymbol }}{{ formatCurrency(bonus.reward_amount) }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ bonus.reward_details }}</p>
                                </div>

                                <!-- Requirements -->
                                <div class="space-y-2.5">
                                    <div v-if="bonus.min_active_referrals > 0">
                                        <div class="flex justify-between items-center mb-1.5">
                                            <span class="text-xs font-medium text-slate-400">{{
                                                    t('activeReferrals')
                                                }}</span>
                                            <span class="text-xs font-bold text-white">{{
                                                    bonus.referral_progress
                                                }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-700/30 rounded-full h-2 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                 :style="`width: ${bonus.referral_progress}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark});`"></div>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">
                                            {{ stats.active_members }} / {{ bonus.min_active_referrals }}
                                            {{ t('referrals') }}
                                        </p>
                                    </div>

                                    <div v-if="bonus.min_team_investment > 0">
                                        <div class="flex justify-between items-center mb-1.5">
                                            <span class="text-xs font-medium text-slate-400">{{
                                                    t('teamInvestment')
                                                }}</span>
                                            <span class="text-xs font-bold text-white">{{
                                                    bonus.investment_progress
                                                }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-700/30 rounded-full h-2 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                 :style="`width: ${bonus.investment_progress}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark});`"></div>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">
                                            {{ currencySymbol }}{{ formatCurrency(stats.team_investment || 0) }} /
                                            {{ currencySymbol }}{{ formatCurrency(bonus.min_team_investment) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Achievement Bonuses -->
                    <div v-if="referralBonuses.filter(b => b.type === 'achievement').length > 0" class="mt-6">
                        <h4 class="text-base font-bold text-white mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            {{ t('achievementBonuses') }}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                            <div
                                v-for="bonus in referralBonuses.filter(b => b.type === 'achievement')"
                                :key="bonus.id"
                                class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 relative"
                                :class="bonus.is_claimed ? 'border-green-500/40' : bonus.is_eligible ? 'border-purple-500/40' : 'border-slate-700/40'">

                                <div v-if="bonus.is_claimed"
                                     class="absolute top-2.5 right-2.5 px-2.5 py-1 bg-green-500/20 border border-green-500/50 rounded-full text-xs font-bold text-green-500 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                    {{ t('claimed') }}
                                </div>

                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="text-sm font-bold text-white mb-1.5">{{ bonus.name }}</h5>
                                        <p class="text-xs text-slate-400 mb-2.5">{{ bonus.description }}</p>
                                        <div class="flex items-center gap-2.5">
                                            <span class="text-base font-bold" :style="{ color: themeColors.primary }">
                                                {{ currencySymbol }}{{ formatCurrency(bonus.reward_amount) }}
                                            </span>
                                            <span v-if="bonus.time_frame_days > 0" class="text-xs text-slate-500">
                                                ({{ bonus.time_frame_days }} {{ t('days') }})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loyalty Bonuses -->
                    <div v-if="referralBonuses.filter(b => b.type === 'loyalty').length > 0" class="mt-6">
                        <h4 class="text-base font-bold text-white mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                            {{ t('loyaltyBonuses') }}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                            <div
                                v-for="bonus in referralBonuses.filter(b => b.type === 'loyalty')"
                                :key="bonus.id"
                                class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300">

                                <div class="flex items-start gap-2.5 mb-3">
                                    <div
                                        class="w-9 h-9 rounded-lg bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4.5 h-4.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="text-sm font-bold text-white mb-0.5">{{ bonus.name }}</h5>
                                        <span
                                            class="inline-block px-2 py-0.5 bg-yellow-500/20 text-yellow-500 text-xs font-bold rounded-full">
                                            {{ t('recurring') }}
                                        </span>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-400 mb-3">{{ bonus.description }}</p>

                                <div class="p-2.5 rounded-lg bg-slate-800/40 mb-2.5">
                                    <p class="text-xs text-slate-400 mb-0.5">{{ t('reward') }}</p>
                                    <p class="text-lg font-bold text-yellow-500">
                                        {{ currencySymbol }}{{ formatCurrency(bonus.reward_amount) }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ t('every') }} {{ bonus.time_frame_days }} {{ t('days') }}
                                    </p>
                                </div>

                                <div class="text-xs text-slate-500">
                                    <p>{{ t('requires') }}: {{ bonus.min_active_referrals }}+ {{
                                            t('activeReferrals')
                                        }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300 shadow-2xl"
                        role="region" :aria-labelledby="t('howItWorks')">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center"
                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-white">{{ t('howItWorks') }}</h3>
                        </div>
                        <ul class="space-y-3 text-slate-300">
                            <li class="flex items-start gap-3">
                                <span
                                    class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                                    :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); color: ${themeColors.primary};`">
                                    1
                                </span>
                                <span class="text-sm pt-0.5">{{ t('shareReferralCode') }}</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                                    :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); color: ${themeColors.primary};`">
                                    2
                                </span>
                                <span class="text-sm pt-0.5">{{ t('whenTheySignUp') }}</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                                    :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); color: ${themeColors.primary};`">
                                    3
                                </span>
                                <span class="text-sm pt-0.5">{{ t('earnCommissions') }}</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                                    :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); color: ${themeColors.primary};`">
                                    4
                                </span>
                                <span class="text-sm pt-0.5">{{ t('buildDeeperNetwork') }}</span>
                            </li>
                        </ul>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300 shadow-2xl"
                        role="region" :aria-labelledby="t('networkLevels')">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5 text-purple-500" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-white">{{ t('networkLevels') }}</h3>
                        </div>
                        <div class="space-y-2.5">
                            <div
                                class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                <span class="text-sm font-medium text-slate-300">{{ t('level1Direct') }}</span>
                                <span class="text-sm font-bold" :style="{ color: themeColors.primary }">
                                    {{ network.filter(n => n.level === 1).length }} {{ t('members') }}
                                </span>
                            </div>
                            <div v-if="stats.network_depth >= 2"
                                 class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                <span class="text-sm font-medium text-slate-300">{{ t('level2') }}</span>
                                <span class="text-sm font-bold text-green-500">{{ t('active') }}</span>
                            </div>
                            <div v-if="stats.network_depth >= 3"
                                 class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                <span class="text-sm font-medium text-slate-300">{{ t('level3') }}</span>
                                <span class="text-sm font-bold text-green-500">{{ t('active') }}</span>
                            </div>
                            <div v-if="stats.network_depth >= 4"
                                 class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                <span class="text-sm font-medium text-slate-300">{{ t('level4Plus') }}</span>
                                <span class="text-sm font-bold text-green-500">{{ t('active') }}</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </UserLayout>
</template>

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

.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

button:focus,
[tabindex]:focus {
    outline: 2px solid v-bind('`rgba(${themeColors.primaryRgb}, 0.4)`');
    outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in-up,
    .transition-all {
        animation: none !important;
        transition: none !important;
    }
}

@media (max-width: 640px) {
    .grid {
        gap: 1rem;
    }
}

button,
a,
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

*:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

body {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
}

.hover\:scale-\[1\.01\]:hover,
.hover\:scale-\[1\.02\]:hover,
.hover\:scale-105:hover {
    will-change: transform;
}

@media (max-width: 768px) {
    * {
        -webkit-overflow-scrolling: touch;
    }
}

@media print {
    .hover\:scale-\[1\.01\],
    .hover\:scale-\[1\.02\],
    .hover\:scale-105 {
        transform: none !important;
    }

    button,
    a[href^="/"] {
        display: none;
    }
}
</style>

<template>
    <UserLayout
        :page-title="t('referral')"
        :page-section="t('network')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('referralDashboard')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <section :aria-labelledby="t('referralStatistics')"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                    <h2 class="sr-only">{{ t('referralStatistics') }}</h2>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('totalReferrals')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('totalReferrals') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('totalReferrals')}: ${referralStats.totalReferrals}`">
                            {{ referralStats.totalReferrals }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }"
                                  :aria-label="`${referralStats.activeReferrals} ${t('active')}`">
                                {{ referralStats.activeReferrals }} {{ t('active') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('totalEarned')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('totalEarned') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('totalEarned')}: ${currencySymbol}${formatCurrency(referralStats.totalEarned)}`">
                            {{ currencySymbol }}{{ formatCurrency(referralStats.totalEarned) }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }"
                                  :aria-label="`${t('growth')}: ${t('plus')} ${calculateGrowth()} ${t('percent')}`">
                                +{{ calculateGrowth() }}%
                            </span>
                            <span class="text-xs font-medium text-slate-400">{{ t('thisMonth') }}</span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('pendingCommission')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('pendingCommission') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('pendingCommission')}: ${currencySymbol}${formatCurrency(referralStats.pendingCommission)}`">
                            {{ currencySymbol }}{{ formatCurrency(referralStats.pendingCommission) }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{
                                    t('awaiting')
                                }}</span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('teamInvestment')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('teamInvestment') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('teamInvestment')}: ${currencySymbol}${formatCurrency(referralStats.teamInvestment)}`">
                            {{ currencySymbol }}{{ formatCurrency(referralStats.teamInvestment) }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }"
                                  :aria-label="`${referralStats.totalReferrals} ${t('members')}`">
                                {{ referralStats.totalReferrals }} {{ t('members') }}
                            </span>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('referralMainContent')"
                         class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">
                    <h2 class="sr-only">{{ t('referralMainContent') }}</h2>
                    <div class="xl:col-span-8 space-y-5 sm:space-y-6">
                        <div
                            class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300 shadow-2xl"
                            role="region" :aria-labelledby="t('yourReferralLink')">
                            <div class="mb-4 sm:mb-5 flex items-center justify-between">
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-white mb-1.5">{{
                                            t('yourReferralLink')
                                        }}</h3>
                                    <p class="text-sm font-medium text-slate-400">{{ t('shareThisLink') }}</p>
                                </div>
                                <Link
                                    href="/user/referral-level"
                                    class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-lg hover:opacity-90 transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`color: ${themeColors.primary}; background: rgba(${themeColors.primaryRgb}, 0.1); border: 1px solid rgba(${themeColors.primaryRgb}, 0.3); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                    :aria-label="t('viewLevel')">
                                    {{ t('viewLevel') }}
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </Link>
                            </div>

                            <div class="relative">
                                <input
                                    type="text"
                                    :value="referralLink"
                                    readonly
                                    class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700/50 rounded-lg text-sm font-medium text-white transition-all focus:border-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 pr-24 sm:pr-28"
                                    :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.3);`"
                                    @click="selectText"
                                    :aria-label="t('yourReferralLink')">
                                <button
                                    @click="copyToClipboard"
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg font-semibold text-xs transition-all duration-300 hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); color: #0f172a; --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                    :aria-label="copied ? t('linkCopied') : t('copyReferralLink')">
                                    <span class="flex items-center gap-1.5">
                                        <svg v-if="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                        {{ copied ? t('copied') : t('copy') }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div
                            class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300 shadow-2xl"
                            role="region" :aria-labelledby="t('commissionStructure')">
                            <div class="mb-4 sm:mb-5">
                                <h3 class="text-base sm:text-lg font-bold text-white mb-1.5">{{
                                        t('commissionStructure')
                                    }}</h3>
                                <p class="text-sm font-medium text-slate-400">{{ t('earnUpToLevels') }}</p>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2.5 sm:gap-3" role="list"
                                 :aria-label="t('commissionTierLevels')">
                                <div
                                    v-for="tier in commissionTiers"
                                    :key="tier.level"
                                    class="bg-slate-800/40 rounded-lg p-3 border border-slate-700/30 hover:bg-slate-800/60 hover:scale-[1.02] transition-all duration-300 text-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                    role="listitem"
                                    tabindex="0"
                                    :aria-label="`${t('level')} ${tier.level}: ${tier.commission_rate} ${t('percent')} ${t('commission')}`">
                                    <div class="font-bold text-xl sm:text-2xl mb-1.5"
                                         :style="{ color: themeColors.primary }">{{ tier.commission_rate }}%
                                    </div>
                                    <div class="text-white font-semibold text-xs mb-0.5">{{ t('level') }}
                                        {{ tier.level }}
                                    </div>
                                    <div class="text-slate-400 text-xs font-medium capitalize">{{
                                            tier.applies_to
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-4 space-y-5 sm:space-y-6">
                        <div
                            class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300 shadow-2xl"
                            role="region" :aria-labelledby="t('networkOverview')">
                            <div class="flex items-center justify-between mb-4 sm:mb-5">
                                <h3 class="text-base sm:text-lg font-bold text-white">{{
                                        t('networkOverview')
                                    }}</h3>
                                <Link
                                    href="/user/referral-network"
                                    class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-lg hover:opacity-90 transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`color: ${themeColors.primary}; background: rgba(${themeColors.primaryRgb}, 0.1); border: 1px solid rgba(${themeColors.primaryRgb}, 0.3); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                    :aria-label="t('viewNetwork')">
                                    {{ t('viewNetwork') }}
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </Link>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5 sm:gap-3 mb-4 sm:mb-5">
                                <div
                                    class="text-center p-3 rounded-lg border hover:scale-[1.02] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`background: rgba(${themeColors.primaryRgb}, 0.1); border-color: rgba(${themeColors.primaryRgb}, 0.3); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                    tabindex="0"
                                    role="region"
                                    :aria-label="`${t('totalReferrals')}: ${referralStats.totalReferrals}`">
                                    <p class="text-xl font-bold text-white mb-0.5">{{ referralStats.totalReferrals }}</p>
                                    <p class="text-xs font-semibold" :style="{ color: themeColors.primary }">
                                        {{ t('total') }}</p>
                                </div>
                                <div
                                    class="text-center p-3 rounded-lg border hover:scale-[1.02] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`background: rgba(${themeColors.primaryRgb}, 0.1); border-color: rgba(${themeColors.primaryRgb}, 0.3); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                    tabindex="0"
                                    role="region"
                                    :aria-label="`${t('activeReferrals')}: ${referralStats.activeReferrals}`">
                                    <p class="text-xl font-bold text-white mb-0.5">{{
                                            referralStats.activeReferrals
                                        }}</p>
                                    <p class="text-xs font-semibold" :style="{ color: themeColors.primary }">
                                        {{ t('active') }}</p>
                                </div>
                            </div>

                            <div class="mb-4 sm:mb-5">
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-slate-400 font-medium">{{ t('commissionProgress') }}</span>
                                    <span class="text-white font-semibold"
                                          :aria-label="`${t('commissionEarned')}: ${currencySymbol}${formatCurrency(referralStats.totalEarned)}`">
                                        {{ currencySymbol }}{{ formatCurrency(referralStats.totalEarned) }}
                                    </span>
                                </div>
                                <div class="w-full bg-slate-700/50 rounded-full h-2 overflow-hidden"
                                     role="progressbar" :aria-valuenow="progressPercentage" aria-valuemin="0"
                                     aria-valuemax="100"
                                     :aria-label="`${t('commissionProgress')}: ${progressPercentage}% ${t('of')} ${currencySymbol}${formatNumber(progressGoal)} ${t('goal')}`">
                                    <div class="h-full rounded-full transition-all duration-1000"
                                         :style="`width: ${progressPercentage}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark});`"></div>
                                </div>
                                <div class="flex justify-between text-xs font-medium text-slate-500 mt-1.5">
                                    <span>{{ currencySymbol }}0</span>
                                    <span>{{ currencySymbol }}{{ formatNumber(progressGoal) }} {{ t('goal') }}</span>
                                </div>
                            </div>

                            <div class="p-4 rounded-lg border hover:scale-[1.02] transition-all duration-300"
                                 :style="`background: linear-gradient(to right, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.05)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <p class="text-xs text-slate-400 font-semibold mb-2.5 uppercase tracking-wider">
                                    {{ t('referralCode') }}</p>
                                <div class="flex items-center gap-2">
                                    <code
                                        class="flex-1 p-2.5 bg-slate-800/60 rounded-lg text-sm font-mono font-semibold break-all"
                                        :style="{ color: themeColors.primary }"
                                        tabindex="0"
                                        :aria-label="`${t('yourReferralCode')}: ${referralCode}`">{{
                                            referralCode
                                        }}</code>
                                    <button
                                        @click="copyReferralCode"
                                        class="p-2.5 rounded-lg hover:scale-110 transition-all duration-300 group focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                        :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                        :aria-label="t('copyReferralCode')">
                                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform"
                                             :style="{ color: themeColors.primary }" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('recentActivityTopReferrers')"
                         class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
                    <h2 class="sr-only">{{ t('recentActivityTopReferrers') }}</h2>
                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300 shadow-2xl"
                        role="region" :aria-labelledby="t('recentCommissions')">
                        <div class="flex items-center justify-between mb-4 sm:mb-5">
                            <div class="flex-1">
                                <h3 class="text-base sm:text-lg font-bold text-white mb-1">{{
                                        t('recentCommissions')
                                    }}</h3>
                                <p class="text-sm font-medium text-slate-400">{{ t('latestReferralEarnings') }}</p>
                            </div>
                            <Link
                                href="/user/referral-commissions"
                                class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-lg hover:opacity-90 transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`color: ${themeColors.primary}; background: rgba(${themeColors.primaryRgb}, 0.1); border: 1px solid rgba(${themeColors.primaryRgb}, 0.3); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                :aria-label="t('viewAllCommissions')">
                                {{ t('viewAll') }}
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </Link>
                        </div>
                        <div class="space-y-2.5 sm:space-y-3" role="list" :aria-label="t('recentCommissionsList')">
                            <div
                                v-for="commission in recentCommissions"
                                :key="commission.id"
                                class="flex items-center justify-between p-3.5 sm:p-4 bg-slate-800/40 rounded-lg hover:bg-slate-800/60 hover:scale-[1.01] transition-all duration-300 border border-slate-700/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                role="listitem"
                                tabindex="0"
                                :aria-label="`${t('commission')} ${t('from')} ${commission.referrer_name}: ${currencySymbol}${formatCurrency(commission.commission_amount)}, ${t('status')}: ${commission.status}`">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div
                                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                        :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`"
                                        aria-hidden="true">
                                        <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="{ color: themeColors.primary }"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-white text-sm truncate">
                                            {{ commission.referrer_name }}</p>
                                        <p class="text-xs text-slate-400 font-medium capitalize truncate">
                                            {{ commission.type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 ml-3">
                                    <p class="font-bold text-sm sm:text-base" :style="{ color: themeColors.primary }">
                                        +{{ currencySymbol }}{{ formatCurrency(commission.commission_amount) }}</p>
                                    <p class="text-xs text-slate-400 capitalize font-medium">
                                        {{ commission.status }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300 shadow-2xl"
                        role="region" :aria-labelledby="t('topReferrals')">
                        <div class="flex items-center justify-between mb-4 sm:mb-5">
                            <div class="flex-1">
                                <h3 class="text-base sm:text-lg font-bold text-white mb-1">{{ t('topReferrals') }}</h3>
                                <p class="text-sm font-medium text-slate-400">{{ t('yourHighestEarners') }}</p>
                            </div>
                            <Link
                                href="/user/referral-list"
                                class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-lg hover:opacity-90 transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`color: ${themeColors.primary}; background: rgba(${themeColors.primaryRgb}, 0.1); border: 1px solid rgba(${themeColors.primaryRgb}, 0.3); --tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                :aria-label="t('viewAllReferrals')">
                                {{ t('viewAll') }}
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </Link>
                        </div>
                        <div class="space-y-3" role="list" :aria-label="t('topPerformingReferrals')">
                            <div
                                v-for="(referral, index) in topReferrals"
                                :key="referral.id"
                                class="flex items-center justify-between p-4 bg-slate-800/40 rounded-lg hover:bg-slate-800/60 hover:scale-[1.02] transition-all duration-300 border border-slate-700/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`--tw-ring-color: rgba(${themeColors.primaryRgb}, 0.5);`"
                                role="listitem"
                                tabindex="0"
                                :aria-label="`${referral.name}, ${t('level')} ${referral.level}, ${t('earned')} ${currencySymbol}${formatCurrency(referral.total_commission_earned)}`">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                         :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); border: 2px solid rgba(${themeColors.primaryRgb}, 0.3);`"
                                         aria-hidden="true">
                        <span class="text-base font-bold" :style="{ color: themeColors.primary }">{{
                                getInitials(referral.name)
                            }}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-white text-sm">{{ referral.name }}</p>
                                        <p class="text-xs text-slate-400 font-medium">{{ t('level') }} {{
                                                referral.level
                                            }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-base sm:text-lg" :style="{ color: themeColors.primary }">
                                        {{ currencySymbol }}{{ formatCurrency(referral.total_commission_earned) }}</p>
                                    <p class="text-xs text-slate-400 font-medium">{{ t('earned') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';
import { usePage, Link} from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import {useToast} from "@/composables/useToast.js";
import {useTranslation} from '@/composables/useTranslation';

const props = defineProps({
    referralStats: {
        type: Object,
        required: true
    },
    commissionTiers: {
        type: Array,
        default: () => []
    },
    recentCommissions: {
        type: Array,
        default: () => []
    },
    topReferrals: {
        type: Array,
        default: () => []
    },
    referralLink: {
        type: String,
        required: true
    },
    referralCode: {
        type: String,
        required: true
    }
});

const {showToast} = useToast();
const {t} = useTranslation();
const page = usePage();

const copied = ref(false);
const progressGoal = computed(() => {
    const earned = props.referralStats.totalEarned;
    if (earned < 5000) return 5000;
    if (earned < 10000) return 10000;
    if (earned < 20000) return 20000;
    if (earned < 50000) return 50000;
    return Math.ceil(earned / 10000) * 10000 + 10000;
});

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

const progressPercentage = computed(() => {
    return Math.min(100, Math.round((props.referralStats.totalEarned / progressGoal.value) * 100));
});

const formatCurrency = (amount) => {
    const number = parseFloat(amount) || 0;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
};

const formatNumber = (num) => {
    const number = parseFloat(num) || 0;
    if (number >= 1000000000) {
        return (number / 1000000000).toFixed(1) + 'B';
    } else if (number >= 1000000) {
        return (number / 1000000).toFixed(1) + 'M';
    } else if (number >= 1000) {
        return (number / 1000).toFixed(1) + 'K';
    }
    return number.toString();
};

const calculateGrowth = () => {
    return props.referralStats.monthlyGrowth || 0;
};

const getInitials = (name) => {
    if (!name) return 'NA';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const copyToClipboard = async () => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(props.referralLink);
            copied.value = true;
            showToast(t('referralLinkCopied') || 'Referral link copied!', 'success');
            setTimeout(() => {
                copied.value = false;
            }, 2000);
        } else {
            fallbackCopyToClipboard(props.referralLink);
        }
    } catch (err) {
        console.error('Failed to copy:', err);
        fallbackCopyToClipboard(props.referralLink);
    }
};

const copyReferralCode = async () => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(props.referralCode);
            showToast(t('referralCodeCopied') || 'Referral code copied!', 'success');
        } else {
            fallbackCopyToClipboard(props.referralCode);
        }
    } catch (err) {
        console.error('Failed to copy:', err);
        fallbackCopyToClipboard(props.referralCode);
    }
};

const fallbackCopyToClipboard = (text) => {
    try {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        const successful = document.execCommand('copy');
        textArea.remove();

        if (successful) {
            if (text === props.referralLink) {
                copied.value = true;
                setTimeout(() => {
                    copied.value = false;
                }, 2000);
            }
            showToast(
                text === props.referralLink
                    ? (t('referralLinkCopied') || 'Referral link copied!')
                    : (t('referralCodeCopied') || 'Referral code copied!'),
                'success'
            );
        } else {
            throw new Error('Copy command was unsuccessful');
        }
    } catch (err) {
        console.error('Fallback copy failed:', err);
        showToast(t('failedToCopy') || 'Failed to copy. Please copy manually.', 'error');
    }
};

const selectText = (event) => {
    event.target.select();
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
.hover\:scale-\[1\.02\]:hover {
    will-change: transform;
}

@media (max-width: 768px) {
    * {
        -webkit-overflow-scrolling: touch;
    }
}

@media print {
    .hover\:scale-\[1\.01\],
    .hover\:scale-\[1\.02\] {
        transform: none !important;
    }

    button,
    a[href^="/"] {
        display: none;
    }
}
</style>

<template>
    <UserLayout :page-title="t('earnings')" :page-section="t('financials')">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('investmentDashboard')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('totalEarnings') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">{{
                                currencySymbol
                            }}{{ formatCurrency(earningsStats.totalEarnings) }}</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold"
                                  :style="{ color: themeColors.primary }">+{{ earningsStats.monthlyGrowth }}%</span>
                            <span class="text-xs font-medium text-slate-400">{{ t('thisMonth') }}</span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('investmentProfit') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">{{
                                currencySymbol
                            }}{{ formatCurrency(earningsStats.investmentProfit) }}</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{
                                    earningsStats.activeInvestments
                                }} {{ t('plans') }}</span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('referralCommission') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">{{
                                currencySymbol
                            }}{{ formatCurrency(earningsStats.referralCommission) }}</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{
                                    earningsStats.totalReferrals
                                }} {{ t('referrals') }}</span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('stakingRewards') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">{{
                                currencySymbol
                            }}{{ formatCurrency(earningsStats.stakingRewards) }}</p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{
                                    earningsStats.activeStakes
                                }} {{ t('stakes') }}</span>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 shadow-2xl">
                        <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                <svg class="w-4.5 h-4.5" :style="{ color: themeColors.primary }" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-bold text-white">{{ t('monthlyBreakdown') }}</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="p-3.5 rounded-lg border hover:scale-[1.02] transition-all duration-300"
                                 :style="`background: rgba(${themeColors.primaryRgb}, 0.15); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" :style="{ color: themeColors.primary }" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                        </svg>
                                        <span class="text-sm font-semibold text-white">{{ t('investmentProfits') }}</span>
                                    </div>
                                    <span class="text-base font-bold" :style="{ color: themeColors.primary }">
                                        {{ currencySymbol }}{{ formatCurrency(monthlyBreakdown.investment) }}
                                    </span>
                                </div>
                                <div class="w-full bg-slate-700/50 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000"
                                         :style="`width: ${getProgressPercentage(monthlyBreakdown.investment)}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark});`"></div>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-lg border hover:scale-[1.02] transition-all duration-300"
                                 :style="`background: rgba(${themeColors.primaryRgb}, 0.15); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" :style="{ color: themeColors.primary }" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <span class="text-sm font-semibold text-white">{{ t('referralCommissions') }}</span>
                                    </div>
                                    <span class="text-base font-bold" :style="{ color: themeColors.primary }">
                                        {{ currencySymbol }}{{ formatCurrency(monthlyBreakdown.referral) }}
                                    </span>
                                </div>
                                <div class="w-full bg-slate-700/50 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000"
                                         :style="`width: ${getProgressPercentage(monthlyBreakdown.referral)}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark});`"></div>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-lg border hover:scale-[1.02] transition-all duration-300"
                                 :style="`background: rgba(${themeColors.primaryRgb}, 0.15); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" :style="{ color: themeColors.primary }" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                        </svg>
                                        <span class="text-sm font-semibold text-white">{{ t('stakingRewards') }}</span>
                                    </div>
                                    <span class="text-base font-bold" :style="{ color: themeColors.primary }">
                                        {{ currencySymbol }}{{ formatCurrency(monthlyBreakdown.staking) }}
                                    </span>
                                </div>
                                <div class="w-full bg-slate-700/50 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000"
                                         :style="`width: ${getProgressPercentage(monthlyBreakdown.staking)}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark});`"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 shadow-2xl">
                        <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                <svg class="w-4.5 h-4.5" :style="{ color: themeColors.primary }" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-bold text-white">{{ t('quickStats') }}</h3>
                        </div>
                        <div class="space-y-3">
                            <div
                                class="flex items-center justify-between p-3.5 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 transition-colors">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-slate-300">{{ t('avgDaily') }}</span>
                                </div>
                                <span class="text-base font-bold text-white">{{
                                        currencySymbol
                                    }}{{ formatCurrency(quickStats.avgDailyEarnings) }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between p-3.5 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 transition-colors">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-slate-300">{{ t('thisWeek') }}</span>
                                </div>
                                <span class="text-base font-bold text-white">{{
                                        currencySymbol
                                    }}{{ formatCurrency(quickStats.thisWeek) }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between p-3.5 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 transition-colors">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-slate-300">{{ t('thisMonth') }}</span>
                                </div>
                                <span class="text-base font-bold text-white">{{
                                        currencySymbol
                                    }}{{ formatCurrency(quickStats.thisMonth) }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between p-3.5 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 transition-colors">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-slate-300">{{ t('allTime') }}</span>
                                </div>
                                <span class="text-base font-bold" :style="{ color: themeColors.primary }">{{
                                        currencySymbol
                                    }}{{ formatCurrency(quickStats.allTime) }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 shadow-2xl">
                        <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                                 :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                <svg class="w-4.5 h-4.5" :style="{ color: themeColors.primary }" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-bold text-white">{{ t('transactionSummary') }}</h3>
                        </div>
                        <div class="space-y-3">
                            <div
                                class="flex items-center justify-between p-3.5 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 transition-colors">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" :style="{ color: themeColors.primary }" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-slate-300">{{ t('totalCredits') }}</span>
                                </div>
                                <span class="text-base font-bold" :style="{ color: themeColors.primary }">{{
                                        transactionSummary.totalCredits
                                    }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between p-3.5 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 transition-colors">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M18 12H6"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-slate-300">{{ t('totalDebits') }}</span>
                                </div>
                                <span class="text-base font-bold text-red-400">{{ transactionSummary.totalDebits }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between p-3.5 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 transition-colors">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-slate-300">{{ t('totalFees') }}</span>
                                </div>
                                <span class="text-base font-bold text-white">{{
                                        currencySymbol
                                    }}{{ formatCurrency(transactionSummary.totalFees) }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between p-3.5 rounded-lg border hover:scale-[1.02] transition-all duration-300"
                                :style="`background: linear-gradient(to right, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.05)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4.5 h-4.5" :style="{ color: themeColors.primary }" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                    <span class="text-sm font-bold text-white">{{ t('netEarnings') }}</span>
                                </div>
                                <span class="text-lg font-bold" :style="{ color: themeColors.primary }">{{
                                        currencySymbol
                                    }}{{ formatCurrency(transactionSummary.netEarnings) }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl overflow-hidden hover:border-slate-600/50 transition-all duration-300 shadow-2xl">
                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-700/30">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between gap-3">
                            <div class="flex-1">
                                <h2 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('transactionLogs') }}</h2>
                                <p class="text-sm font-medium text-slate-400">{{ t('completeTransactionHistory') }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button
                                    v-for="filter in ['all', 'credit', 'debit', 'bonus', 'stake', 'unstake', 'staking_reward']"
                                    :key="filter" @click="changeFilter(filter)"
                                    :class="['px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 hover:scale-105 focus:outline-none']"
                                    :style="filterType === filter ? `background: ${themeColors.primary}; color: #0f172a;` : 'background: rgba(71, 85, 105, 0.5); color: #e2e8f0;'">
                                    {{ filter === 'all' ? t('all') : formatTransactionType(filter) }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full hidden lg:table">
                            <thead class="bg-slate-800/50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('transaction') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('type') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('amount') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('fee') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('balance') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('status') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('date') }}
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                    {{ t('actions') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/30">
                            <tr v-if="!paginatedTransactions || paginatedTransactions.length === 0">
                                <td colspan="8" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-14 h-14 mb-3 text-slate-600" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-base font-semibold text-slate-300">{{
                                                t('noTransactionsFound')
                                            }}</p>
                                        <p class="text-sm font-medium text-slate-400 mt-1">{{
                                                t('noTransactionHistory')
                                            }}</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="transaction in paginatedTransactions" :key="transaction.id"
                                class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                                             :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                            <svg class="w-4.5 h-4.5" :style="{ color: themeColors.primary }" fill="none"
                                                 stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    v-if="['credit', 'bonus', 'staking_reward', 'deposit'].includes(transaction.type)"
                                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                                <path v-else stroke-linecap="round" stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-white text-sm">{{
                                                    transaction.transaction_id
                                                }}</p>
                                            <p v-if="transaction.details"
                                               class="text-xs font-medium text-slate-400 mt-0.5 truncate max-w-[180px]">
                                                {{ transaction.details }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800/50 text-white capitalize">{{
                                            formatTransactionType(transaction.type)
                                        }}</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-bold text-sm"
                                       :style="['credit', 'bonus', 'staking_reward', 'deposit'].includes(transaction.type) ? `color: ${themeColors.primary};` : 'color: #ef4444;'">
                                        {{
                                            ['credit', 'bonus', 'staking_reward', 'deposit'].includes(transaction.type) ? '+' : '-'
                                        }}{{ currencySymbol }}{{ formatCurrency(transaction.amount) }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-sm font-semibold text-slate-300">{{
                                            currencySymbol
                                        }}{{ formatCurrency(transaction.fee) }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-sm font-semibold text-white">{{
                                            currencySymbol
                                        }}{{ formatCurrency(transaction.post_balance) }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full border"
                                          :style="getStatusStyle(transaction.status)">{{
                                            formatStatus(transaction.status)
                                        }}</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm">
                                    <div class="text-slate-300 font-medium">{{
                                            formatDate(transaction.created_at)
                                        }}
                                    </div>
                                    <div class="text-xs font-medium text-slate-400">
                                        {{ formatTime(transaction.created_at) }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm">
                                    <button
                                        class="font-semibold transition-colors duration-200 hover:opacity-80 cursor-pointer focus:outline-none"
                                        :style="{ color: themeColors.primary }"
                                        @click="showTransactionDetails(transaction)">
                                        {{ t('viewDetails') }}
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <div class="block lg:hidden">
                            <div v-if="!paginatedTransactions || paginatedTransactions.length === 0"
                                 class="text-center py-12 px-4">
                                <div class="flex flex-col items-center">
                                    <svg class="w-14 h-14 mb-3 text-slate-600" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-base font-semibold text-slate-300">{{ t('noTransactionsFound') }}</p>
                                    <p class="text-sm font-medium text-slate-400 mt-1">{{
                                            t('noTransactionHistory')
                                        }}</p>
                                </div>
                            </div>

                            <div v-for="transaction in paginatedTransactions" :key="transaction.id"
                                 class="border-b border-slate-700/30 last:border-b-0">
                                <div class="p-4 sm:p-5 space-y-3 hover:bg-slate-800/30 transition-colors">
                                    <div class="flex justify-between items-start gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-semibold text-white truncate">
                                                {{ transaction.transaction_id }}
                                            </div>
                                            <div class="text-xs font-medium text-slate-400 mt-0.5 capitalize">
                                                {{ formatTransactionType(transaction.type) }}
                                            </div>
                                        </div>
                                        <span
                                            class="px-2.5 py-1 text-xs font-semibold rounded-full border flex-shrink-0"
                                            :style="getStatusStyle(transaction.status)">
                                        {{ formatStatus(transaction.status) }}
                                    </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-slate-800/40 rounded-lg p-3">
                                            <div
                                                class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">
                                                {{ t('amount') }}
                                            </div>
                                            <div class="text-base font-bold"
                                                 :style="['credit', 'bonus', 'staking_reward', 'deposit'].includes(transaction.type) ? `color: ${themeColors.primary};` : 'color: #ef4444;'">
                                                {{
                                                    ['credit', 'bonus', 'staking_reward', 'deposit'].includes(transaction.type) ? '+' : '-'
                                                }}{{ currencySymbol }}{{ formatCurrency(transaction.amount) }}
                                            </div>
                                        </div>
                                        <div class="bg-slate-800/40 rounded-lg p-3">
                                            <div
                                                class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">
                                                {{ t('balance') }}
                                            </div>
                                            <div class="text-base font-bold text-white">{{
                                                    currencySymbol
                                                }}{{ formatCurrency(transaction.post_balance) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center text-xs font-medium text-slate-400">
                                        <span>{{
                                                formatDate(transaction.created_at)
                                            }} {{ formatTime(transaction.created_at) }}</span>
                                        <span>{{ t('fee') }}: {{ currencySymbol }}{{
                                                formatCurrency(transaction.fee)
                                            }}</span>
                                    </div>

                                    <div class="pt-1">
                                        <button
                                            class="w-full text-sm font-semibold transition-all duration-200 px-4 py-2.5 rounded-lg hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                            :style="{ color: themeColors.primary, backgroundColor: `rgba(${themeColors.primaryRgb}, 0.1)`, border: `1px solid rgba(${themeColors.primaryRgb}, 0.3)`, '--tw-ring-color': `rgba(${themeColors.primaryRgb}, 0.5)` }"
                                            @click="showTransactionDetails(transaction)">
                                            {{ t('viewDetails') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-t border-slate-700/30">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <p class="text-sm font-medium text-slate-400">
                                {{ t('showing') }} <span class="font-semibold text-white">{{
                                    ((currentPage - 1) * itemsPerPage) + 1
                                }}-{{ Math.min(currentPage * itemsPerPage, filteredTransactions.length) }}</span>
                                {{ t('of') }} <span class="font-semibold text-white">{{
                                    filteredTransactions.length
                                }}</span>
                            </p>
                            <div class="flex flex-wrap items-center justify-center sm:justify-end gap-1.5">
                                <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                                        :class="['px-3 py-1.5 rounded-lg text-sm font-semibold transition-all hover:scale-105 focus:outline-none', currentPage === 1 ? 'bg-slate-800/30 text-slate-600 cursor-not-allowed' : 'bg-slate-800/50 hover:bg-slate-700/50 text-white']">
                                    {{ t('previous') }}
                                </button>

                                <div class="flex items-center gap-1.5">
                                    <button v-for="page in visiblePages" :key="page" @click="goToPage(page)"
                                            :class="['px-2.5 py-1.5 rounded-lg text-sm font-semibold transition-all hover:scale-105 focus:outline-none']"
                                            :style="page === currentPage ? `background: ${themeColors.primary}; color: #0f172a;` : 'background: rgba(71, 85, 105, 0.5); color: #e2e8f0;'">
                                        {{ page }}
                                    </button>
                                </div>

                                <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                                        :class="['px-3 py-1.5 rounded-lg text-sm font-semibold transition-all hover:scale-105 focus:outline-none', currentPage === totalPages ? 'bg-slate-800/30 text-slate-600 cursor-not-allowed' : 'bg-slate-800/50 hover:bg-slate-700/50 text-white']">
                                    {{ t('next') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
                    enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showModal"
                 class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                 @click.self="closeModal">
                <Transition enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                    <div v-if="showModal"
                         class="bg-gradient-to-br from-slate-900/95 to-slate-800/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                         role="dialog" aria-modal="true">
                        <div
                            class="px-4 sm:px-5 lg:px-6 py-4 sm:py-5 border-b border-slate-700/50 sticky top-0 bg-gradient-to-br from-slate-900/98 to-slate-800/98 backdrop-blur-xl z-10">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg sm:text-xl font-bold text-white">
                                    {{ t('transactionDetails') }}</h3>
                                <button
                                    class="p-2 transition-all duration-200 text-slate-300 hover:text-white rounded-lg hover:bg-slate-700/80 focus:outline-none focus:ring-2 focus:ring-white/20 flex-shrink-0"
                                    @click="closeModal" aria-label="Close modal">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="selectedTransaction" class="p-4 sm:p-5 lg:p-6 space-y-4">
                            <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                                <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                    <div class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0"
                                         :style="{ backgroundColor: themeColors.primary }"></div>
                                    {{ t('transactionInformation') }}
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs block text-slate-400 font-medium mb-1.5">{{
                                                t('transactionId')
                                            }}</label>
                                        <p class="font-mono text-sm sm:text-base font-semibold break-all"
                                           :style="{ color: themeColors.primary }">{{
                                                selectedTransaction.transaction_id
                                            }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs block text-slate-400 font-medium mb-1.5">{{
                                                t('status')
                                            }}</label>
                                        <div class="mt-0.5">
                                        <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full border"
                                              :style="getStatusStyle(selectedTransaction.status)">
                                            {{ formatStatus(selectedTransaction.status) }}
                                        </span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs block text-slate-400 font-medium mb-1.5">{{
                                                t('type')
                                            }}</label>
                                        <p class="font-semibold text-sm text-white capitalize">
                                            {{ formatTransactionType(selectedTransaction.type) }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs block text-slate-400 font-medium mb-1.5">{{
                                                t('date')
                                            }}</label>
                                        <p class="font-semibold text-sm text-white">
                                            {{ formatDateTime(selectedTransaction.created_at) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                                <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                    <div class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0"
                                         :style="{ backgroundColor: themeColors.primary }"></div>
                                    {{ t('amountDetails') }}
                                </h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center gap-3">
                                        <span class="text-slate-400 font-medium text-sm">{{
                                                t('amount')
                                            }}:</span>
                                        <span class="font-semibold text-base"
                                              :style="['credit', 'bonus', 'staking_reward', 'deposit'].includes(selectedTransaction.type) ? `color: ${themeColors.primary};` : 'color: #ef4444;'">
                                        {{
                                                ['credit', 'bonus', 'staking_reward', 'deposit'].includes(selectedTransaction.type) ? '+' : '-'
                                            }}{{ currencySymbol }}{{ formatCurrency(selectedTransaction.amount) }}
                                    </span>
                                    </div>
                                    <div class="flex justify-between items-center gap-3">
                                        <span class="text-slate-400 font-medium text-sm">{{
                                                t('fee')
                                            }}:</span>
                                        <span class="font-semibold text-base text-white">{{
                                                currencySymbol
                                            }}{{ formatCurrency(selectedTransaction.fee) }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between items-center border-t pt-3 border-slate-700/50 gap-3">
                                        <span class="font-semibold text-sm text-white">{{
                                                t('postBalance')
                                            }}:</span>
                                        <span class="font-bold text-lg sm:text-xl"
                                              :style="{ color: themeColors.primary }">{{
                                                currencySymbol
                                            }}{{ formatCurrency(selectedTransaction.post_balance) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedTransaction.details"
                                 class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                                <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                    <div class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0"
                                         :style="{ backgroundColor: themeColors.primary }"></div>
                                    {{ t('additionalDetails') }}
                                </h4>
                                <p class="text-sm font-medium text-slate-300 leading-relaxed">
                                    {{ selectedTransaction.details }}</p>
                            </div>
                        </div>

                        <div
                            class="flex justify-end p-4 sm:p-5 lg:p-6 border-t border-slate-700/50 bg-slate-900/50 sticky bottom-0 backdrop-blur-xl">
                            <button
                                class="py-2.5 px-5 rounded-lg font-semibold text-sm transition-all duration-200 hover:scale-105 bg-slate-800/80 hover:bg-slate-700/80 text-white focus:outline-none focus:ring-2 focus:ring-white/20"
                                @click="closeModal">
                                {{ t('close') }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </UserLayout>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';
import {usePage} from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import {useToast} from "@/composables/useToast.js";
import {useTranslation} from '@/composables/useTranslation';

const props = defineProps({
    earningsStats: {type: Object, required: true},
    transactions: {type: Array, default: () => []},
    monthlyBreakdown: {type: Object, required: true},
    quickStats: {type: Object, required: true},
    transactionSummary: {type: Object, required: true}
});

const {showToast} = useToast();
const {t} = useTranslation();
const page = usePage();

const filterType = ref('all');
const currentPage = ref(1);
const itemsPerPage = 10;
const showModal = ref(false);
const selectedTransaction = ref(null);

const primaryColor = computed(() => page.props.primaryColor || '#86efac');
const currencySymbol = computed(() => page.props.currencySymbol || '$');

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') return {r: 134, g: 239, b: 172};
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : {r: 134, g: 239, b: 172};
};

const rgbToHex = (r, g, b) => "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);

const adjustColor = (hex, amount) => {
    const rgb = hexToRgb(hex);
    return rgbToHex(
        Math.min(255, Math.max(0, rgb.r + amount)),
        Math.min(255, Math.max(0, rgb.g + amount)),
        Math.min(255, Math.max(0, rgb.b + amount))
    );
};

const themeColors = computed(() => ({
    primary: primaryColor.value,
    primaryDark: adjustColor(primaryColor.value, -20),
    primaryLight: adjustColor(primaryColor.value, 40),
    primaryRgb: `${hexToRgb(primaryColor.value).r}, ${hexToRgb(primaryColor.value).g}, ${hexToRgb(primaryColor.value).b}`
}));

const filteredTransactions = computed(() => {
    if (filterType.value === 'all') return props.transactions;
    return props.transactions.filter(t => t.type === filterType.value);
});

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredTransactions.value.slice(start, start + itemsPerPage);
});

const totalPages = computed(() => Math.ceil(filteredTransactions.value.length / itemsPerPage));

const visiblePages = computed(() => {
    const pages = [];
    for (let i = 1; i <= totalPages.value; i++) {
        if (i === 1 || i === totalPages.value || Math.abs(i - currentPage.value) <= 1) {
            pages.push(i);
        }
    }
    return pages;
});

const totalMonthlyEarnings = computed(() => {
    return parseFloat(props.monthlyBreakdown.investment || 0) +
        parseFloat(props.monthlyBreakdown.referral || 0) +
        parseFloat(props.monthlyBreakdown.staking || 0);
});

const getProgressPercentage = (value) => {
    const total = totalMonthlyEarnings.value;
    if (total === 0) return 0;

    const percentage = (parseFloat(value || 0) / total) * 100;
    return value > 0 ? Math.max(5, Math.min(100, percentage)) : 0;
};

const formatCurrency = (amount) => {
    const number = parseFloat(amount) || 0;
    return new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(number);
};

const formatDate = (dateString) => {
    if (!dateString) return t('notAvailable');
    try {
        return new Date(dateString).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
    } catch {
        return t('invalidDate');
    }
};

const formatTime = (dateString) => {
    if (!dateString) return t('notAvailable');
    try {
        return new Date(dateString).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'});
    } catch {
        return t('invalidTime');
    }
};

const formatDateTime = (dateString) => {
    if (!dateString) return t('notAvailable');
    try {
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return t('invalidDate');
    }
};

const formatTransactionType = (type) => {
    const typeMap = {
        'deposit': t('deposit'), 'withdrawal': t('withdrawal'), 'transfer': t('transfer'),
        'credit': t('credit'), 'debit': t('debit'), 'bonus': t('bonus'),
        'stake': t('stake'), 'unstake': t('unstake'), 'staking_reward': t('stakingReward')
    };
    return typeMap[type] || type.charAt(0).toUpperCase() + type.slice(1);
};

const formatStatus = (status) => {
    if (!status || typeof status !== 'string') return t('unknown');
    return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
};

const getStatusStyle = (status) => {
    if (!status) return {
        backgroundColor: 'rgba(148, 163, 184, 0.2)',
        borderColor: 'rgba(148, 163, 184, 0.3)',
        color: '#94a3b8'
    };

    switch (status.toLowerCase()) {
        case 'completed':
            return {
                backgroundColor: `rgba(${themeColors.value.primaryRgb}, 0.2)`,
                borderColor: `rgba(${themeColors.value.primaryRgb}, 0.3)`,
                color: themeColors.value.primary
            };
        case 'pending':
            return {
                backgroundColor: 'rgba(251, 191, 36, 0.2)',
                borderColor: 'rgba(251, 191, 36, 0.3)',
                color: '#fbbf24'
            };
        case 'failed':
        case 'cancelled':
            return {backgroundColor: 'rgba(239, 68, 68, 0.2)', borderColor: 'rgba(239, 68, 68, 0.3)', color: '#ef4444'};
        default:
            return {
                backgroundColor: 'rgba(148, 163, 184, 0.2)',
                borderColor: 'rgba(148, 163, 184, 0.3)',
                color: '#94a3b8'
            };
    }
};

const showTransactionDetails = (transaction) => {
    if (!transaction?.id) {
        showToast(t('invalidTransactionSelected'), 'error');
        return;
    }
    selectedTransaction.value = transaction;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => {
        selectedTransaction.value = null;
    }, 300);
};

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
};
const changeFilter = (filter) => {
    filterType.value = filter;
    currentPage.value = 1;
};

onMounted(() => {
    const flash = page.props.flash;
    if (flash?.success) showToast(flash.success, 'success');
    if (flash?.error) showToast(flash.error, 'error');
    if (flash?.warning) showToast(flash.warning, 'warning');
    if (flash?.info) showToast(flash.info, 'info');
});
</script>

<style scoped>
::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(30, 41, 59, 0.3);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: rgba(71, 85, 105, 0.5);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(71, 85, 105, 0.7);
}
</style>

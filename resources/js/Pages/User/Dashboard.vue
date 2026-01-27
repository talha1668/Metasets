<template>
    <UserLayout
        :page-title="t('dashboard')"
        :page-section="t('overview')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('investmentDashboard')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <section :aria-labelledby="t('portfolioStatistics')"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                    <h2 class="sr-only">{{ t('portfolioStatistics') }}</h2>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('portfolioBalance')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('portfolioBalance') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                           :aria-label="`${t('portfolioBalance')}: ${currencySymbol}${formatCurrency(wallet.balance)}`">
                            {{ currencySymbol }}{{ formatCurrency(wallet.balance) }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ wallet.dailyChange >= 0 ? '+' : '' }}{{ wallet.dailyChange }}%
                            </span>
                            <span class="text-xs font-medium text-slate-500">{{ t('last24h') }}</span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('totalInvested')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('totalInvested') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                            {{ currencySymbol }}{{ formatCurrency(wallet.total_invested) }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ activeInvestments.length }} {{ t('activePlans') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('totalEarnings')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('totalEarnings') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                            {{ currencySymbol }}{{ formatCurrency(wallet.total_earnings) }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ calculateROI() }}% {{ t('roi') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                        role="region" :aria-labelledby="t('referralIncome')" tabindex="0">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ t('referralIncome') }}</h3>
                        <p class="text-xl sm:text-2xl font-bold text-white mb-1.5">
                            {{ currencySymbol }}{{ formatCurrency(wallet.total_referral_bonus) }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                {{ user.total_referrals }} {{ t('referrals') }}
                            </span>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('chartsAnalytics')"
                         class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">
                    <h2 class="sr-only">{{ t('chartsAnalytics') }}</h2>
                    <div
                        class="xl:col-span-8 bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300"
                        role="region" :aria-labelledby="t('portfolioPerformance')">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-5 sm:mb-6">
                            <div class="flex-1">
                                <h3 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('portfolioPerformance') }}</h3>
                                <p class="text-sm font-medium text-slate-400">{{ t('realTimeTracking') }}</p>
                            </div>
                            <div class="flex items-center gap-2" role="group" :aria-label="t('chartTimeframe')">
                                <button
                                    v-for="timeframe in ['7D', '30D', '90D']"
                                    :key="timeframe"
                                    @click="changeTimeframe(timeframe)"
                                    :class="['px-3 py-1.5 rounded-lg text-sm font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900', selectedTimeframe === timeframe ? 'shadow-lg' : 'bg-slate-800/60 text-slate-400 hover:text-white hover:bg-slate-800']"
                                    :style="selectedTimeframe === timeframe ? `background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.25), rgba(${themeColors.primaryRgb}, 0.15)); color: ${themeColors.primary}; border: 1px solid rgba(${themeColors.primaryRgb}, 0.3);` : ''"
                                    :aria-pressed="selectedTimeframe === timeframe"
                                >
                                    {{ timeframe }}
                                </button>
                            </div>
                        </div>
                        <div class="h-56 sm:h-64 lg:h-72 w-full" role="img" :aria-label="t('portfolioChart')">
                            <canvas ref="portfolioChart" :aria-label="t('lineChart')"></canvas>
                        </div>
                    </div>

                    <div
                        class="xl:col-span-4 bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300"
                        role="region" :aria-labelledby="t('investmentDistribution')">
                        <div class="mb-5 sm:mb-6">
                            <h3 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('investmentDistribution') }}</h3>
                            <p class="text-sm font-medium text-slate-400">{{ t('assetAllocation') }}</p>
                        </div>
                        <div v-if="investmentDistribution.length"
                             class="h-40 sm:h-48 lg:h-52 w-full flex items-center justify-center mb-5" role="img"
                             :aria-label="t('distributionPieChart')">
                            <canvas ref="pieChart" :aria-label="t('doughnutChart')"></canvas>
                        </div>
                        <div v-else class="h-40 sm:h-48 lg:h-52 flex items-center justify-center text-slate-400">
                            <div class="text-center">
                                <svg class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 text-slate-600" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                                </svg>
                                <p class="text-sm font-medium">{{ t('noDistributionData') }}</p>
                            </div>
                        </div>
                        <div v-if="investmentDistribution.length" class="space-y-2 sm:space-y-3" role="list"
                             :aria-label="t('distributionLegend')">
                            <div v-for="item in investmentDistribution" :key="item.name"
                                 class="flex items-center justify-between p-2.5 bg-slate-800/40 rounded-lg hover:bg-slate-800/60 transition-colors"
                                 role="listitem">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                         :style="{ backgroundColor: item.color }"></div>
                                    <span class="text-sm font-medium text-white truncate">{{ item.name }}</span>
                                </div>
                                <span class="text-sm font-semibold text-slate-300 ml-2">{{ item.value }}%</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('investmentsActions')"
                         class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
                    <h2 class="sr-only">{{ t('investmentsActions') }}</h2>
                    <div
                        class="lg:col-span-2 bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300"
                        role="region" :aria-labelledby="t('activeInvestmentPlans')">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 sm:mb-6">
                            <div class="flex-1">
                                <h3 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('activeInvestmentPlans') }}</h3>
                                <p class="text-sm font-medium text-slate-400">{{ t('monitorInvestments') }}</p>
                            </div>
                            <Link
                                href="/user/investments"
                                class="inline-flex items-center text-sm font-semibold px-3 py-1.5 rounded-lg hover:opacity-90 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`color: ${themeColors.primary}; background: rgba(${themeColors.primaryRgb}, 0.1); border: 1px solid rgba(${themeColors.primaryRgb}, 0.3);`"
                            >
                                {{ t('viewAllPlans') }}
                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </Link>
                        </div>

                        <div v-if="activeInvestments.length" class="space-y-3 sm:space-y-4" role="list">
                            <div
                                v-for="(investment, index) in activeInvestments"
                                :key="investment.id"
                                class="bg-slate-800/40 rounded-lg p-4 sm:p-5 border border-slate-700/30 hover:bg-slate-800/60 hover:border-slate-600/50 transition-all duration-300"
                                role="listitem"
                                tabindex="0"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-white text-base mb-0.5 truncate">{{ investment.plan_name }}</h4>
                                        <p class="text-sm font-medium text-slate-400 truncate">{{ investment.investment_id }}</p>
                                    </div>
                                    <div class="text-left sm:text-right flex-shrink-0">
                                        <p class="text-xl font-bold text-white mb-0.5">
                                            {{ currencySymbol }}{{ formatCurrency(investment.amount) }}
                                        </p>
                                        <p class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                            {{ investment.interest_rate }}% {{ t('daily') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-5">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between text-sm mb-2">
                                            <span class="text-slate-400 font-medium">{{ t('investmentProgress') }}</span>
                                            <span class="text-white font-semibold">
                                                {{ Math.round((investment.returns_paid / investment.total_returns_expected) * 100) }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-700/50 rounded-full h-2.5 overflow-hidden" role="progressbar">
                                            <div
                                                class="h-full rounded-full transition-all duration-1000"
                                                :style="`width: ${Math.round((investment.returns_paid / investment.total_returns_expected) * 100)}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark})`"
                                            ></div>
                                        </div>
                                    </div>
                                    <div class="text-left sm:text-right flex-shrink-0">
                                        <p class="text-lg font-bold" :style="{ color: themeColors.primary }">
                                            {{ currencySymbol }}{{ formatCurrency(investment.earned_amount) }}
                                        </p>
                                        <p class="text-sm font-medium text-slate-400">{{ t('totalEarned') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 sm:py-16">
                            <svg class="w-14 h-14 sm:w-16 sm:h-16 mx-auto mb-3 text-slate-600" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            <p class="text-sm font-medium text-slate-400">{{ t('noActiveInvestments') }}</p>
                        </div>
                    </div>

                    <div class="space-y-5 sm:space-y-6">
                        <div
                            class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300"
                            role="region" :aria-labelledby="t('quickActions')">
                            <h3 class="text-lg sm:text-xl font-bold text-white mb-4 sm:mb-5">{{ t('quickActions') }}</h3>
                            <nav class="space-y-3" :aria-label="t('quickActionLinks')">
                                <Link
                                    href="/user/finance"
                                    class="w-full p-4 rounded-lg text-left hover:scale-[1.02] transition-all duration-300 group block focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 border"
                                    :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.08)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform"
                                            :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                            <svg class="w-5 h-5" :style="{ color: themeColors.primary }" fill="none"
                                                 stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-white text-sm mb-0.5">{{ t('makeDeposit') }}</p>
                                            <p class="text-xs font-medium text-slate-400">{{ t('addFunds') }}</p>
                                        </div>
                                    </div>
                                </Link>

                                <Link
                                    href="/user/investments"
                                    class="w-full p-4 rounded-lg text-left hover:scale-[1.02] transition-all duration-300 group block focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 border"
                                    :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.08)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform"
                                            :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                            <svg class="w-5 h-5" :style="{ color: themeColors.primary }" fill="none"
                                                 stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-white text-sm mb-0.5">{{ t('newInvestment') }}</p>
                                            <p class="text-xs font-medium text-slate-400">{{ t('browsePlans') }}</p>
                                        </div>
                                    </div>
                                </Link>

                                <Link
                                    href="/user/referral-level"
                                    class="w-full p-4 rounded-lg text-left hover:scale-[1.02] transition-all duration-300 group block focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 border"
                                    :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.08)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform"
                                            :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                            <svg class="w-5 h-5" :style="{ color: themeColors.primary }" fill="none"
                                                 stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-white text-sm mb-0.5">{{ t('referralLevel') }}</p>
                                            <p class="text-xs font-medium text-slate-400">{{ t('checkYourLevel') }}</p>
                                        </div>
                                    </div>
                                </Link>
                            </nav>
                        </div>

                        <div
                            class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300"
                            role="region" :aria-labelledby="t('aiInsights')">
                            <div class="flex items-center justify-between mb-4 sm:mb-5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                                         :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                        <svg class="w-4 h-4" :style="{ color: themeColors.primary }" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg sm:text-xl font-bold text-white">{{ t('aiInsights') }}</h3>
                                </div>
                                <Link
                                    href="/user/ai-recommendations"
                                    class="inline-flex items-center text-sm font-semibold px-3 py-1.5 rounded-lg hover:opacity-90 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`color: ${themeColors.primary}; background: rgba(${themeColors.primaryRgb}, 0.1); border: 1px solid rgba(${themeColors.primaryRgb}, 0.3);`"
                                >
                                    {{ t('viewAll') }}
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </Link>
                            </div>
                            <div class="space-y-3" role="list">
                                <div
                                    v-for="rec in aiRecommendations"
                                    :key="rec.id"
                                    class="p-4 rounded-lg hover:scale-[1.02] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 border"
                                    :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.08)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`"
                                    role="listitem"
                                    tabindex="0"
                                >
                                    <div class="flex items-start justify-between mb-3">
                                        <h4 class="font-bold text-white text-sm flex-1 mr-2">{{ rec.title }}</h4>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-md flex-shrink-0"
                                              :style="`background-color: rgba(${themeColors.primaryRgb}, 0.25); color: ${themeColors.primary};`">
                                            {{ rec.confidence_score }}%
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-slate-300 mb-3 leading-relaxed">{{ rec.description }}</p>
                                    <Link
                                        :href="`/user/ai-recommendations/${rec.id}`"
                                        class="text-sm font-semibold hover:opacity-80 transition-opacity focus:outline-none inline-flex items-center gap-1"
                                        :style="{ color: themeColors.primary }"
                                    >
                                        {{ t('learnMore') }}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('transactionsReferrals')"
                         class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
                    <h2 class="sr-only">{{ t('transactionsReferrals') }}</h2>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300"
                        role="region" :aria-labelledby="t('recentTransactions')">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 sm:mb-6">
                            <div class="flex-1">
                                <h3 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('recentTransactions') }}</h3>
                                <p class="text-sm font-medium text-slate-400">{{ t('latestActivity') }}</p>
                            </div>
                            <Link
                                href="/user/earnings"
                                class="inline-flex items-center text-sm font-semibold px-3 py-1.5 rounded-lg hover:opacity-90 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`color: ${themeColors.primary}; background: rgba(${themeColors.primaryRgb}, 0.1); border: 1px solid rgba(${themeColors.primaryRgb}, 0.3);`"
                            >
                                {{ t('viewAllHistory') }}
                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </Link>
                        </div>
                        <div v-if="recentTransactions.length" class="space-y-3" role="list">
                            <div
                                v-for="transaction in recentTransactions.slice(0, 4)"
                                :key="transaction.id"
                                class="flex items-center justify-between p-4 bg-slate-800/40 rounded-lg hover:bg-slate-800/60 hover:scale-[1.01] transition-all duration-300 border border-slate-700/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                role="listitem"
                                tabindex="0"
                            >
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                         :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`">
                                        <svg class="w-5 h-5" :style="{ color: themeColors.primary }" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path v-if="transaction.type === 'deposit'" stroke-linecap="round"
                                                  stroke-linejoin="round" stroke-width="2"
                                                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            <path v-else-if="transaction.type === 'withdrawal'" stroke-linecap="round"
                                                  stroke-linejoin="round" stroke-width="2"
                                                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            <path v-else-if="transaction.type === 'credit'" stroke-linecap="round"
                                                  stroke-linejoin="round" stroke-width="2"
                                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-white text-sm truncate">{{ formatTransactionType(transaction.type) }}</p>
                                        <p class="text-xs font-medium text-slate-400 truncate">{{ formatDate(transaction.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 ml-2">
                                    <p class="font-bold text-base" :style="{ color: themeColors.primary }">
                                        {{ transaction.type === 'deposit' || transaction.type === 'credit' ? '+' : '-' }}{{ currencySymbol }}{{ formatCurrency(transaction.amount) }}
                                    </p>
                                    <p class="text-xs font-medium text-slate-400 capitalize">{{ transaction.status }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 sm:py-16">
                            <svg class="w-14 h-14 sm:w-16 sm:h-16 mx-auto mb-3 text-slate-600" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm font-medium text-slate-400">{{ t('noTransactionsYet') }}</p>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300"
                        role="region" :aria-labelledby="t('referralNetwork')">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 sm:mb-6">
                            <div class="flex-1">
                                <h3 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('referralNetwork') }}</h3>
                                <p class="text-sm font-medium text-slate-400">{{ t('networkPerformance') }}</p>
                            </div>
                            <Link
                                href="/user/referral-network"
                                class="inline-flex items-center text-sm font-semibold px-3 py-1.5 rounded-lg hover:opacity-90 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`color: ${themeColors.primary}; background: rgba(${themeColors.primaryRgb}, 0.1); border: 1px solid rgba(${themeColors.primaryRgb}, 0.3);`"
                            >
                                {{ t('manageNetwork') }}
                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </Link>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-5 sm:mb-6">
                            <div
                                class="text-center p-4 sm:p-5 border rounded-lg hover:scale-[1.02] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.08)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`"
                                tabindex="0">
                                <p class="text-2xl sm:text-3xl font-bold text-white mb-1.5">{{ referralStats.totalReferrals }}</p>
                                <p class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{ t('totalReferrals') }}</p>
                            </div>
                            <div
                                class="text-center p-4 sm:p-5 border rounded-lg hover:scale-[1.02] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.08)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`"
                                tabindex="0">
                                <p class="text-2xl sm:text-3xl font-bold text-white mb-1.5">{{ referralStats.activeReferrals }}</p>
                                <p class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{ t('activeMembers') }}</p>
                            </div>
                        </div>

                        <div class="mb-5 sm:mb-6">
                            <div class="flex justify-between text-sm mb-3">
                                <span class="text-slate-400 font-medium">{{ t('commissionEarned') }}</span>
                                <span class="text-white font-semibold">{{ currencySymbol }}{{ formatCurrency(referralStats.totalEarned) }}</span>
                            </div>
                            <div class="w-full bg-slate-700/50 rounded-full h-2.5 overflow-hidden mb-1.5" role="progressbar">
                                <div class="h-full rounded-full transition-all duration-1000"
                                     :style="`width: ${Math.min((referralStats.totalEarned / 20000) * 100, 100)}%; background: linear-gradient(90deg, ${themeColors.primary}, ${themeColors.primaryDark})`"></div>
                            </div>
                            <div class="flex justify-between text-xs font-medium text-slate-500">
                                <span>{{ currencySymbol }}0</span>
                                <span>{{ currencySymbol }}20K {{ t('goal') }}</span>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5 rounded-lg border"
                             :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.08)); border-color: rgba(${themeColors.primaryRgb}, 0.3);`">
                            <p class="text-sm font-semibold text-white mb-3">{{ t('yourReferralCode') }}</p>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                                <code
                                    class="flex-1 p-3 bg-slate-800/60 rounded-lg text-sm font-mono font-bold break-all"
                                    :style="{ color: themeColors.primary }"
                                    tabindex="0">{{ user.referral_code }}</code>
                                <button
                                    class="p-3 rounded-lg hover:opacity-80 transition-all duration-300 group focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`background-color: rgba(${themeColors.primaryRgb}, 0.25);`"
                                    @click="copyReferralCode">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform"
                                         :style="{ color: themeColors.primary }"
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import {ref, computed, onMounted, onUnmounted} from 'vue';
import {usePage, Link} from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import {useToast} from "@/composables/useToast.js";
import {useTranslation} from '@/composables/useTranslation';
import {Chart, registerables} from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    wallet: {
        type: Object,
        required: true,
        validator: (value) => value && typeof value === 'object'
    },
    user: {
        type: Object,
        required: true,
        validator: (value) => value && typeof value === 'object'
    },
    activeInvestments: {
        type: Array,
        default: () => [],
        validator: (value) => Array.isArray(value)
    },
    recentTransactions: {
        type: Array,
        default: () => [],
        validator: (value) => Array.isArray(value)
    },
    aiRecommendations: {
        type: Array,
        default: () => [],
        validator: (value) => Array.isArray(value)
    },
    portfolioChartData: {
        type: Object,
        default: () => ({'7D': [], '30D': [], '90D': []}),
        validator: (value) => typeof value === 'object'
    },
    investmentDistribution: {
        type: Array,
        default: () => [],
        validator: (value) => Array.isArray(value)
    },
    referralStats: {
        type: Object,
        default: () => ({
            totalReferrals: 0,
            activeReferrals: 0,
            totalEarned: 0,
            pendingCommission: 0,
            thisMonthEarnings: 0,
            monthlyGrowth: 0,
            teamInvestment: 0,
        })
    }
});

const {showToast} = useToast();
const {t} = useTranslation();
const page = usePage();

const portfolioChart = ref(null);
const pieChart = ref(null);
let portfolioChartInstance = null;
let pieChartInstance = null;

const isLoadingChart = ref(true);
const selectedTimeframe = ref('7D');

const primaryColor = computed(() => page.props.primaryColor || '#86efac');
const currencySymbol = computed(() => page.props.currencySymbol || '$');

const calculateGrowth = () => {
    return props.referralStats?.monthlyGrowth?.toFixed(2) || 0;
};

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

const initPortfolioChart = () => {
    try {
        if (!portfolioChart.value) {
            return;
        }

        const ctx = portfolioChart.value.getContext('2d');
        if (!ctx) {
            throw new Error('Failed to get canvas context');
        }

        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, `rgba(${themeColors.value.primaryRgb}, 0.3)`);
        gradient.addColorStop(1, `rgba(${themeColors.value.primaryRgb}, 0.05)`);

        const currentData = props.portfolioChartData[selectedTimeframe.value] || [];

        portfolioChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: currentData.map(item => item.date),
                datasets: [{
                    label: t('portfolioValue'),
                    data: currentData.map(item => item.value),
                    borderColor: themeColors.value.primary,
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: themeColors.value.primary,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {display: false},
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        borderColor: 'rgba(148, 163, 184, 0.2)',
                        borderWidth: 1,
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        cornerRadius: 12,
                        displayColors: false,
                        callbacks: {
                            label: function (context) {
                                return `${t('portfolioValue')}: ${currencySymbol.value}${formatNumber(context.parsed.y)}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {display: false},
                        ticks: {color: '#64748b', font: {size: 12}},
                        border: {display: false}
                    },
                    y: {
                        grid: {color: 'rgba(148, 163, 184, 0.1)', borderDash: [5, 5]},
                        ticks: {
                            color: '#64748b',
                            font: {size: 12},
                            callback: function (value) {
                                return `${currencySymbol.value}${(value / 1000).toFixed(0)}K`;
                            }
                        },
                        border: {display: false}
                    }
                },
                interaction: {intersect: false, mode: 'index'}
            }
        });
    } catch (error) {
        console.error('Failed to initialize portfolio chart:', error);
        showToast(t('chartInitializationFailed') || 'Failed to load chart', 'error');
    }
};

const initPieChart = () => {
    try {
        if (!pieChart.value || !props.investmentDistribution.length) {
            return;
        }

        const ctx = pieChart.value.getContext('2d');
        if (!ctx) {
            throw new Error('Failed to get canvas context');
        }

        pieChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: props.investmentDistribution.map(item => item.name),
                datasets: [{
                    data: props.investmentDistribution.map(item => item.value),
                    backgroundColor: props.investmentDistribution.map(item => item.color),
                    borderWidth: 0,
                    cutout: '60%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {display: false},
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        borderColor: 'rgba(148, 163, 184, 0.2)',
                        borderWidth: 1,
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        cornerRadius: 12,
                        displayColors: true,
                        callbacks: {
                            label: function (context) {
                                return `${context.label}: ${context.parsed}%`;
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Failed to initialize pie chart:', error);
        showToast(t('chartInitializationFailed') || 'Failed to load chart', 'error');
    }
};

const updatePortfolioChart = () => {
    try {
        if (!portfolioChartInstance || !portfolioChart.value) {
            return;
        }

        const currentData = props.portfolioChartData[selectedTimeframe.value] || [];
        const ctx = portfolioChart.value.getContext('2d');

        if (!ctx) {
            throw new Error('Failed to get canvas context');
        }

        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, `rgba(${themeColors.value.primaryRgb}, 0.3)`);
        gradient.addColorStop(1, `rgba(${themeColors.value.primaryRgb}, 0.05)`);

        portfolioChartInstance.data.labels = currentData.map(item => item.date);
        portfolioChartInstance.data.datasets[0].data = currentData.map(item => item.value);
        portfolioChartInstance.data.datasets[0].backgroundColor = gradient;
        portfolioChartInstance.data.datasets[0].borderColor = themeColors.value.primary;
        portfolioChartInstance.data.datasets[0].pointBackgroundColor = themeColors.value.primary;
        portfolioChartInstance.update('active');
    } catch (error) {
        console.error('Failed to update portfolio chart:', error);
    }
};

const changeTimeframe = (timeframe) => {
    selectedTimeframe.value = timeframe;
    updatePortfolioChart();
};

const copyReferralCode = async () => {
    try {
        if (!props.user?.referral_code) {
            showToast(t('noReferralCode') || 'No referral code available', 'error');
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(props.user.referral_code);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = props.user.referral_code;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        showToast(t('referralCodeCopied') || 'Referral code copied!', 'success');
    } catch (err) {
        console.error('Failed to copy referral code:', err);
        showToast(t('failedToCopy') || 'Failed to copy code', 'error');
    }
};

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
        return (number / 1000000000).toFixed(2) + 'B';
    } else if (number >= 1000000) {
        return (number / 1000000).toFixed(2) + 'M';
    } else if (number >= 1000) {
        return (number / 1000).toFixed(2) + 'K';
    } else if (Number.isInteger(number)) {
        return number.toString();
    } else {
        return number.toFixed(2);
    }
};

const formatDate = (dateString) => {
    if (!dateString) return t('notAvailable') || 'N/A';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return t('invalidDate') || 'Invalid date';
        }
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        console.error('Date formatting error:', error);
        return t('invalidDate') || 'Invalid date';
    }
};

const formatTransactionType = (type) => {
    if (!type) return '';

    const typeMap = {
        'deposit': t('deposit') || 'Deposit',
        'withdrawal': t('withdrawal') || 'Withdrawal',
        'credit': t('credit') || 'Credit',
        'debit': t('debit') || 'Debit',
        'transfer': t('transfer') || 'Transfer',
        'payment': t('payment') || 'Payment',
        'refund': t('refund') || 'Refund'
    };
    return typeMap[type.toLowerCase()] || type.charAt(0).toUpperCase() + type.slice(1);
};

const calculateROI = () => {
    if (!props.wallet?.total_invested || props.wallet.total_invested === 0) {
        return 0;
    }
    const earnings = parseFloat(props.wallet.total_earnings) || 0;
    const invested = parseFloat(props.wallet.total_invested);
    return Math.round((earnings / invested) * 100);
};

onMounted(() => {
    try {
        setTimeout(() => {
            initPortfolioChart();
            initPieChart();
            isLoadingChart.value = false;
        }, 100);
    } catch (error) {
        console.error('Component mount error:', error);
        isLoadingChart.value = false;
    }
});

onUnmounted(() => {
    try {
        if (portfolioChartInstance) {
            portfolioChartInstance.destroy();
            portfolioChartInstance = null;
        }
        if (pieChartInstance) {
            pieChartInstance.destroy();
            pieChartInstance = null;
        }
    } catch (error) {
        console.error('Chart cleanup error:', error);
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
    .transition-all,
    .animate-pulse {
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

@keyframes progressBar {
    from {
        width: 0;
    }
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

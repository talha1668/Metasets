<template>
    <section class="mt-5 sm:mt-6 animate-fade-in animation-delay-800">
        <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl overflow-hidden hover:border-slate-600/50 transition-all duration-300 shadow-2xl">
            <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-700/30">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex-1">
                        <h2 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('stakingHistory') }}</h2>
                        <p class="text-sm font-medium text-slate-400">{{ t('monitorStakingPositions') }}</p>
                    </div>
                    <div class="text-sm font-medium text-slate-400">
                        {{ logs?.total || filteredLogs.length }} {{ t('totalStakes') }}
                    </div>
                </div>
            </div>

            <div class="px-5 sm:px-6 py-3 sm:py-4 border-b border-slate-700/30 bg-slate-800/30">
                <div class="flex flex-wrap gap-1.5 sm:gap-2">
                    <button
                        v-for="filter in filters"
                        :key="filter.value"
                        @click="activeFilter = filter.value"
                        :class="['px-2.5 sm:px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900']"
                        :style="{ backgroundColor: activeFilter === filter.value ? themeColors.primary : 'rgba(71, 85, 105, 0.5)', color: activeFilter === filter.value ? '#0f172a' : '#e2e8f0', '--tw-ring-color': `rgba(${themeColors.primaryRgb}, 0.5)` }"
                        :aria-pressed="activeFilter === filter.value"
                        :aria-label="`${t('filterBy')} ${filter.label}`"
                    >
                        {{ filter.label }}
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <div class="block lg:hidden">
                    <div v-if="!filteredLogs || filteredLogs.length === 0" class="text-center py-12 sm:py-16 px-4">
                        <div class="flex flex-col items-center">
                            <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                            </svg>
                            <p class="text-base font-semibold text-slate-300">{{ t('noStakesFound') }}</p>
                            <p class="text-sm font-medium text-slate-400 mt-1">{{ t('startStakingToSeeActivities') }}</p>
                        </div>
                    </div>

                    <div v-for="log in filteredLogs" :key="log.id" class="border-b border-slate-700/30 last:border-b-0">
                        <div class="p-4 sm:p-5 space-y-3 hover:bg-slate-800/30 transition-colors">
                            <div class="flex justify-between items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold text-white truncate flex items-center gap-2">
                                        {{ log.pool_name }}
                                        <span v-if="log.auto_compound" class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-purple-400/20 text-purple-400 border border-purple-400/30">
                                            AUTO
                                        </span>
                                    </div>
                                    <div class="text-xs font-medium text-slate-400 mt-0.5">{{ formatDate(log.staked_at) }}</div>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border flex-shrink-0" :style="{ backgroundColor: getStatusBadgeColor(log.status) + '20', borderColor: getStatusBadgeColor(log.status) + '30', color: getStatusBadgeColor(log.status) }">
                                    {{ formatStatus(log.status) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-800/40 rounded-lg p-3">
                                    <div class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">{{ t('staked') }}</div>
                                    <div class="text-base font-bold text-white">{{ currencySymbol }}{{ formatNumber(log.stake_amount) }}</div>
                                </div>
                                <div class="rounded-lg p-3 border" :style="{ background: `linear-gradient(to bottom right, rgba(${themeColors.primaryRgb}, 0.1), rgba(${themeColors.primaryRgb}, 0.05))`, borderColor: `rgba(${themeColors.primaryRgb}, 0.2)` }">
                                    <div class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">{{ t('current') }}</div>
                                    <div class="text-base font-bold" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatNumber(log.current_balance) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-800/40 rounded-lg p-3">
                                    <div class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">{{ t('rewards') }}</div>
                                    <div class="text-base font-bold" :style="{ color: themeColors.primaryLight }">{{ currencySymbol }}{{ formatNumber(log.total_rewards) }}</div>
                                </div>
                                <div class="bg-slate-800/40 rounded-lg p-3">
                                    <div class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">{{ t('apy') }}</div>
                                    <div class="text-base font-bold text-purple-400">{{ log.apy_rate }}%</div>
                                </div>
                            </div>

                            <div v-if="log.status === 'active' && log.unlock_at" class="bg-slate-800/40 rounded-lg p-3">
                                <div class="flex justify-between text-xs mb-2">
                                    <span class="text-slate-300 font-semibold">{{ isUnlocked(log.unlock_at) ? t('unlocked') : t('lockPeriod') }}</span>
                                    <span class="text-white font-semibold">{{ calculateLockProgress(log) }}%</span>
                                </div>
                                <div class="relative w-full bg-slate-700/50 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000" :style="`width: ${calculateLockProgress(log)}%; background: linear-gradient(to right, ${themeColors.primary}, ${themeColors.primaryLight});`"></div>
                                </div>
                            </div>

                            <div class="pt-1">
                                <button class="w-full text-sm font-semibold transition-all duration-200 px-4 py-2.5 rounded-lg hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900" :style="{ color: themeColors.primary, backgroundColor: `rgba(${themeColors.primaryRgb}, 0.1)`, border: `1px solid rgba(${themeColors.primaryRgb}, 0.3)`, '--tw-ring-color': `rgba(${themeColors.primaryRgb}, 0.5)` }" @click="showStakeDetails(log)">
                                    {{ t('viewDetails') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <table class="w-full hidden lg:table">
                    <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">{{ t('pool') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">{{ t('staked') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">{{ t('current') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">{{ t('rewards') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">{{ t('apy') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">{{ t('lockPeriod') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">{{ t('status') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">{{ t('date') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">{{ t('actions') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/30">
                    <tr v-if="!filteredLogs || filteredLogs.length === 0">
                        <td colspan="9" class="px-5 py-12 sm:py-16 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                                </svg>
                                <p class="text-base font-semibold text-slate-300">{{ t('noStakesFound') }}</p>
                                <p class="text-sm font-medium text-slate-400 mt-1">{{ t('startStakingToSeeActivities') }}</p>
                            </div>
                        </td>
                    </tr>

                    <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-slate-800/30 transition-colors">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-white flex items-center gap-2">
                                {{ log.pool_name }}
                                <span v-if="log.auto_compound" class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-purple-400/20 text-purple-400 border border-purple-400/30">AUTO</span>
                            </div>
                            <div class="text-xs font-medium text-slate-400 mt-0.5">ID: {{ log.stake_id }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-white">{{ currencySymbol }}{{ formatNumber(log.stake_amount) }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatNumber(log.current_balance) }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold" :style="{ color: themeColors.primaryLight }">{{ currencySymbol }}{{ formatNumber(log.total_rewards) }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-purple-400">{{ log.apy_rate }}%</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div v-if="log.unlock_at" class="flex items-center gap-2">
                                <div class="flex-1 max-w-[100px]">
                                    <div class="relative w-full bg-slate-700/50 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-1000" :style="`width: ${calculateLockProgress(log)}%; background: linear-gradient(to right, ${themeColors.primary}, ${themeColors.primaryLight});`"></div>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold" :style="{ color: isUnlocked(log.unlock_at) ? themeColors.primary : '#ffffff' }">
                                        {{ isUnlocked(log.unlock_at) ? t('unlocked') : calculateLockProgress(log) + '%' }}
                                    </span>
                            </div>
                            <div v-else class="text-xs font-medium text-slate-400">{{ t('flexible') }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border" :style="{ backgroundColor: getStatusBadgeColor(log.status) + '20', borderColor: getStatusBadgeColor(log.status) + '30', color: getStatusBadgeColor(log.status) }">
                                    {{ formatStatus(log.status) }}
                                </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-sm">
                            <div class="text-slate-300 font-medium">{{ formatDate(log.staked_at) }}</div>
                            <div class="text-xs font-medium text-slate-400">{{ formatTime(log.staked_at) }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-sm">
                            <button class="font-semibold transition-colors duration-200 hover:opacity-80 cursor-pointer focus:outline-none" :style="{ color: themeColors.primary }" @click="showStakeDetails(log)">
                                {{ t('viewDetails') }}
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="logs?.data && logs.data.length > 0 && logs.links" class="px-5 sm:px-6 py-4 sm:py-5 border-t border-slate-700/30">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-sm font-medium text-slate-400">
                        {{ t('showingResults', { from: logs.from || 0, to: logs.to || 0, total: logs.total || 0 }) }}
                    </div>
                    <div class="flex flex-wrap justify-center sm:justify-end gap-1.5">
                        <button
                            v-for="(link, index) in logs.links"
                            :key="index"
                            :disabled="!link.url"
                            :class="['px-3 py-1.5 text-sm font-semibold rounded-lg transition-all hover:scale-105 focus:outline-none', link.active ? 'cursor-default' : link.url ? 'hover:opacity-80' : 'cursor-not-allowed opacity-50']"
                            :style="{ backgroundColor: link.active ? themeColors.primary : link.url ? 'rgba(71, 85, 105, 0.5)' : 'rgba(71, 85, 105, 0.3)', color: link.active ? '#0f172a' : link.url ? '#e2e8f0' : '#64748b' }"
                            @click="goToPage(link.url)"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="closeModal">
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="showModal" class="bg-gradient-to-br from-slate-900/95 to-slate-800/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" role="dialog" aria-modal="true">
                        <div class="px-4 sm:px-5 lg:px-6 py-4 sm:py-5 border-b border-slate-700/50 sticky top-0 bg-gradient-to-br from-slate-900/98 to-slate-800/98 backdrop-blur-xl z-10">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg sm:text-xl font-bold text-white">{{ t('stakeDetails') }}</h3>
                                <button
                                    class="p-2 transition-all duration-200 text-slate-300 hover:text-white rounded-lg hover:bg-slate-700/80 focus:outline-none focus:ring-2 focus:ring-white/20 flex-shrink-0"
                                    @click="closeModal"
                                    aria-label="Close modal"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="selectedStake" class="p-4 sm:p-5 lg:p-6 space-y-4">
                            <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                                <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                    <div class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" :style="{ backgroundColor: themeColors.primary }"></div>
                                    {{ t('stakeInformation') }}
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs block text-slate-400 font-medium mb-1.5">{{ t('stakeId') }}</label>
                                        <p class="font-mono text-sm sm:text-base font-semibold break-all" :style="{ color: themeColors.primary }">{{ selectedStake.stake_id }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs block text-slate-400 font-medium mb-1.5">{{ t('status') }}</label>
                                        <div class="mt-0.5">
                                            <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full border" :style="{ backgroundColor: getStatusBadgeColor(selectedStake.status) + '20', borderColor: getStatusBadgeColor(selectedStake.status) + '30', color: getStatusBadgeColor(selectedStake.status) }">
                                                {{ formatStatus(selectedStake.status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs block text-slate-400 font-medium mb-1.5">{{ t('pool') }}</label>
                                        <p class="font-semibold text-sm text-white">{{ selectedStake.pool_name }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs block text-slate-400 font-medium mb-1.5">{{ t('apyRate') }}</label>
                                        <p class="font-semibold text-sm text-purple-400">{{ selectedStake.apy_rate }}%</p>
                                    </div>
                                    <div v-if="selectedStake.auto_compound">
                                        <label class="text-xs block text-slate-400 font-medium mb-1.5">{{ t('compounding') }}</label>
                                        <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded bg-purple-400/20 text-purple-400 border border-purple-400/30">
                                            {{ t('autoCompound') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                                <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                    <div class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" :style="{ backgroundColor: themeColors.primary }"></div>
                                    {{ t('amountDetails') }}
                                </h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center gap-3">
                                        <span class="text-slate-400 font-medium text-sm">{{ t('stakedAmount') }}:</span>
                                        <span class="font-semibold text-base text-white">{{ currencySymbol }}{{ formatNumber(selectedStake.stake_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center gap-3">
                                        <span class="text-slate-400 font-medium text-sm">{{ t('currentBalance') }}:</span>
                                        <span class="font-semibold text-base" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatNumber(selectedStake.current_balance) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center gap-3">
                                        <span class="text-slate-400 font-medium text-sm">{{ t('totalRewards') }}:</span>
                                        <span class="font-semibold text-base" :style="{ color: themeColors.primaryLight }">{{ currencySymbol }}{{ formatNumber(selectedStake.total_rewards) }}</span>
                                    </div>
                                    <div v-if="selectedStake.pending_rewards" class="flex justify-between items-center gap-3">
                                        <span class="text-slate-400 font-medium text-sm">{{ t('pendingRewards') }}:</span>
                                        <span class="font-semibold text-base text-purple-400">{{ currencySymbol }}{{ formatNumber(selectedStake.pending_rewards) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center border-t pt-3 border-slate-700/50 gap-3">
                                        <span class="font-semibold text-sm text-white">{{ t('totalValue') }}:</span>
                                        <span class="font-bold text-lg sm:text-xl" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatNumber(selectedStake.current_balance) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedStake.unlock_at" class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                                <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                    <div class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" :style="{ backgroundColor: themeColors.primary }"></div>
                                    {{ t('lockProgress') }}
                                </h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-xs mb-2">
                                        <span class="text-slate-300 font-semibold">{{ isUnlocked(selectedStake.unlock_at) ? t('unlocked') : t('lockPeriod') }}</span>
                                        <span class="text-white font-semibold">{{ calculateLockProgress(selectedStake) }}%</span>
                                    </div>
                                    <div class="relative w-full bg-slate-700/50 rounded-full h-2.5 sm:h-3 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-1000" :style="`width: ${calculateLockProgress(selectedStake)}%; background: linear-gradient(to right, ${themeColors.primary}, ${themeColors.primaryLight});`"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                                <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                    <div class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" :style="{ backgroundColor: themeColors.primary }"></div>
                                    {{ t('timeline') }}
                                </h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center gap-3">
                                        <span class="text-slate-400 font-medium text-sm">{{ t('staked') }}:</span>
                                        <span class="font-semibold text-sm text-white text-right">{{ formatDateTime(selectedStake.staked_at) }}</span>
                                    </div>
                                    <div v-if="selectedStake.unlock_at" class="flex justify-between items-center gap-3">
                                        <span class="text-slate-400 font-medium text-sm">{{ t('unlockDate') }}:</span>
                                        <span class="font-semibold text-sm text-right" :style="{ color: isUnlocked(selectedStake.unlock_at) ? themeColors.primary : '#ffffff' }">{{ formatDateTime(selectedStake.unlock_at) }}</span>
                                    </div>
                                    <div v-if="selectedStake.last_claim_at" class="flex justify-between items-center gap-3">
                                        <span class="text-slate-400 font-medium text-sm">{{ t('lastClaim') }}:</span>
                                        <span class="font-semibold text-sm text-white text-right">{{ formatDateTime(selectedStake.last_claim_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="selectedStake.status === 'active'" class="flex flex-col sm:flex-row gap-2.5">
                                <button
                                    v-if="isUnlocked(selectedStake.unlock_at)"
                                    @click="handleUnstake(selectedStake)"
                                    class="flex-1 py-2.5 px-5 rounded-lg font-semibold text-sm transition-all duration-200 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 bg-red-500/20 border border-red-500/30 text-red-400"
                                >
                                    {{ t('unstake') }}
                                </button>
                                <button
                                    v-if="selectedStake.pending_rewards && selectedStake.pending_rewards > 0"
                                    @click="handleClaimRewards(selectedStake)"
                                    class="flex-1 py-2.5 px-5 rounded-lg font-semibold text-sm transition-all duration-200 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 bg-purple-400/20 border border-purple-400/30 text-purple-400"
                                >
                                    {{ t('claimRewards') }}
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end p-4 sm:p-5 lg:p-6 border-t border-slate-700/50 bg-slate-900/50 sticky bottom-0 backdrop-blur-xl">
                            <button class="py-2.5 px-5 rounded-lg font-semibold text-sm transition-all duration-200 hover:scale-105 bg-slate-800/80 hover:bg-slate-700/80 text-white focus:outline-none focus:ring-2 focus:ring-white/20" @click="closeModal">
                                {{ t('close') }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </section>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    logs: {
        type: [Array, Object],
        default: () => []
    }
})

const page = usePage()
const { t } = useTranslation()
const { success, error } = useToast()
const activeFilter = ref('all')
const showModal = ref(false)
const selectedStake = ref(null)

const primaryColor = computed(() => page.props.primaryColor || '#86efac')

const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 }
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

const currencySymbol = computed(() => page.props.currencySymbol || '$')

const filters = computed(() => [
    { label: t('all'), value: 'all' },
    { label: t('active'), value: 'active' },
    { label: t('completed'), value: 'completed' },
    { label: t('cancelled'), value: 'cancelled' }
])

const logsData = computed(() => {
    if (props.logs && typeof props.logs === 'object' && props.logs.data) {
        return props.logs.data
    }
    return Array.isArray(props.logs) ? props.logs : []
})

const filteredLogs = computed(() => {
    if (activeFilter.value === 'all') {
        return logsData.value
    }
    return logsData.value.filter(log => log.status === activeFilter.value)
})

const formatNumber = (num) => {
    if (num === null || num === undefined || num === '') return '0.00'
    const number = parseFloat(num)
    if (isNaN(number)) return '0.00'
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number)
}

const formatDate = (dateString) => {
    if (!dateString) return t('notAvailable')
    try {
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return t('invalidDate')
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        })
    } catch (error) {
        return t('invalidDate')
    }
}

const formatTime = (dateString) => {
    if (!dateString) return ''
    try {
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return ''
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        })
    } catch (error) {
        return ''
    }
}

const formatDateTime = (dateString) => {
    if (!dateString) return t('notAvailable')
    try {
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return t('invalidDate')
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    } catch (error) {
        return t('invalidDate')
    }
}

const formatStatus = (status) => {
    if (!status || typeof status !== 'string') return t('unknown')
    return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase()
}

const calculateLockProgress = (log) => {
    if (!log.unlock_at || !log.staked_at) return 0
    const now = new Date()
    const stakedDate = new Date(log.staked_at)
    const unlockDate = new Date(log.unlock_at)
    const totalDuration = unlockDate - stakedDate
    const elapsed = now - stakedDate
    return Math.min(Math.round((elapsed / totalDuration) * 100), 100)
}

const isUnlocked = (unlockDate) => {
    if (!unlockDate) return true
    return new Date() >= new Date(unlockDate)
}

const getStatusBadgeColor = (status) => {
    if (!status || typeof status !== 'string') return '#94a3b8'
    switch (status.toLowerCase()) {
        case 'active': return themeColors.value.primary
        case 'completed': return '#3b82f6'
        case 'cancelled': return '#ef4444'
        default: return '#94a3b8'
    }
}

const showStakeDetails = (stake) => {
    if (!stake?.id) {
        error(t('invalidStakeSelected'))
        return
    }
    selectedStake.value = stake
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    setTimeout(() => { selectedStake.value = null }, 300)
}

const handleUnstake = (stake) => {
    if (!confirm(t('confirmUnstake'))) return

    closeModal()

    router.post(`/user/unstake/${stake.id}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('unstakeSuccessful'))
        },
        onError: (errors) => {
            error(errors.error || errors.message || t('unstakeFailed'))
        }
    })
}

const handleClaimRewards = (stake) => {
    closeModal()

    router.post(`/user/claim-rewards/${stake.id}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('rewardsClaimedSuccessfully'))
        },
        onError: (errors) => {
            error(errors.error || errors.message || t('claimRewardsFailed'))
        }
    })
}

const goToPage = (url) => {
    if (!url || typeof url !== 'string') return
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        onError: () => error(t('failedToLoadPage'))
    })
}

const handleKeydown = (event) => {
    if (event.key === 'Escape' && showModal.value) {
        closeModal()
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
input:focus,
select:focus,
button:focus {
    outline: none;
    box-shadow: 0 0 0 2px v-bind('`rgba(${themeColors.primaryRgb}, 0.2)`');
}
</style>

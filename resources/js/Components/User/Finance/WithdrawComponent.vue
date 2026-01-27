<template>
    <div class="space-y-6">
        <section :aria-labelledby="t('withdrawalStatistics')" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
            <h2 class="sr-only">{{ t('withdrawalStatistics') }}</h2>
            <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                 role="region" :aria-labelledby="t('availableBalance')" tabindex="0">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ t('availableBalance') }}</h3>
                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5" :aria-label="`${t('availableBalance')}: ${currencySymbol}${formatNumber(walletBalance)}`">
                    {{ currencySymbol }}{{ formatNumber(walletBalance) }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-semibold" :style="{ color: themeColors.primary }" :aria-label="t('canWithdraw')">
                        {{ t('canWithdraw') }}
                    </span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                 role="region" :aria-labelledby="t('totalWithdrawals')" tabindex="0">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ t('totalWithdrawals') }}</h3>
                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5" :aria-label="`${t('totalWithdrawals')}: ${withdrawals?.total || 0}`">
                    {{ withdrawals?.total || 0 }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{ t('allTime') }}</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                 role="region" :aria-labelledby="t('successfulWithdrawals')" tabindex="0">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ t('successfulWithdrawals') }}</h3>
                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5" :aria-label="`${t('successfulWithdrawals')}: ${successfulWithdrawals}`">
                    {{ successfulWithdrawals }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{ t('completed') }}</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                 role="region" :aria-labelledby="t('pendingWithdrawals')" tabindex="0">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ t('pendingWithdrawals') }}</h3>
                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5" :aria-label="`${t('pendingWithdrawals')}: ${pendingWithdrawals}`">
                    {{ pendingWithdrawals }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-semibold text-yellow-400">{{ t('processing') }}</span>
                </div>
            </div>
        </section>

        <section :aria-labelledby="t('withdrawalForm')" class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">
            <h2 class="sr-only">{{ t('withdrawalForm') }}</h2>
            <div class="xl:col-span-8 space-y-5">
                <div class="bg-gradient-to-br from-slate-900/70 to-slate-800/50 backdrop-blur-xl border border-slate-700/30 rounded-2xl p-5 shadow-2xl"
                     role="region" :aria-labelledby="t('selectWithdrawalMethod')">
                    <div class="mb-5 sm:mb-6">
                        <h3 class="text-lg sm:text-xl font-bold text-white mb-1.5">{{ t('selectWithdrawalMethod') }}</h3>
                        <p class="text-sm font-medium text-slate-400">{{ t('chooseGateway') }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-5 sm:mb-6">
                        <div
                            v-for="gateway in withdrawalGateways"
                            :key="gateway.id"
                            class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border rounded-lg sm:rounded-xl p-4 sm:p-5 cursor-pointer transition-all duration-300 hover:scale-[1.02] hover:shadow-lg hover:shadow-slate-900/20 group"
                            :class="selectedGateway?.id === gateway.id ? 'shadow-xl' : ''"
                            :style="{
                                borderColor: selectedGateway?.id === gateway.id ? `rgba(${themeColors.primaryRgb}, 0.5)` : 'rgba(51, 65, 85, 0.4)',
                                backgroundColor: selectedGateway?.id === gateway.id ? `rgba(${themeColors.primaryRgb}, 0.05)` : ''
                            }"
                            @click="selectGateway(gateway)"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition-transform duration-300 group-hover:scale-110"
                                     :style="{ background: `linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})` }">
                                    <span class="font-bold text-slate-900 text-xs">{{ gateway.name.substring(0, 2).toUpperCase() }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-white text-sm mb-1 group-hover:opacity-90 transition-opacity">{{ gateway.name }}</h4>
                                    <div class="flex items-center gap-1.5">
                                        <div class="text-xs font-semibold px-2 py-0.5 rounded-md"
                                             :style="{
                                                 color: themeColors.primary,
                                                 backgroundColor: `rgba(${themeColors.primaryRgb}, 0.15)`
                                             }">
                                            {{ gateway.fixed_charge > 0 ? currencySymbol + formatNumber(gateway.fixed_charge) + ' + ' : '' }}{{ formatNumber(gateway.percent_charge) }}% {{ t('fee') }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="selectedGateway?.id === gateway.id"
                                     class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center"
                                     :style="{
                                         backgroundColor: `rgba(${themeColors.primaryRgb}, 0.2)`,
                                         color: themeColors.primary
                                     }">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedGateway && selectedGateway.description"
                         class="rounded-lg p-4 sm:p-5 border mb-5 sm:mb-6"
                         :style="{ borderColor: `rgba(${themeColors.primaryRgb}, 0.3)`, background: `linear-gradient(to right, rgba(${themeColors.primaryRgb}, 0.1), rgba(${themeColors.primaryRgb}, 0.05))` }">
                        <h4 class="font-bold text-white text-sm sm:text-base mb-4 sm:mb-5">{{ t('withdrawalInstructions') }}</h4>
                        <div class="text-sm font-medium text-slate-300 leading-relaxed" v-html="selectedGateway.description"></div>
                    </div>

                    <div v-if="selectedGateway" class="border-t border-slate-700/30 pt-5 sm:pt-6">
                        <form class="space-y-4 sm:space-y-5" @submit.prevent="submitWithdrawal">
                            <div>
                                <label class="block font-semibold text-white text-sm mb-2">
                                    {{ t('withdrawalAmount') }} ({{ selectedGateway.currency }})
                                </label>
                                <input
                                    v-model.number="withdrawalForm.amount"
                                    type="number"
                                    step="0.01"
                                    :min="selectedGateway.min_amount"
                                    :max="Math.min(selectedGateway.max_amount, availableBalance)"
                                    class="w-full px-4 py-3 border border-slate-700/50 rounded-lg text-sm font-medium text-white bg-slate-800/50 transition-all focus:border-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="{ '--tw-ring-color': `rgba(${themeColors.primaryRgb}, 0.3)` }"
                                    :placeholder="t('enterAmount')"
                                    required
                                >
                                <div class="flex items-center justify-between mt-1.5 text-xs">
                                    <span class="text-slate-400 font-medium">
                                        {{ t('min') }}: {{ getGatewayCurrencySymbol(selectedGateway) }}{{ formatNumber(selectedGateway.min_amount) }} - {{ t('max') }}: {{ getGatewayCurrencySymbol(selectedGateway) }}{{ formatNumber(Math.min(selectedGateway.max_amount, availableBalance)) }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="selectedGateway.parameters && selectedGateway.parameters.length > 0" class="space-y-3 sm:space-y-4">
                                <h4 class="font-bold text-white text-sm sm:text-base">{{ t('withdrawalInformation') }}</h4>
                                <div v-for="param in selectedGateway.parameters" :key="param.field_name" class="space-y-1.5">
                                    <label class="block text-sm font-semibold text-slate-300">
                                        {{ param.field_label }}
                                        <span v-if="param.field_required" class="text-red-400 ml-1">*</span>
                                    </label>
                                    <input
                                        v-if="['text', 'email', 'tel', 'number', 'date', 'datetime-local'].includes(param.field_type)"
                                        v-model="withdrawalDetails[param.field_name]"
                                        :type="param.field_type"
                                        :placeholder="param.field_placeholder"
                                        :required="param.field_required"
                                        class="w-full px-4 py-3 border border-slate-700/50 rounded-lg text-sm font-medium text-white bg-slate-800/50 focus:border-slate-600 focus:outline-none transition-colors"
                                    >
                                    <select
                                        v-else-if="param.field_type === 'select'"
                                        v-model="withdrawalDetails[param.field_name]"
                                        :required="param.field_required"
                                        class="w-full px-4 py-3 border border-slate-700/50 rounded-lg text-sm font-medium text-white bg-slate-800/50 focus:border-slate-600 focus:outline-none transition-colors"
                                    >
                                        <option value="">{{ param.field_placeholder || t('selectOption') }}</option>
                                        <option v-for="(label, value) in param.field_options" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                    <input
                                        v-else-if="param.field_type === 'file'"
                                        type="file"
                                        :required="param.field_required"
                                        accept="image/*,.pdf"
                                        class="w-full px-4 py-3 border border-slate-700/50 rounded-lg text-sm font-medium text-white bg-slate-800/50 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:cursor-pointer hover:file:opacity-80 transition-all"
                                        :style="{ 'file:backgroundColor': `rgba(${themeColors.primaryRgb}, 0.2)`, 'file:color': themeColors.primary }"
                                        @change="handleFileUpload($event, param.field_name)"
                                    >
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="!canSubmit || isProcessing"
                                class="w-full py-2.5 sm:py-3 px-5 rounded-lg font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 shadow-lg hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="{
                                    background: canSubmit ? `linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})` : '#1e293b',
                                    color: canSubmit ? '#0f172a' : '#64748b',
                                    '--tw-ring-color': `rgba(${themeColors.primaryRgb}, 0.5)`
                                }"
                            >
                                <span v-if="isProcessing" class="flex items-center justify-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    {{ t('processing') }}...
                                </span>
                                <span v-else>{{ t('submitWithdrawalRequest') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-4 space-y-5">
                <div v-if="selectedGateway && isValidAmount"
                     class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 transition-all duration-300"
                     role="region" :aria-labelledby="t('withdrawalSummary')">
                    <h3 class="text-sm sm:text-base font-bold text-white mb-4">{{ t('withdrawalSummary') }}</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium text-sm">{{ t('requestedAmount') }}:</span>
                            <span class="font-semibold text-white text-base">{{ getGatewayCurrencySymbol(selectedGateway) }}{{ formatNumber(withdrawalForm.amount) }}</span>
                        </div>
                        <div v-if="calculatedCharge > 0" class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium text-sm">{{ t('processingFee') }}:</span>
                            <span class="font-semibold text-yellow-400">{{ getGatewayCurrencySymbol(selectedGateway) }}{{ formatNumber(calculatedCharge) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-slate-700/50 pt-3">
                            <span class="font-semibold text-white text-sm">{{ t('youWillReceive') }}:</span>
                            <span class="font-bold text-lg sm:text-xl" :style="{ color: themeColors.primary }">{{ getGatewayCurrencySymbol(selectedGateway) }}{{ formatNumber(finalAmount) }}</span>
                        </div>
                        <div class="rounded-lg p-4 mt-4" :style="{ background: `linear-gradient(to right, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.05))`, borderColor: 'rgba(239, 68, 68, 0.3)' }">
                            <div class="text-xs text-slate-400 font-medium mb-1.5">{{ t('deductedFromWallet') }}:</div>
                            <div class="font-bold text-lg sm:text-xl text-red-300">{{ currencySymbol }}{{ formatNumber(withdrawalForm.amount) }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedGateway"
                     class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <div v-if="selectedGateway.file" class="mb-5 flex justify-center">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden bg-slate-800/50 p-3 flex items-center justify-center border border-slate-700/30">
                            <img :src="`/assets/files/${selectedGateway.file}`"
                                 :alt="selectedGateway.name"
                                 class="w-full h-full object-contain"
                                 @error="$event.target.style.display='none'">
                        </div>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold text-white mb-3 text-center">{{ selectedGateway.name }}</h3>
                </div>

                <div v-if="selectedGateway"
                     class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <h3 class="text-base sm:text-lg font-bold text-white mb-5">{{ t('gatewayDetails') }}</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium">{{ t('gateway') }}:</span>
                            <span class="font-semibold text-white">{{ selectedGateway.name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium">{{ t('currency') }}:</span>
                            <span class="font-semibold text-white">{{ selectedGateway.currency }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium">{{ t('type') }}:</span>
                            <span class="font-semibold text-white capitalize">{{ selectedGateway.type }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium">{{ t('processing') }}:</span>
                            <span class="font-semibold" :style="{ color: themeColors.primary }">{{ t('instantManual') }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <h3 class="text-base sm:text-lg font-bold text-white mb-5">{{ t('quickTips') }}</h3>
                    <div class="space-y-3 sm:space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" :style="{ background: `linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})` }">
                                <svg class="w-4 h-4 text-slate-900" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-white mb-0.5">{{ t('verifyAccountInfo') }}</p>
                                <p class="text-xs font-medium text-slate-400">{{ t('doubleCheckWithdrawalDetails') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section :aria-labelledby="t('withdrawalHistory')" class="space-y-5">
            <h2 class="sr-only">{{ t('withdrawalHistory') }}</h2>
            <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl overflow-hidden hover:border-slate-600/50 transition-all duration-300">
                <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-700/30">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex-1">
                            <h3 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('withdrawalHistory') }}</h3>
                            <p class="text-sm text-slate-400">{{ t('trackYourWithdrawals') }}</p>
                        </div>
                        <div class="text-sm text-slate-400">
                            {{ withdrawals?.total || 0 }} {{ t('totalTransactions') }}
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <div class="block lg:hidden">
                        <div v-if="!withdrawals?.data || withdrawals.data.length === 0" class="text-center py-12 sm:py-16 px-4">
                            <div class="flex flex-col items-center">
                                <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="text-base font-medium text-slate-300">{{ t('noWithdrawalsYet') }}</p>
                                <p class="text-sm text-slate-400 mt-1">{{ t('makeYourFirstWithdrawal') }}</p>
                            </div>
                        </div>

                        <div v-for="withdrawal in withdrawals?.data" :key="withdrawal.id" class="border-b border-slate-700/30 last:border-b-0">
                            <div class="p-4 sm:p-5 space-y-3 hover:bg-slate-800/30 transition-colors">
                                <div class="flex justify-between items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-bold font-mono text-white truncate">{{ withdrawal.trx }}</div>
                                        <div class="text-xs text-slate-400 mt-0.5">{{ formatDate(withdrawal.created_at) }}</div>
                                    </div>
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border flex-shrink-0" :style="{ backgroundColor: getStatusBadgeColor(withdrawal.status) + '20', borderColor: getStatusBadgeColor(withdrawal.status) + '30', color: getStatusBadgeColor(withdrawal.status) }">
                                        {{ formatStatus(withdrawal.status) }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-slate-800/40 rounded-lg p-3">
                                        <div class="text-xs uppercase font-bold tracking-wide text-slate-400 mb-1.5">{{ t('requested') }}</div>
                                        <div class="text-base font-black text-white">{{ currencySymbol }}{{ formatNumber(withdrawal.amount) }}</div>
                                        <div v-if="withdrawal.charge > 0" class="text-xs text-yellow-400 mt-1 font-semibold">
                                            -{{ currencySymbol }}{{ formatNumber(withdrawal.charge) }} {{ t('fee') }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg p-3 border" :style="{ background: `linear-gradient(to bottom right, rgba(${themeColors.primaryRgb}, 0.1), rgba(${themeColors.primaryRgb}, 0.05))`, borderColor: `rgba(${themeColors.primaryRgb}, 0.2)` }">
                                        <div class="text-xs uppercase font-bold tracking-wide text-slate-400 mb-1.5">{{ t('youReceive') }}</div>
                                        <div class="text-base font-black" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatNumber(withdrawal.final_amount) }}</div>
                                        <div class="text-xs text-slate-400 mt-1 font-semibold">{{ t('transferred') }}</div>
                                    </div>
                                </div>

                                <div class="bg-slate-800/40 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs uppercase font-bold tracking-wide text-slate-400 mb-1">{{ t('gateway') }}</div>
                                            <div class="text-sm font-bold text-slate-300 truncate">{{ withdrawal.withdrawal_gateway?.name || t('notAvailable') }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-1 flex gap-2">
                                    <button v-if="withdrawal.status === 'pending'" class="text-sm font-bold transition-all duration-200 px-3 py-2.5 rounded-lg hover:scale-[1.02] bg-red-500/20 text-red-400 border border-red-400/30" @click="showCancelConfirmation(withdrawal)">
                                        {{ t('cancel') }}
                                    </button>
                                    <button class="flex-1 text-sm font-bold transition-all duration-200 px-4 py-2.5 rounded-lg hover:scale-[1.02]" :style="{ color: themeColors.primary, backgroundColor: `rgba(${themeColors.primaryRgb}, 0.1)`, border: `1px solid rgba(${themeColors.primaryRgb}, 0.3)` }" @click="showWithdrawalDetails(withdrawal)">
                                        {{ t('viewDetails') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="w-full hidden lg:table">
                        <thead class="bg-slate-800/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-300">{{ t('trxId') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-300">{{ t('requested') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-300">{{ t('youReceive') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-300">{{ t('gateway') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-300">{{ t('status') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-300">{{ t('date') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-300">{{ t('actions') }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/30">
                        <tr v-if="!withdrawals?.data || withdrawals.data.length === 0">
                            <td colspan="7" class="px-5 py-12 sm:py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <p class="text-base font-bold text-slate-300">{{ t('noWithdrawalsYet') }}</p>
                                    <p class="text-sm text-slate-400 mt-1">{{ t('makeYourFirstWithdrawal') }}</p>
                                </div>
                            </td>
                        </tr>

                        <tr v-for="withdrawal in withdrawals?.data" :key="withdrawal.id" class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold font-mono text-white">{{ withdrawal.trx }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-white">{{ currencySymbol }}{{ formatNumber(withdrawal.amount) }}</div>
                                <div v-if="withdrawal.charge > 0" class="text-xs text-yellow-400 font-semibold">
                                    -{{ currencySymbol }}{{ formatNumber(withdrawal.charge) }} {{ t('fee') }}
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatNumber(withdrawal.final_amount) }}</div>
                                <div class="text-xs text-slate-400 font-medium">{{ t('transferred') }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-300">{{ withdrawal.withdrawal_gateway?.name || t('notAvailable') }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full border" :style="{ backgroundColor: getStatusBadgeColor(withdrawal.status) + '20', borderColor: getStatusBadgeColor(withdrawal.status) + '30', color: getStatusBadgeColor(withdrawal.status) }">
                                    {{ formatStatus(withdrawal.status) }}
                                </span>
                                <div v-if="withdrawal.status === 'pending'" class="mt-1.5">
                                    <button class="text-xs font-bold text-red-400 hover:text-red-300 transition-colors" @click="showCancelConfirmation(withdrawal)">{{ t('cancel') }}</button>
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                <div class="text-slate-300 font-medium">{{ formatDate(withdrawal.created_at) }}</div>
                                <div class="text-xs text-slate-400">{{ formatTime(withdrawal.created_at) }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                <button class="font-bold transition-colors duration-200 hover:opacity-80 cursor-pointer"
                                        :style="{ color: themeColors.primary }"
                                        @click="showWithdrawalDetails(withdrawal)">
                                    {{ t('viewDetails') }}
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="withdrawals?.data && withdrawals.data.length > 0 && withdrawals.links" class="px-5 sm:px-6 py-4 sm:py-5 border-t border-slate-700/30">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-sm text-slate-400">
                            {{ t('showingResults', { from: withdrawals.from || 0, to: withdrawals.to || 0, total: withdrawals.total || 0 }) }}
                        </div>
                        <div class="flex flex-wrap justify-center sm:justify-end gap-1.5">
                            <button v-for="(link, index) in withdrawals.links" :key="index" :disabled="!link.url" :class="['px-3 py-1.5 text-sm font-bold rounded-lg transition-all hover:scale-105', link.active ? 'cursor-default' : link.url ? 'hover:opacity-80' : 'cursor-not-allowed opacity-50']" :style="{ backgroundColor: link.active ? themeColors.primary : link.url ? 'rgba(71, 85, 105, 0.5)' : 'rgba(71, 85, 105, 0.3)', color: link.active ? '#0f172a' : link.url ? '#e2e8f0' : '#64748b' }" @click="goToPage(link.url)" v-html="link.label" />
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
                <div v-if="showModal" class="bg-gradient-to-br from-slate-900/95 to-slate-800/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape="closeModal">
                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-700/50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg sm:text-xl font-bold text-white">{{ t('withdrawalDetails') }}</h3>
                            <button class="p-2 transition-colors text-slate-400 hover:text-white rounded-lg hover:bg-slate-800/50" @click="closeModal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="selectedWithdrawal" class="p-4 sm:p-5 lg:p-6 space-y-4">
                        <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5" :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('transactionInformation') }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs block text-slate-400 font-semibold mb-1.5">{{ t('transactionId') }}</label>
                                    <p class="font-mono text-sm sm:text-base font-bold" :style="{ color: themeColors.primary }">{{ selectedWithdrawal.trx }}</p>
                                </div>
                                <div>
                                    <label class="text-xs block text-slate-400 font-semibold mb-1.5">{{ t('status') }}</label>
                                    <div class="mt-0.5">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full border" :style="{ backgroundColor: getStatusBadgeColor(selectedWithdrawal.status) + '20', borderColor: getStatusBadgeColor(selectedWithdrawal.status) + '30', color: getStatusBadgeColor(selectedWithdrawal.status) }">
                                            {{ formatStatus(selectedWithdrawal.status) }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs block text-slate-400 font-semibold mb-1.5">{{ t('withdrawalGateway') }}</label>
                                    <p class="font-bold text-sm text-white">{{ selectedWithdrawal.withdrawal_gateway?.name || t('notAvailable') }}</p>
                                </div>
                                <div>
                                    <label class="text-xs block text-slate-400 font-semibold mb-1.5">{{ t('currency') }}</label>
                                    <p class="font-bold text-sm text-white">{{ selectedWithdrawal.currency || 'USD' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5" :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('amountDetails') }}
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('requestedAmount') }}:</span>
                                    <span class="font-bold text-base text-white">{{ currencySymbol }}{{ formatNumber(selectedWithdrawal.amount) }}</span>
                                </div>
                                <div v-if="selectedWithdrawal.charge > 0" class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('processingFee') }}:</span>
                                    <span class="font-bold text-yellow-400">{{ currencySymbol }}{{ formatNumber(selectedWithdrawal.charge) }}</span>
                                </div>
                                <div class="flex justify-between items-center border-t pt-3 border-slate-700/50">
                                    <span class="font-bold text-sm text-white">{{ t('youReceived') }}:</span>
                                    <span class="font-black text-lg sm:text-xl" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatNumber(selectedWithdrawal.final_amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedWithdrawal.user_data && Object.keys(selectedWithdrawal.user_data).length > 0" class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5" :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('withdrawalDetails') }}
                            </h4>
                            <div class="space-y-3">
                                <div v-for="(value, key) in selectedWithdrawal.user_data" :key="key" class="flex justify-between items-start">
                                    <span class="capitalize min-w-[140px] text-slate-400 font-semibold text-sm">{{ formatFieldName(key) }}:</span>
                                    <span class="font-bold break-all text-right flex-1 ml-3 text-white text-sm">{{ value || t('notAvailable') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5" :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('timeline') }}
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('created') }}:</span>
                                    <span class="font-bold text-sm text-white">{{ formatDateTime(selectedWithdrawal.created_at) }}</span>
                                </div>
                                <div v-if="selectedWithdrawal.approved_at" class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('approved') }}:</span>
                                    <span class="font-bold text-sm" :style="{ color: themeColors.primary }">{{ formatDateTime(selectedWithdrawal.approved_at) }}</span>
                                </div>
                                <div v-if="selectedWithdrawal.rejected_at" class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('rejected') }}:</span>
                                    <span class="font-bold text-sm text-red-400">{{ formatDateTime(selectedWithdrawal.rejected_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedWithdrawal.admin_response" class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-3 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5" :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('adminResponse') }}
                            </h4>
                            <p class="leading-relaxed text-slate-300 text-sm">{{ selectedWithdrawal.admin_response }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end p-4 sm:p-5 lg:p-6 border-t border-slate-700/50">
                        <button class="py-2.5 px-5 rounded-lg font-bold text-sm transition-all duration-200 hover:scale-105 bg-slate-800/80 hover:bg-slate-700/80 text-white" @click="closeModal">
                            {{ t('close') }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>

    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showCancelModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="closeCancelModal">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="showCancelModal" class="bg-gradient-to-br from-slate-900/95 to-slate-800/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl max-w-md w-full" role="dialog" aria-modal="true">
                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-700/50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg sm:text-xl font-bold text-white">{{ t('cancelWithdrawal') }}</h3>
                            <button class="p-2 transition-colors text-slate-400 hover:text-white rounded-lg hover:bg-slate-800/50" @click="closeCancelModal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="withdrawalToCancel" class="p-5 sm:p-6">
                        <div class="text-center mb-5 sm:mb-6">
                            <div class="mx-auto flex items-center justify-center h-14 w-14 sm:h-16 sm:w-16 rounded-full bg-red-500/20 mb-4 sm:mb-5">
                                <svg class="h-7 w-7 sm:h-8 sm:w-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <h4 class="text-base sm:text-lg font-bold text-white mb-1.5">{{ t('cancelWithdrawalRequest') }}</h4>
                            <p class="text-sm text-slate-400">{{ t('cancelWithdrawalConfirmation') }}</p>
                        </div>

                        <div class="rounded-lg p-4 sm:p-5 mb-5 sm:mb-6 bg-slate-800/50 border border-slate-700/50">
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-400 font-semibold">{{ t('transactionId') }}:</span>
                                    <span class="font-mono text-sm font-bold text-white">{{ withdrawalToCancel.trx }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-400 font-semibold">{{ t('amount') }}:</span>
                                    <span class="font-bold text-white">{{ currencySymbol }}{{ formatNumber(withdrawalToCancel.amount) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-400 font-semibold">{{ t('gateway') }}:</span>
                                    <span class="font-bold text-white">{{ withdrawalToCancel.withdrawal_gateway?.name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg p-4 sm:p-5 mb-5 sm:mb-6 border" :style="{ background: `linear-gradient(to right, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.05))`, borderColor: `rgba(${themeColors.primaryRgb}, 0.3)` }">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2.5 sm:mr-3 flex-shrink-0" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-bold text-white">
                                    <strong class="font-black" :style="{ color: themeColors.primary }">{{ currencySymbol }}{{ formatNumber(withdrawalToCancel.amount) }}</strong> {{ t('willBeRefunded') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2.5 p-5 sm:p-6 border-t border-slate-700/50">
                        <button class="py-2.5 px-5 rounded-lg font-bold text-sm transition-all duration-200 hover:scale-105 bg-slate-800/80 hover:bg-slate-700/80 text-white" @click="closeCancelModal">
                            {{ t('keepWithdrawal') }}
                        </button>
                        <button :disabled="isCancelling" class="bg-red-600 text-white py-2.5 px-5 rounded-lg font-bold text-sm hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:scale-105 flex items-center justify-center" @click="confirmCancelWithdrawal">
                            <svg v-if="isCancelling" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            {{ isCancelling ? t('cancelling') : t('yesCancelWithdrawal') }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from "@/composables/useToast.js";
import { useTranslation } from '@/composables/useTranslation';

const props = defineProps({
    withdrawals: {
        type: Object,
        required: true,
        default: () => ({ data: [], total: 0, links: [] })
    },
    withdrawalGateways: {
        type: Array,
        required: true,
        default: () => []
    },
    walletBalance: {
        type: [Number, String],
        default: 0
    }
});

const MAX_FILE_SIZE = 5 * 1024 * 1024;
const ALLOWED_FILE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

const selectedGateway = ref(null);
const isProcessing = ref(false);
const withdrawalDetails = ref({});
const showModal = ref(false);
const selectedWithdrawal = ref(null);
const showCancelModal = ref(false);
const withdrawalToCancel = ref(null);
const isCancelling = ref(false);

const withdrawalForm = ref({
    amount: null,
    withdrawal_gateway_id: null
});

const { showToast } = useToast();
const { t } = useTranslation();
const page = usePage();

const primaryColor = computed(() => page.props.primaryColor || '#86efac');
const hexToRgb = (hex) => {
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

const currencySymbol = computed(() => page.props.currencySymbol || '$');

const availableBalance = computed(() => {
    const balance = parseFloat(props.walletBalance);
    return isNaN(balance) ? 0 : Math.max(0, balance);
});

const isValidAmount = computed(() => {
    if (!selectedGateway.value) return false;
    const amount = parseFloat(withdrawalForm.value.amount);
    if (isNaN(amount) || amount <= 0) return false;
    const minAmount = parseFloat(selectedGateway.value.min_amount || 0);
    const maxAmount = parseFloat(selectedGateway.value.max_amount || Number.MAX_SAFE_INTEGER);
    if (amount > availableBalance.value) return false;
    return amount >= minAmount && amount <= maxAmount;
});

const calculatedCharge = computed(() => {
    if (!selectedGateway.value || !isValidAmount.value) return 0;
    const amount = parseFloat(withdrawalForm.value.amount);
    if (isNaN(amount)) return 0;
    const fixedCharge = parseFloat(selectedGateway.value.fixed_charge || 0);
    const percentCharge = parseFloat(selectedGateway.value.percent_charge || 0);
    return fixedCharge + (amount * percentCharge / 100);
});

const finalAmount = computed(() => {
    if (!isValidAmount.value) return 0;
    const amount = parseFloat(withdrawalForm.value.amount);
    return Math.max(0, amount - calculatedCharge.value);
});

const canSubmit = computed(() => {
    if (!isValidAmount.value || isProcessing.value) return false;
    if (selectedGateway.value?.parameters) {
        const requiredFields = selectedGateway.value.parameters.filter(p => p.field_required);
        for (const field of requiredFields) {
            const value = withdrawalDetails.value[field.field_name];
            if (!value || (typeof value === 'string' && value.trim() === '')) return false;
        }
    }
    return true;
});

const successfulWithdrawals = computed(() => {
    if (!props.withdrawals?.data || !Array.isArray(props.withdrawals.data)) return 0;
    return props.withdrawals.data.filter(w => ['approved', 'completed', 'success'].includes(w.status?.toLowerCase())).length;
});

const pendingWithdrawals = computed(() => {
    if (!props.withdrawals?.data || !Array.isArray(props.withdrawals.data)) return 0;
    return props.withdrawals.data.filter(w => ['pending', 'processing'].includes(w.status?.toLowerCase())).length;
});

const getGatewayCurrencySymbol = (gateway) => {
    if (!gateway?.currency) return '$';
    const currencySymbols = {
        'USD': '$', 'EUR': '€', 'GBP': '£', 'JPY': '¥', 'BDT': '৳',
        'BTC': '₿', 'ETH': 'Ξ', 'INR': '₹', 'CAD': 'C$', 'AUD': 'A$'
    };
    return currencySymbols[gateway.currency] || gateway.currency + ' ';
};

const getStatusBadgeColor = (status) => {
    if (!status || typeof status !== 'string') return '#94a3b8';
    switch (status.toLowerCase()) {
        case 'approved':
        case 'completed':
        case 'success': return themeColors.value.primary;
        case 'pending':
        case 'processing': return '#fbbf24';
        case 'rejected':
        case 'failed':
        case 'cancelled': return '#ef4444';
        default: return '#94a3b8';
    }
};

const formatNumber = (num) => {
    if (num === null || num === undefined || num === '') return '0.00';
    const number = parseFloat(num);
    if (isNaN(number)) return '0.00';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8
    }).format(number);
};

const formatDate = (dateString) => {
    if (!dateString) return t('notAvailable');
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return t('invalidDate');
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    } catch (error) {
        return t('invalidDate');
    }
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return '';
    }
};

const formatDateTime = (dateString) => {
    if (!dateString) return t('notAvailable');
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return t('invalidDate');
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return t('invalidDate');
    }
};

const formatStatus = (status) => {
    if (!status || typeof status !== 'string') return t('unknown');
    return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
};

const formatFieldName = (fieldName) => {
    if (!fieldName || typeof fieldName !== 'string') return '';
    return fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const selectGateway = (gateway) => {
    if (!gateway?.id) {
        showToast(t('invalidWithdrawalGateway'), 'error');
        return;
    }
    selectedGateway.value = gateway;
    withdrawalForm.value.withdrawal_gateway_id = gateway.id;
    withdrawalForm.value.amount = null;
    withdrawalDetails.value = {};
};

const handleFileUpload = (event, fieldName) => {
    const file = event.target.files[0];
    if (!file) return;
    if (file.size > MAX_FILE_SIZE) {
        showToast(t('fileSizeTooLarge'), 'error');
        event.target.value = '';
        return;
    }
    if (!ALLOWED_FILE_TYPES.includes(file.type)) {
        showToast(t('invalidFileType'), 'error');
        event.target.value = '';
        return;
    }
    withdrawalDetails.value[fieldName] = file;
    showToast(t('fileSelectedSuccessfully'), 'success');
};

const submitWithdrawal = async () => {
    if (!canSubmit.value) {
        showToast(t('ensureRequiredFieldsFilled'), 'error');
        return;
    }

    isProcessing.value = true;

    try {
        const formData = new FormData();
        formData.append('withdrawal_gateway_id', withdrawalForm.value.withdrawal_gateway_id);
        formData.append('amount', withdrawalForm.value.amount);

        Object.keys(withdrawalDetails.value).forEach(key => {
            const value = withdrawalDetails.value[key];
            if (value instanceof File) {
                formData.append(`withdrawal_details[${key}]`, value);
            } else if (value !== null && value !== undefined && value !== '') {
                const sanitizedValue = String(value).trim();
                if (sanitizedValue) {
                    formData.append(`withdrawal_details[${key}]`, sanitizedValue);
                }
            }
        });

        router.post('/user/wallet/withdraw', formData, {
            onSuccess: () => {
                resetForm();
                showToast(t('withdrawalRequestSubmitted'), 'success');
            },
            onError: (errors) => {
                const errorMessages = Object.values(errors).flat();
                showToast(errorMessages[0] || t('withdrawalSubmissionFailed'), 'error');
            },
            preserveScroll: true,
            forceFormData: true
        });
    } catch (error) {
        showToast(t('withdrawalSubmissionFailed'), 'error');
    } finally {
        isProcessing.value = false;
    }
};

const showWithdrawalDetails = (withdrawal) => {
    if (!withdrawal?.id) {
        showToast(t('invalidWithdrawalSelected'), 'error');
        return;
    }
    selectedWithdrawal.value = withdrawal;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => { selectedWithdrawal.value = null; }, 300);
};

const showCancelConfirmation = (withdrawal) => {
    if (!withdrawal?.id) {
        showToast(t('invalidWithdrawalSelected'), 'error');
        return;
    }
    withdrawalToCancel.value = withdrawal;
    showCancelModal.value = true;
};

const closeCancelModal = () => {
    showCancelModal.value = false;
    setTimeout(() => {
        withdrawalToCancel.value = null;
        isCancelling.value = false;
    }, 300);
};

const confirmCancelWithdrawal = async () => {
    if (!withdrawalToCancel.value?.id) return;
    isCancelling.value = true;

    router.patch(`/user/wallet/withdraw/${withdrawalToCancel.value.id}/cancel`, {}, {
        onSuccess: () => {
            showToast(t('withdrawalCancelledSuccessfully'), 'success');
            closeCancelModal();
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).flat();
            showToast(errorMessages[0] || t('failedToCancelWithdrawal'), 'error');
        },
        preserveScroll: true
    });

    isCancelling.value = false;
};

const resetForm = () => {
    selectedGateway.value = null;
    withdrawalForm.value = { amount: null, withdrawal_gateway_id: null };
    withdrawalDetails.value = {};
};

const goToPage = (url) => {
    if (!url || typeof url !== 'string') return;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        onError: () => showToast(t('failedToLoadPage'), 'error')
    });
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        if (showModal.value) closeModal();
        else if (showCancelModal.value) closeCancelModal();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    isProcessing.value = false;
    isCancelling.value = false;
});
</script>

<style scoped>
input:focus,
select:focus,
button:focus {
    outline: none;
    box-shadow: 0 0 0 2px v-bind('`rgba(${themeColors.primaryRgb}, 0.2)`');
}
</style>

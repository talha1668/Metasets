<template>
    <div class="space-y-6">
        <section :aria-labelledby="t('depositStatistics')"
                 class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
            <h2 class="sr-only">{{ t('depositStatistics') }}</h2>

            <div
                class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                role="region" :aria-labelledby="t('walletBalance')" tabindex="0">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{
                        t('walletBalance')
                    }}</h3>
                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                   :aria-label="`${t('walletBalance')}: ${currencySymbol}${formatNumber(walletBalance)}`">
                    {{ currencySymbol }}{{ formatNumber(walletBalance) }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-semibold" :style="{ color: themeColors.primary }"
                          :aria-label="t('available')">
                        {{ t('available') }}
                    </span>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                role="region" :aria-labelledby="t('totalDeposits')" tabindex="0">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{
                        t('totalDeposits')
                    }}</h3>
                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                   :aria-label="`${t('totalDeposits')}: ${deposits?.total || 0}`">
                    {{ deposits?.total || 0 }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{
                            t('allTime')
                        }}</span>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                role="region" :aria-labelledby="t('successfulDeposits')" tabindex="0">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    {{ t('successfulDeposits') }}</h3>
                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                   :aria-label="`${t('successfulDeposits')}: ${successfulDeposits}`">
                    {{ successfulDeposits }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-semibold" :style="{ color: themeColors.primary }">{{
                            t('completed')
                        }}</span>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 group"
                role="region" :aria-labelledby="t('pendingDeposits')" tabindex="0">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{
                        t('pendingDeposits')
                    }}</h3>
                <p class="text-xl sm:text-2xl font-bold text-white mb-1.5"
                   :aria-label="`${t('pendingDeposits')}: ${pendingDeposits}`">
                    {{ pendingDeposits }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-semibold text-yellow-400">{{ t('processing') }}</span>
                </div>
            </div>
        </section>

        <section :aria-labelledby="t('depositForm')" class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">
            <h2 class="sr-only">{{ t('depositForm') }}</h2>
            <div class="xl:col-span-8 space-y-5">
                <div
                    class="bg-gradient-to-br from-slate-900/70 to-slate-800/50 backdrop-blur-xl border border-slate-700/30 rounded-2xl p-5 shadow-2xl"
                    role="region" :aria-labelledby="t('selectPaymentMethod')">
                    <div class="mb-5 sm:mb-6">
                        <h3 class="text-lg sm:text-xl font-bold text-white mb-1.5">{{ t('selectPaymentMethod') }}</h3>
                        <p class="text-sm font-medium text-slate-400">{{ t('chooseGateway') }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-5 sm:mb-6">
                        <div
                            v-for="gateway in paymentGateways"
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
                                <div
                                    class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition-transform duration-300 group-hover:scale-110"
                                    :style="{ background: `linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})` }">
                                    <span class="font-bold text-slate-900 text-xs">{{
                                            gateway.name.substring(0, 2).toUpperCase()
                                        }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-white text-sm mb-1 group-hover:opacity-90 transition-opacity">
                                        {{ gateway.name }}</h4>
                                    <div class="flex items-center gap-1.5">
                                        <div class="text-xs font-semibold px-2 py-0.5 rounded-md"
                                             :style="{
                                                 color: themeColors.primary,
                                                 backgroundColor: `rgba(${themeColors.primaryRgb}, 0.15)`
                                             }">
                                            {{
                                                gateway.fixed_charge > 0 ? currencySymbol + formatNumber(gateway.fixed_charge) + ' + ' : ''
                                            }}{{ formatNumber(gateway.percent_charge) }}% {{ t('fee') }}
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
                                        <path fill-rule="evenodd"
                                              d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedGateway && selectedGateway.credentials && selectedGateway.type === 'manual'"
                         class="rounded-lg p-4 sm:p-5 border mb-5 sm:mb-6"
                         :style="{ borderColor: `rgba(${themeColors.primaryRgb}, 0.3)`, background: `linear-gradient(to right, rgba(${themeColors.primaryRgb}, 0.1), rgba(${themeColors.primaryRgb}, 0.05))` }">
                        <h4 class="font-bold text-white text-sm sm:text-base mb-4 sm:mb-5">{{
                                t('paymentCredentials')
                            }}</h4>
                        <div class="space-y-3">
                            <div v-for="(value, key) in selectedGateway.credentials" :key="key"
                                 class="flex items-center justify-between p-3 sm:p-4 rounded-lg bg-slate-800/40 hover:bg-slate-800/60 transition-colors">
                                <div class="flex-1 min-w-0 mr-3">
                                    <p class="text-xs text-slate-400 font-medium mb-1 capitalize">
                                        {{ formatFieldName(key) }}</p>
                                    <p class="text-sm font-mono font-medium text-white break-all">{{
                                            value
                                        }}</p>
                                </div>
                                <button
                                    class="flex-shrink-0 p-2.5 rounded-lg transition-all hover:scale-110"
                                    :style="{ backgroundColor: `rgba(${themeColors.primaryRgb}, 0.2)`, color: themeColors.primary }"
                                    @click="copyToClipboard(value)"
                                    :aria-label="`${t('copy')} ${formatFieldName(key)}`"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedGateway" class="border-t border-slate-700/30 pt-5 sm:pt-6">
                        <form class="space-y-4 sm:space-y-5" @submit.prevent="submitDeposit">
                            <div>
                                <label class="block font-semibold text-white text-sm mb-2">
                                    {{ t('depositAmount') }} ({{ selectedGateway.currency }})
                                </label>
                                <input
                                    v-model.number="depositForm.amount"
                                    type="number"
                                    :min="selectedGateway.min_amount"
                                    :max="selectedGateway.max_amount"
                                    step="0.0001"
                                    class="w-full px-4 py-3 border border-slate-700/50 rounded-lg text-sm font-medium text-white bg-slate-800/50 transition-all focus:border-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="{ '--tw-ring-color': `rgba(${themeColors.primaryRgb}, 0.3)` }"
                                    :placeholder="t('enterAmount')"
                                    required
                                >
                                <div class="flex items-center justify-between mt-1.5 text-xs">
                                    <span class="text-slate-400 font-medium">
                                        {{ t('min') }}: {{
                                            getGatewayCurrencySymbol(selectedGateway)
                                        }}{{ formatNumber(selectedGateway.min_amount) }} - {{
                                            t('max')
                                        }}: {{
                                            getGatewayCurrencySymbol(selectedGateway)
                                        }}{{ formatNumber(selectedGateway.max_amount) }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="selectedGateway.type === 'automatic' && selectedGateway.slug === 'nowpayments'">
                                <label class="block font-semibold text-white text-sm mb-2">
                                    {{ t('selectCryptocurrency') }}
                                </label>

                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-3">
                                    <div
                                        v-for="crypto in availableCryptos"
                                        :key="crypto.code"
                                        class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border rounded-lg p-3 cursor-pointer transition-all duration-300 hover:scale-[1.02] hover:shadow-lg group"
                                        :class="selectedCryptoCurrency === crypto.code ? 'shadow-xl' : ''"
                                        :style="{
                                            borderColor: selectedCryptoCurrency === crypto.code ? `rgba(${themeColors.primaryRgb}, 0.5)` : 'rgba(51, 65, 85, 0.4)',
                                            backgroundColor: selectedCryptoCurrency === crypto.code ? `rgba(${themeColors.primaryRgb}, 0.05)` : ''
                                        }"
                                        @click="selectedCryptoCurrency = crypto.code"
                                    >
                                        <div class="flex flex-col items-center space-y-1.5">
                                            <div
                                                class="w-10 h-10 rounded-full flex items-center justify-center text-base font-bold transition-transform duration-300 group-hover:scale-110"
                                                :style="{
                                                    backgroundColor: selectedCryptoCurrency === crypto.code ? themeColors.primary : 'rgba(51, 65, 85, 0.5)',
                                                    color: selectedCryptoCurrency === crypto.code ? '#0f172a' : '#e2e8f0'
                                                }"
                                            >
                                                {{ crypto.symbol }}
                                            </div>
                                            <div class="text-center">
                                                <div class="text-xs font-bold uppercase text-white">
                                                    {{ crypto.code }}
                                                </div>
                                                <div class="text-xs text-slate-400 truncate max-w-[100px]">
                                                    {{ crypto.name }}
                                                </div>
                                            </div>
                                            <div
                                                v-if="selectedCryptoCurrency === crypto.code"
                                                class="w-5 h-5"
                                                :style="{ color: themeColors.primary }"
                                            >
                                                <svg fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 p-3 rounded-lg border bg-slate-800/30"
                                     :style="{ borderColor: `rgba(${themeColors.primaryRgb}, 0.3)` }">
                                    <div class="flex items-start space-x-2.5">
                                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" :style="{ color: themeColors.primary }"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <div class="text-sm text-slate-300">
                                            <p class="font-semibold mb-0.5">{{ t('cryptoPaymentInfo') }}</p>
                                            <p class="text-slate-400 text-xs">{{ t('cryptoPaymentDescription') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedGateway.parameters && selectedGateway.parameters.length > 0"
                                 class="space-y-3 sm:space-y-4">
                                <h4 class="font-bold text-white text-sm sm:text-base">{{ t('paymentInformation') }}</h4>
                                <div v-for="param in selectedGateway.parameters" :key="param.field_name"
                                     class="space-y-1.5">
                                    <label class="block text-sm font-semibold text-slate-300">
                                        {{ param.field_label }}
                                        <span v-if="param.field_required" class="text-red-400 ml-1">*</span>
                                    </label>
                                    <input
                                        v-if="['text', 'email', 'tel', 'number', 'date', 'datetime-local'].includes(param.field_type)"
                                        v-model="paymentDetails[param.field_name]"
                                        :type="param.field_type"
                                        :placeholder="param.field_placeholder"
                                        :required="param.field_required"
                                        class="w-full px-4 py-3 border border-slate-700/50 rounded-lg text-sm font-medium text-white bg-slate-800/50 focus:border-slate-600 focus:outline-none transition-colors"
                                    >
                                    <select
                                        v-else-if="param.field_type === 'select'"
                                        v-model="paymentDetails[param.field_name]"
                                        :required="param.field_required"
                                        class="w-full px-4 py-3 border border-slate-700/50 rounded-lg text-sm font-medium text-white bg-slate-800/50 focus:border-slate-600 focus:outline-none transition-colors"
                                    >
                                        <option value="">{{ param.field_placeholder || t('selectOption') }}</option>
                                        <option v-for="(label, value) in param.field_options" :key="value"
                                                :value="value">{{ label }}
                                        </option>
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
                                class="w-full py-2.5 sm:py-3 px-5 rounded-lg font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 shadow-lg hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="{
                                    background: canSubmit ? `linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})` : '#1e293b',
                                    color: canSubmit ? '#0f172a' : '#64748b',
                                    '--tw-ring-color': `rgba(${themeColors.primaryRgb}, 0.5)`
                                }"
                            >
                                <span v-if="isProcessing" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                    {{ t('processing') }}...
                                </span>
                                <span v-else class="flex items-center justify-center">
                                    {{ t('submitDepositRequest') }}
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-4 space-y-5">
                <div v-if="selectedGateway && isValidAmount"
                     class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-4 sm:p-5 hover:border-slate-600/50 transition-all duration-300"
                     role="region" :aria-labelledby="t('paymentSummary')">
                    <h3 class="text-sm sm:text-base font-bold text-white mb-3">{{ t('paymentSummary') }}</h3>
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-400 font-medium">{{ t('amount') }}:</span>
                            <span class="font-semibold text-white">{{
                                    getGatewayCurrencySymbol(selectedGateway)
                                }}{{ formatNumber(depositForm.amount) }}</span>
                        </div>
                        <div v-if="calculatedCharge > 0" class="flex justify-between items-center">
                            <span class="text-sm text-slate-400 font-medium">{{ t('processingFee') }}:</span>
                            <span class="font-semibold text-yellow-400">{{
                                    getGatewayCurrencySymbol(selectedGateway)
                                }}{{ formatNumber(calculatedCharge) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-slate-700/50 pt-2.5">
                            <span class="text-sm font-semibold text-white">{{ t('totalPayable') }}:</span>
                            <span class="font-bold text-base sm:text-lg" :style="{ color: themeColors.primary }">{{
                                    getGatewayCurrencySymbol(selectedGateway)
                                }}{{ formatNumber(totalAmount) }}</span>
                        </div>
                        <div class="rounded-lg p-3 mt-2.5 flex justify-between items-center"
                             :style="{ background: `linear-gradient(to right, rgba(${themeColors.primaryRgb}, 0.15), rgba(${themeColors.primaryRgb}, 0.05))`, borderColor: `rgba(${themeColors.primaryRgb}, 0.3)` }">
                            <div class="text-xs font-medium text-slate-400">{{ t('youWillReceive') }}:</div>
                            <div class="font-bold text-base sm:text-lg" :style="{ color: themeColors.primary }">
                                {{ currencySymbol }}{{ formatNumber(depositForm.amount) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedGateway"
                     class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <div v-if="selectedGateway.file" class="mb-5 flex justify-center">
                        <div
                            class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden bg-slate-800/50 p-3 flex items-center justify-center border border-slate-700/30">
                            <img :src="`/assets/files/${selectedGateway.file}`"
                                 :alt="selectedGateway.name"
                                 class="w-full h-full object-contain"
                                 @error="$event.target.style.display='none'">
                        </div>
                    </div>

                    <h3 class="text-base sm:text-lg font-bold text-white mb-3 text-center">{{ selectedGateway.name }}</h3>
                    <div v-if="selectedGateway.description"
                         class="text-sm font-medium text-slate-300 leading-relaxed p-3 sm:p-4 rounded-lg bg-slate-800/30 border border-slate-700/30"
                         v-html="selectedGateway.description"></div>
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
                            <span class="font-semibold" :style="{ color: themeColors.primary }">{{
                                    t('instantManual')
                                }}</span>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <h3 class="text-base sm:text-lg font-bold text-white mb-5">{{ t('quickTips') }}</h3>
                    <div class="space-y-3 sm:space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 :style="{ background: `linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})` }">
                                <svg class="w-4 h-4 text-slate-900" fill="currentColor"
                                     viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-white mb-0.5">{{ t('secureTransactions') }}</p>
                                <p class="text-xs font-medium text-slate-400">{{
                                        t('allDepositsEncrypted')
                                    }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section :aria-labelledby="t('depositHistory')" class="space-y-5">
            <h2 class="sr-only">{{ t('depositHistory') }}</h2>

            <div
                class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl overflow-hidden hover:border-slate-600/50 transition-all duration-300">
                <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-700/30">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex-1">
                            <h3 class="text-lg sm:text-xl font-bold text-white mb-1">{{ t('depositHistory') }}</h3>
                            <p class="text-sm font-medium text-slate-400">{{ t('trackDepositTransactions') }}</p>
                        </div>
                        <div class="text-sm font-medium text-slate-400">
                            {{ deposits?.total || 0 }} {{ t('totalDepositsCount') }}
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <div class="block lg:hidden">
                        <div v-if="!deposits?.data || deposits.data.length === 0"
                             class="text-center py-12 sm:py-16 px-4">
                            <div class="flex flex-col items-center">
                                <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-3 text-slate-600" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <p class="text-base font-semibold text-slate-300">{{
                                        t('noDepositsFound')
                                    }}</p>
                                <p class="text-sm font-medium text-slate-400 mt-1">{{ t('makeFirstDeposit') }}</p>
                            </div>
                        </div>

                        <div v-for="deposit in deposits?.data" :key="deposit.id"
                             class="border-b border-slate-700/30 last:border-b-0">
                            <div class="p-4 sm:p-5 space-y-3 hover:bg-slate-800/30 transition-colors">
                                <div class="flex justify-between items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold font-mono text-white truncate">{{
                                                deposit.trx
                                            }}
                                        </div>
                                        <div class="text-xs font-medium text-slate-400 mt-0.5">
                                            {{ formatDate(deposit.created_at) }}
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full border flex-shrink-0"
                                          :style="
                                            { backgroundColor: getStatusBadgeColor(deposit.status) + '20', borderColor: getStatusBadgeColor(deposit.status) + '30', color: getStatusBadgeColor(deposit.status) }">
                                            {{ formatStatus(deposit.status) }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-slate-800/40 rounded-lg p-3">
                                        <div class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">
                                            {{ t('amount') }}
                                        </div>
                                        <div class="text-base font-bold text-white">{{
                                                currencySymbol
                                            }}{{ formatNumber(deposit.amount) }}
                                        </div>
                                        <div v-if="deposit.charge > 0" class="text-xs text-yellow-400 mt-1 font-medium">
                                            +{{ currencySymbol }}{{ formatNumber(deposit.charge) }} {{ t('fee') }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg p-3 border"
                                         :style="{ background: `linear-gradient(to bottom right, rgba(${themeColors.primaryRgb}, 0.1), rgba(${themeColors.primaryRgb}, 0.05))`, borderColor: `rgba(${themeColors.primaryRgb}, 0.2)` }">
                                        <div class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1.5">
                                            {{ t('depositAmount') }}
                                        </div>
                                        <div class="text-base font-bold" :style="{ color: themeColors.primary }">
                                            {{ currencySymbol }}{{ formatNumber(deposit.deposit_amount) }}
                                        </div>
                                        <div class="text-xs text-slate-400 mt-1 font-medium">{{ t('toWallet') }}</div>
                                    </div>
                                </div>

                                <div class="bg-slate-800/40 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div
                                                class="text-xs uppercase font-semibold tracking-wider text-slate-400 mb-1">
                                                {{ t('gateway') }}
                                            </div>
                                            <div class="text-sm font-semibold text-slate-300 truncate">
                                                {{ deposit.payment_gateway?.name || t('notAvailable') }}
                                            </div>
                                        </div>
                                        <div class="text-xs font-semibold text-slate-400 ml-2">{{
                                                deposit.currency
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-1">
                                    <button
                                        class="w-full text-sm font-semibold transition-all duration-200 px-4 py-2.5 rounded-lg hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                        :style="{ color: themeColors.primary, backgroundColor: `rgba(${themeColors.primaryRgb}, 0.1)`, border: `1px solid rgba(${themeColors.primaryRgb}, 0.3)`, '--tw-ring-color': `rgba(${themeColors.primaryRgb}, 0.5)` }"
                                        @click="showDepositDetails(deposit)">
                                        {{ t('viewDetails') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="w-full hidden lg:table">
                        <thead class="bg-slate-800/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                {{ t('trxId') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                {{ t('amount') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                {{ t('depositAmount') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">
                                {{ t('gateway') }}
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
                        <tr v-if="!deposits?.data || deposits.data.length === 0">
                            <td colspan="7" class="px-5 py-12 sm:py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-3 text-slate-600" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <p class="text-base font-semibold text-slate-300">{{ t('noDepositsFound') }}</p>
                                    <p class="text-sm font-medium text-slate-400 mt-1">{{ t('makeFirstDeposit') }}</p>
                                </div>
                            </td>
                        </tr>

                        <tr v-for="deposit in deposits?.data" :key="deposit.id"
                            class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold font-mono text-white">{{ deposit.trx }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-white">{{ formatNumber(deposit.amount) }}
                                    {{ deposit.currency }}
                                </div>
                                <div v-if="deposit.charge > 0" class="text-xs text-yellow-400 font-medium">
                                    +{{ formatNumber(deposit.charge) }} {{ deposit.currency }} {{ t('fee') }}
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold" :style="{ color: themeColors.primary }">
                                    {{ currencySymbol }}{{ formatNumber(deposit.deposit_amount) }}
                                </div>
                                <div class="text-xs text-slate-400 font-medium">{{ t('toWallet') }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-300">
                                    {{ deposit.payment_gateway?.name || t('notAvailable') }}
                                </div>
                                <div class="text-xs text-slate-400 font-medium">{{ deposit.currency }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border"
                                      :style="{ backgroundColor: getStatusBadgeColor(deposit.status) + '20', borderColor: getStatusBadgeColor(deposit.status) + '30', color: getStatusBadgeColor(deposit.status) }">
                                    {{ formatStatus(deposit.status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                <div class="text-slate-300 font-medium">{{ formatDate(deposit.created_at) }}</div>
                                <div class="text-xs text-slate-400 font-medium">{{
                                        formatTime(deposit.created_at)
                                    }}
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                <button
                                    class="font-semibold transition-colors duration-200 hover:opacity-80 cursor-pointer focus:outline-none"
                                    :style="{ color: themeColors.primary }"
                                    @click="showDepositDetails(deposit)">
                                    {{ t('viewDetails') }}
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="deposits?.data && deposits.data.length > 0 && deposits.links"
                     class="px-5 sm:px-6 py-4 sm:py-5 border-t border-slate-700/30">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-sm font-medium text-slate-400">
                            {{
                                t('showingResults', {
                                    from: deposits.from || 0,
                                    to: deposits.to || 0,
                                    total: deposits.total || 0
                                })
                            }}
                        </div>
                        <div class="flex flex-wrap justify-center sm:justify-end gap-1.5">
                            <button v-for="(link, index) in deposits.links" :key="index" :disabled="!link.url"
                                    :class="['px-3 py-1.5 text-sm font-semibold rounded-lg transition-all hover:scale-105 focus:outline-none', link.active ? 'cursor-default' : link.url ? 'hover:opacity-80' : 'cursor-not-allowed opacity-50']"
                                    :style="{ backgroundColor: link.active ? themeColors.primary : link.url ? 'rgba(71, 85, 105, 0.5)' : 'rgba(71, 85, 105, 0.3)', color: link.active ? '#0f172a' : link.url ? '#e2e8f0' : '#64748b' }"
                                    @click="goToPage(link.url)" v-html="link.label"/>
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
        <div v-if="showModal"
             class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
             @click.self="closeModal">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="showModal"
                     class="bg-gradient-to-br from-slate-900/95 to-slate-800/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                     role="dialog" aria-modal="true" @keydown.escape="closeModal">
                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-700/50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg sm:text-xl font-bold text-white">{{ t('depositDetails') }}</h3>
                            <button
                                class="p-2 transition-colors text-slate-400 hover:text-white rounded-lg hover:bg-slate-800/50 focus:outline-none focus:ring-2 focus:ring-white/20"
                                @click="closeModal" :aria-label="t('close')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="selectedDeposit" class="p-4 sm:p-5 lg:p-6 space-y-4">
                        <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5"
                                     :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('transactionInformation') }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs block text-slate-400 font-medium mb-1.5">{{
                                            t('transactionId')
                                        }}</label>
                                    <p class="font-mono text-sm sm:text-base font-semibold" :style="{ color: themeColors.primary }">
                                        {{ selectedDeposit.trx }}</p>
                                </div>
                                <div>
                                    <label class="text-xs block text-slate-400 font-medium mb-1.5">{{
                                            t('status')
                                        }}</label>
                                    <div class="mt-0.5">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full border"
                                          :style="{ backgroundColor: getStatusBadgeColor(selectedDeposit.status) + '20', borderColor: getStatusBadgeColor(selectedDeposit.status) + '30', color: getStatusBadgeColor(selectedDeposit.status) }">
                                        {{ formatStatus(selectedDeposit.status) }}
                                    </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs block text-slate-400 font-medium mb-1.5">{{
                                            t('paymentGateway')
                                        }}</label>
                                    <p class="font-semibold text-sm text-white">
                                        {{ selectedDeposit.payment_gateway?.name || t('notAvailable') }}</p>
                                </div>
                                <div>
                                    <label class="text-xs block text-slate-400 font-medium mb-1.5">{{
                                            t('currency')
                                        }}</label>
                                    <p class="font-semibold text-sm text-white">{{ selectedDeposit.currency || 'USD' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5"
                                     :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('amountDetails') }}
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('requestedAmount') }}:</span>
                                    <span class="font-semibold text-base text-white">{{
                                            formatNumber(selectedDeposit.amount)
                                        }} {{ selectedDeposit.currency }}</span>
                                </div>
                                <div v-if="selectedDeposit.charge > 0" class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('processingFee') }}:</span>
                                    <span class="font-semibold text-yellow-400">{{
                                            formatNumber(selectedDeposit.charge)
                                        }} {{ selectedDeposit.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center border-t pt-3 border-slate-700/50">
                                    <span class="font-semibold text-sm text-white">{{ t('totalPaid') }}:</span>
                                    <span class="font-bold text-lg sm:text-xl" :style="{ color: themeColors.primary }">{{
                                            formatNumber(selectedDeposit.final_amount || selectedDeposit.amount)
                                        }} {{ selectedDeposit.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center rounded-lg p-3 mt-3 bg-slate-800/30">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('addedToWallet') }}:</span>
                                    <span class="font-bold text-base" :style="{ color: themeColors.primary }">{{
                                            currencySymbol
                                        }}{{ formatNumber(selectedDeposit.deposit_amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="selectedDeposit.payment_details && Object.keys(selectedDeposit.payment_details).length > 0"
                            class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5"
                                     :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('paymentDetails') }}
                            </h4>
                            <div class="space-y-3">
                                <div v-for="(value, key) in selectedDeposit.payment_details" :key="key"
                                     class="flex justify-between items-start">
                                    <span class="capitalize min-w-[140px] text-slate-400 font-medium text-sm">{{
                                            formatFieldName(key)
                                        }}:</span>
                                    <span class="font-semibold break-all text-right flex-1 ml-3 text-white text-sm">
                                    <template v-if="value && key === 'file'">
                                        <div class="flex items-center gap-1.5 justify-end flex-wrap">
                                            <a :href="`/user/wallet/deposit/${selectedDeposit.id}/view/${key}`"
                                               target="_blank"
                                               class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                {{ t('view') }}
                                            </a>
                                            <a :href="`/user/wallet/deposit/${selectedDeposit.id}/download/${key}`"
                                               class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                {{ t('download') }}
                                            </a>
                                        </div>
                                    </template>
                                    <template v-else>{{ value || t('notAvailable') }}</template>
                                </span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-4 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5"
                                     :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('timeline') }}
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('created') }}:</span>
                                    <span class="font-semibold text-sm text-white">{{
                                            formatDateTime(selectedDeposit.created_at)
                                        }}</span>
                                </div>
                                <div v-if="selectedDeposit.approved_at" class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('approved') }}:</span>
                                    <span class="font-semibold text-sm" :style="{ color: themeColors.primary }">{{
                                            formatDateTime(selectedDeposit.approved_at)
                                        }}</span>
                                </div>
                                <div v-if="selectedDeposit.rejected_at" class="flex justify-between items-center">
                                    <span class="text-slate-400 font-medium text-sm">{{ t('rejected') }}:</span>
                                    <span class="font-semibold text-sm text-red-400">{{
                                            formatDateTime(selectedDeposit.rejected_at)
                                        }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedDeposit.admin_response"
                             class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-3 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5"
                                     :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('adminResponse') }}
                            </h4>
                            <p class="leading-relaxed font-medium text-slate-300 text-sm">{{
                                    selectedDeposit.admin_response
                                }}</p>
                        </div>

                        <div v-if="selectedDeposit.transaction_id"
                             class="rounded-lg p-4 sm:p-5 border bg-slate-800/50 border-slate-700/50">
                            <h4 class="font-bold text-sm sm:text-base mb-3 flex items-center text-white">
                                <div class="w-2 h-2 rounded-full mr-2.5"
                                     :style="{ backgroundColor: themeColors.primary }"></div>
                                {{ t('paymentTransactionId') }}
                            </h4>
                            <p class="font-mono break-all text-sm sm:text-base font-semibold"
                               :style="{ color: themeColors.primary }">{{ selectedDeposit.transaction_id }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end p-4 sm:p-5 lg:p-6 border-t border-slate-700/50">
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
</template>

<script setup>
import {ref, computed, onMounted, onUnmounted} from 'vue';
import {router, usePage} from '@inertiajs/vue3';
import {useToast} from "@/composables/useToast.js";
import {useTranslation} from '@/composables/useTranslation';

const props = defineProps({
    deposits: {
        type: Object,
        required: true,
        default: () => ({data: [], total: 0, links: []})
    },
    paymentGateways: {
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
const paymentDetails = ref({});
const showModal = ref(false);
const selectedDeposit = ref(null);

const depositForm = ref({
    amount: null,
    payment_gateway_id: null
});

const selectedCryptoCurrency = ref('btc');
const availableCryptos = ref([
    { code: 'btc', name: 'Bitcoin', symbol: '₿' },
    { code: 'eth', name: 'Ethereum', symbol: 'Ξ' },
    { code: 'usdttrc20', name: 'Tether TRC20', symbol: '₮' },
    { code: 'usdterc20', name: 'Tether ERC20', symbol: '₮' },
    {code: 'usdtbsc', name: 'Tether BEP20 (USDT)', symbol: '₮'},
    { code: 'usdc', name: 'USD Coin', symbol: '$' },
    { code: 'bnbbsc', name: 'Binance Coin', symbol: 'BNB' },
    { code: 'trx', name: 'Tron', symbol: 'TRX' },
    { code: 'doge', name: 'Dogecoin', symbol: 'Ð' },
    { code: 'ltc', name: 'Litecoin', symbol: 'Ł' },
    { code: 'xrp', name: 'Ripple', symbol: 'XRP' },
    { code: 'ada', name: 'Cardano', symbol: '₳' },
    { code: 'matic', name: 'Polygon', symbol: 'M' },
    { code: 'avax', name: 'Avalanche', symbol: 'A' },
    { code: 'sol', name: 'Solana', symbol: '◎' },
    { code: 'dot', name: 'Polkadot', symbol: 'DOT' },
    { code: 'link', name: 'Chainlink', symbol: '🔗' },
    { code: 'shib', name: 'Shiba Inu', symbol: '🐕' },
    { code: 'ton', name: 'Toncoin', symbol: 'TON' },
    { code: 'xmr', name: 'Monero', symbol: 'XMR' }
]);

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

const currencySymbol = computed(() => page.props.currencySymbol || '$');

const isValidAmount = computed(() => {
    if (!selectedGateway.value) return false;
    const amount = parseFloat(depositForm.value.amount);
    if (isNaN(amount) || amount <= 0) return false;
    const minAmount = parseFloat(selectedGateway.value.min_amount || 0);
    const maxAmount = parseFloat(selectedGateway.value.max_amount || Number.MAX_SAFE_INTEGER);
    return amount >= minAmount && amount <= maxAmount;
});

const calculatedCharge = computed(() => {
    if (!selectedGateway.value || !isValidAmount.value) return 0;
    const amount = parseFloat(depositForm.value.amount);
    if (isNaN(amount)) return 0;
    const fixedCharge = parseFloat(selectedGateway.value.fixed_charge || 0);
    const percentCharge = parseFloat(selectedGateway.value.percent_charge || 0);
    return fixedCharge + (amount * percentCharge / 100);
});

const totalAmount = computed(() => {
    if (!isValidAmount.value) return 0;
    const amount = parseFloat(depositForm.value.amount);
    return amount + calculatedCharge.value;
});

const canSubmit = computed(() => {
    if (!isValidAmount.value || isProcessing.value) return false;
    if (selectedGateway.value?.parameters) {
        const requiredFields = selectedGateway.value.parameters.filter(param => param.field_required);
        for (const field of requiredFields) {
            const value = paymentDetails.value[field.field_name];
            if (!value || (typeof value === 'string' && value.trim() === '')) return false;
        }
    }
    return true;
});

const successfulDeposits = computed(() => {
    if (!props.deposits?.data || !Array.isArray(props.deposits.data)) return 0;
    return props.deposits.data.filter(d => ['approved', 'completed', 'success'].includes(d.status?.toLowerCase())).length;
});

const pendingDeposits = computed(() => {
    if (!props.deposits?.data || !Array.isArray(props.deposits.data)) return 0;
    return props.deposits.data.filter(d => ['pending', 'processing'].includes(d.status?.toLowerCase())).length;
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
        case 'success':
            return themeColors.value.primary;
        case 'pending':
        case 'processing':
            return '#fbbf24';
        case 'rejected':
        case 'failed':
        case 'cancelled':
            return '#ef4444';
        default:
            return '#94a3b8';
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
        showToast(t('invalidPaymentGateway'), 'error');
        return;
    }
    selectedGateway.value = gateway;
    depositForm.value.payment_gateway_id = gateway.id;
    depositForm.value.amount = null;
    paymentDetails.value = {};
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
    paymentDetails.value[fieldName] = file;
    showToast(t('fileSelectedSuccessfully'), 'success');
};
const submitDeposit = async () => {
    if (!canSubmit.value) {
        showToast(t('ensureRequiredFieldsFilled'), 'error');
        return;
    }

    isProcessing.value = true;
    try {
        const formData = new FormData();
        formData.append('payment_gateway_id', depositForm.value.payment_gateway_id);
        formData.append('amount', depositForm.value.amount);

        if (selectedGateway.value?.slug === 'nowpayments') {
            formData.append('selected_currency', selectedCryptoCurrency.value);
        }

        Object.keys(paymentDetails.value).forEach(key => {
            const value = paymentDetails.value[key];
            if (value instanceof File) {
                formData.append(`payment_details[${key}]`, value);
            } else if (value !== null && value !== undefined && value !== '') {
                const sanitizedValue = String(value).trim();
                if (sanitizedValue) {
                    formData.append(`payment_details[${key}]`, sanitizedValue);
                }
            }
        });

        router.post('/user/wallet/deposit', formData, {
            onSuccess: (page) => {
                resetForm();
                const flash = page.props.flash;
                if (flash?.success) {
                    showToast(flash.success, 'success');
                } else {
                    showToast(t('depositRequestSubmitted'), 'success');
                }
            },
            onError: (errors) => {
                const errorMessages = Object.values(errors).flat();
                showToast(errorMessages[0] || t('depositSubmissionFailed'), 'error');
            },
            preserveScroll: true,
            forceFormData: true
        });
    } catch (error) {
        showToast(t('depositSubmissionFailed'), 'error');
    } finally {
        isProcessing.value = false;
    }
};

const showDepositDetails = (deposit) => {
    if (!deposit?.id) {
        showToast(t('invalidDepositSelected'), 'error');
        return;
    }
    selectedDeposit.value = deposit;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => {
        selectedDeposit.value = null;
    }, 300);
};

const resetForm = () => {
    selectedGateway.value = null;
    depositForm.value = {amount: null, payment_gateway_id: null};
    paymentDetails.value = {};
    selectedCryptoCurrency.value = 'btc';
};

const goToPage = (url) => {
    if (!url || typeof url !== 'string') return;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        onError: () => showToast(t('failedToLoadPage'), 'error')
    });
};

const copyToClipboard = async (text, label = '') => {
    try {
        const cleanText = text.replace(/<[^>]*>/g, '');

        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(cleanText);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = cleanText;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            const successful = document.execCommand('copy');
            textArea.remove();

            if (!successful) {
                throw new Error('Copy command failed');
            }
        }

        showToast(`Text copied to clipboard successfully!`, 'success');
    } catch (error) {
        console.error('Copy failed:', error);
        showToast('Failed to copy to clipboard', 'error');
    }
};

const handleKeydown = (event) => {
    if (event.key === 'Escape' && showModal.value) {
        closeModal();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    isProcessing.value = false;
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

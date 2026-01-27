<template>
    <UserLayout
        :page-title="t('settings')"
        :page-section="t('account')"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
                    <div class="space-y-5 sm:space-y-6">
                        <section class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6">
                            <h2 class="text-lg sm:text-xl font-bold text-white mb-5">{{ t('profileInformation') }}</h2>

                            <form @submit.prevent="updateProfile" class="space-y-5">
                                <div class="flex items-center gap-5">
                                    <div class="relative">
                                        <div class="w-18 h-18 sm:w-20 sm:h-20 rounded-full overflow-hidden bg-slate-800/50 border-2 border-slate-700/50">
                                            <img
                                                v-if="avatarPreview || currentUser.avatar_url"
                                                :src="avatarPreview || currentUser.avatar_url"
                                                alt="Profile"
                                                class="w-full h-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="w-full h-full flex items-center justify-center text-white text-xl font-bold"
                                                :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})`"
                                            >
                                                {{ currentUser.name.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <input
                                            ref="avatarInput"
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg"
                                            @change="handleAvatarChange"
                                            class="hidden"
                                        />
                                        <button
                                            type="button"
                                            @click="$refs.avatarInput.click()"
                                            :disabled="avatarUploading"
                                            class="absolute bottom-0 right-0 w-7 h-7 rounded-full flex items-center justify-center transition-all hover:scale-110 disabled:opacity-50"
                                            :style="`background: ${themeColors.primary}; color: white;`"
                                        >
                                            <svg v-if="!avatarUploading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-400 mb-0.5">{{ t('profilePhoto') }}</p>
                                        <p class="text-xs font-medium text-slate-500 mb-1.5">{{ t('jpgPngMax2mb') }}</p>
                                        <button
                                            v-if="currentUser.avatar_url"
                                            type="button"
                                            @click="removeAvatar"
                                            :disabled="avatarRemoving"
                                            class="text-xs font-medium text-red-400 hover:text-red-300 transition-colors disabled:opacity-50"
                                        >
                                            {{ avatarRemoving ? t('removing') + '...' : t('removePhoto') }}
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('fullName') }}</label>
                                    <input
                                        v-model="profileForm.name"
                                        type="text"
                                        class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                        :style="`--tw-ring-color: ${themeColors.primary}`"
                                    />
                                    <p v-if="errors.name" class="text-red-400 text-xs font-medium mt-1">{{ errors.name }}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('email') }}</label>
                                    <input
                                        v-model="profileForm.email"
                                        type="email"
                                        class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                        :style="`--tw-ring-color: ${themeColors.primary}`"
                                    />
                                    <p v-if="errors.email" class="text-red-400 text-xs font-medium mt-1">{{ errors.email }}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('gender') }}</label>
                                    <select
                                        v-model="profileForm.gender"
                                        class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                        :style="`--tw-ring-color: ${themeColors.primary}`"
                                    >
                                        <option value="">{{ t('selectGender') }}</option>
                                        <option value="male">{{ t('male') }}</option>
                                        <option value="female">{{ t('female') }}</option>
                                        <option value="other">{{ t('other') }}</option>
                                    </select>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isProcessing"
                                    class="w-full px-5 py-2.5 rounded-lg font-semibold text-sm text-slate-900 transition-all hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); --tw-ring-color: ${themeColors.primary}`"
                                >
                                    <span v-if="isProcessing">{{ t('updating') }}...</span>
                                    <span v-else>{{ t('updateProfile') }}</span>
                                </button>
                            </form>
                        </section>

                        <section class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6">
                            <h2 class="text-lg sm:text-xl font-bold text-white mb-5">{{ t('updatePassword') }}</h2>
                            <form @submit.prevent="updatePassword" class="space-y-5">
                                <div>
                                    <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('currentPassword') }}</label>
                                    <div class="relative">
                                        <input
                                            v-model="passwordForm.current_password"
                                            :type="showCurrentPassword ? 'text' : 'password'"
                                            class="w-full px-4 py-2.5 pr-11 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                            :style="`--tw-ring-color: ${themeColors.primary}`"
                                            :placeholder="t('enterCurrentPassword')"
                                        />
                                        <button
                                            type="button"
                                            @click="showCurrentPassword = !showCurrentPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors"
                                        >
                                            <svg v-if="showCurrentPassword" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="passwordErrors.current_password" class="text-red-400 text-xs font-medium mt-1">{{ passwordErrors.current_password }}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('newPassword') }}</label>
                                    <div class="relative">
                                        <input
                                            v-model="passwordForm.password"
                                            :type="showNewPassword ? 'text' : 'password'"
                                            class="w-full px-4 py-2.5 pr-11 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                            :style="`--tw-ring-color: ${themeColors.primary}`"
                                            :placeholder="t('enterNewPassword')"
                                        />
                                        <button
                                            type="button"
                                            @click="showNewPassword = !showNewPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors"
                                        >
                                            <svg v-if="showNewPassword" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="passwordErrors.password" class="text-red-400 text-xs font-medium mt-1">{{ passwordErrors.password }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ t('passwordRequirements') }}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('confirmNewPassword') }}</label>
                                    <div class="relative">
                                        <input
                                            v-model="passwordForm.password_confirmation"
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            class="w-full px-4 py-2.5 pr-11 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                            :style="`--tw-ring-color: ${themeColors.primary}`"
                                            :placeholder="t('confirmYourNewPassword')"
                                        />
                                        <button
                                            type="button"
                                            @click="showConfirmPassword = !showConfirmPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors"
                                        >
                                            <svg v-if="showConfirmPassword" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="passwordErrors.password_confirmation" class="text-red-400 text-xs font-medium mt-1">{{ passwordErrors.password_confirmation }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="passwordProcessing"
                                    class="w-full px-5 py-2.5 rounded-lg font-semibold text-sm text-slate-900 transition-all hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); --tw-ring-color: ${themeColors.primary}`"
                                >
                                    <span v-if="passwordProcessing">{{ t('updating') }}...</span>
                                    <span v-else>{{ t('updatePassword') }}</span>
                                </button>
                            </form>
                        </section>
                    </div>

                    <div class="space-y-5 sm:space-y-6">
                        <section class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6">
                            <h2 class="text-lg sm:text-xl font-bold text-white mb-5">{{ t('accountInformation') }}</h2>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                    <div>
                                        <p class="text-xs font-medium text-slate-400">{{ t('walletAddress') }}</p>
                                        <p class="text-sm font-semibold text-white font-mono mt-0.5">
                                            {{ user.wallet?.address ? user.wallet.address.substring(0, 10) + '...' + user.wallet.address.substring(user.wallet.address.length - 8) : t('notAvailable') }}
                                        </p>
                                    </div>
                                    <button
                                        v-if="user.wallet?.address"
                                        @click="copyWalletAddress"
                                        class="p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors"
                                        :style="`color: ${themeColors.primary}`"
                                    >
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                    <div>
                                        <p class="text-xs font-medium text-slate-400">{{ t('walletBalance') }}</p>
                                        <p class="text-sm font-semibold text-white mt-0.5">
                                            {{ formatCurrency(user.wallet?.balance || 0) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                    <div>
                                        <p class="text-xs font-medium text-slate-400">{{ t('bonusBalance') }}</p>
                                        <p class="text-sm font-semibold text-white mt-0.5">
                                            {{ formatCurrency(user.wallet?.bonus_balance || 0) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                    <div>
                                        <p class="text-xs font-medium text-slate-400">{{ t('totalInvested') }}</p>
                                        <p class="text-sm font-semibold text-white mt-0.5">
                                            {{ formatCurrency(user.wallet?.total_invested || 0) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                    <div>
                                        <p class="text-xs font-medium text-slate-400">{{ t('totalWithdraw') }}</p>
                                        <p class="text-sm font-semibold text-white mt-0.5">
                                            {{ formatCurrency(user.wallet?.total_withdrawn || 0) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                    <div>
                                        <p class="text-xs font-medium text-slate-400">{{ t('referralCode') }}</p>
                                        <p class="text-sm font-semibold text-white font-mono mt-0.5">{{ user.referral_code }}</p>
                                    </div>
                                    <button
                                        @click="copyReferralCode"
                                        class="p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors"
                                        :style="`color: ${themeColors.primary}`"
                                    >
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30">
                                    <div>
                                        <p class="text-xs font-medium text-slate-400">{{ t('memberSince') }}</p>
                                        <p class="text-sm font-semibold text-white mt-0.5">{{ formatDate(user.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6">
                            <div class="flex items-center justify-between mb-5">
                                <h2 class="text-lg sm:text-xl font-bold text-white">{{ t('kycVerification') }}</h2>
                                <span
                                    class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :style="getKycStatusStyle(user.kyc_status)"
                                >
                                    {{ getKycStatusLabel(user.kyc_status) }}
                                </span>
                            </div>

                            <div v-if="!user.kyc_status || user.kyc_status === 'pending' || user.kyc_status === 'rejected'">
                                <form @submit.prevent="submitKyc" class="space-y-5">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('firstName') }}</label>
                                            <input
                                                v-model="kycForm.first_name"
                                                type="text"
                                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                            />
                                            <p v-if="errors.first_name" class="text-red-400 text-xs font-medium mt-1">{{ errors.first_name }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('lastName') }}</label>
                                            <input
                                                v-model="kycForm.last_name"
                                                type="text"
                                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                            />
                                            <p v-if="errors.last_name" class="text-red-400 text-xs font-medium mt-1">{{ errors.last_name }}</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('dateOfBirth') }}</label>
                                        <input
                                            v-model="kycForm.date_of_birth"
                                            type="date"
                                            class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                            :style="`--tw-ring-color: ${themeColors.primary}`"
                                        />
                                        <p v-if="errors.date_of_birth" class="text-red-400 text-xs font-medium mt-1">{{ errors.date_of_birth }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('phoneNumber') }}</label>
                                        <input
                                            v-model="kycForm.phone"
                                            type="tel"
                                            class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                            :style="`--tw-ring-color: ${themeColors.primary}`"
                                        />
                                        <p v-if="errors.phone" class="text-red-400 text-xs font-medium mt-1">{{ errors.phone }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('address') }}</label>
                                        <input
                                            v-model="kycForm.address"
                                            type="text"
                                            class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                            :style="`--tw-ring-color: ${themeColors.primary}`"
                                        />
                                        <p v-if="errors.address" class="text-red-400 text-xs font-medium mt-1">{{ errors.address }}</p>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('city') }}</label>
                                            <input
                                                v-model="kycForm.city"
                                                type="text"
                                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                            />
                                            <p v-if="errors.city" class="text-red-400 text-xs font-medium mt-1">{{ errors.city }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('state') }}</label>
                                            <input
                                                v-model="kycForm.state"
                                                type="text"
                                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                            />
                                            <p v-if="errors.state" class="text-red-400 text-xs font-medium mt-1">{{ errors.state }}</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('country') }}</label>
                                            <input
                                                v-model="kycForm.country"
                                                type="text"
                                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                            />
                                            <p v-if="errors.country" class="text-red-400 text-xs font-medium mt-1">{{ errors.country }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('postalCode') }}</label>
                                            <input
                                                v-model="kycForm.postal_code"
                                                type="text"
                                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                            />
                                            <p v-if="errors.postal_code" class="text-red-400 text-xs font-medium mt-1">{{ errors.postal_code }}</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('documentType') }}</label>
                                            <select
                                                v-model="kycForm.document_type"
                                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                            >
                                                <option value="">{{ t('selectDocumentType') }}</option>
                                                <option value="passport">{{ t('passport') }}</option>
                                                <option value="driving_license">{{ t('drivingLicense') }}</option>
                                                <option value="national_id">{{ t('nationalId') }}</option>
                                            </select>
                                            <p v-if="errors.document_type" class="text-red-400 text-xs font-medium mt-1">{{ errors.document_type }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('documentNumber') }}</label>
                                            <input
                                                v-model="kycForm.document_number"
                                                type="text"
                                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                            />
                                            <p v-if="errors.document_number" class="text-red-400 text-xs font-medium mt-1">{{ errors.document_number }}</p>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <p class="text-sm font-medium text-slate-400">{{ t('uploadDocuments') }}</p>

                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('documentFrontImage') }}</label>
                                            <input
                                                ref="frontImageInput"
                                                type="file"
                                                accept="image/jpeg,image/png,image/jpg"
                                                @change="handleFrontImageChange"
                                                class="hidden"
                                            />
                                            <button
                                                type="button"
                                                @click="$refs.frontImageInput.click()"
                                                class="w-full px-4 py-6 bg-slate-800/60 border-2 border-dashed border-slate-700/40 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 hover:border-slate-600/50 transition-all duration-300 flex flex-col items-center justify-center gap-2.5"
                                            >
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                                <span class="text-sm font-medium">{{ kycForm.front_image_name || t('clickToUploadFront') }}</span>
                                            </button>
                                            <p v-if="errors.front_image" class="text-red-400 text-xs font-medium mt-1">{{ errors.front_image }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('documentBackImage') }}</label>
                                            <input
                                                ref="backImageInput"
                                                type="file"
                                                accept="image/jpeg,image/png,image/jpg"
                                                @change="handleBackImageChange"
                                                class="hidden"
                                            />
                                            <button
                                                type="button"
                                                @click="$refs.backImageInput.click()"
                                                class="w-full px-4 py-6 bg-slate-800/60 border-2 border-dashed border-slate-700/40 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 hover:border-slate-600/50 transition-all duration-300 flex flex-col items-center justify-center gap-2.5"
                                            >
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                                <span class="text-sm font-medium">{{ kycForm.back_image_name || t('clickToUploadBack') }}</span>
                                            </button>
                                            <p v-if="errors.back_image" class="text-red-400 text-xs font-medium mt-1">{{ errors.back_image }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('selfieWithDocument') }}</label>
                                            <input
                                                ref="selfieInput"
                                                type="file"
                                                accept="image/jpeg,image/png,image/jpg"
                                                @change="handleSelfieChange"
                                                class="hidden"
                                            />
                                            <button
                                                type="button"
                                                @click="$refs.selfieInput.click()"
                                                class="w-full px-4 py-6 bg-slate-800/60 border-2 border-dashed border-slate-700/40 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 hover:border-slate-600/50 transition-all duration-300 flex flex-col items-center justify-center gap-2.5"
                                            >
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span class="text-sm font-medium">{{ kycForm.selfie_name || t('clickToUploadSelfie') }}</span>
                                            </button>
                                            <p v-if="errors.selfie" class="text-red-400 text-xs font-medium mt-1">{{ errors.selfie }}</p>
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="kycProcessing"
                                        class="w-full px-5 py-2.5 rounded-lg font-semibold text-sm text-slate-900 transition-all hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                        :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); --tw-ring-color: ${themeColors.primary}`"
                                    >
                                        <span v-if="kycProcessing">{{ t('submitting') }}...</span>
                                        <span v-else>{{ t('submitKyc') }}</span>
                                    </button>
                                </form>
                            </div>

                            <div v-else-if="user.kyc_status === 'reviewing'" class="text-center py-6">
                                <svg class="w-14 h-14 mx-auto mb-3 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-slate-300 font-medium text-sm">{{ t('kycUnderReview') }}</p>
                            </div>

                            <div v-else-if="user.kyc_status === 'approved'" class="text-center py-6">
                                <svg class="w-14 h-14 mx-auto mb-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-slate-300 font-medium text-sm">{{ t('kycApproved') }}</p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue"
import { useToast } from "@/composables/useToast.js"

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})

const page = usePage()
const { t } = useTranslation()
const { showToast } = useToast()

const isProcessing = ref(false)
const avatarUploading = ref(false)
const avatarRemoving = ref(false)
const kycProcessing = ref(false)
const passwordProcessing = ref(false)
const avatarPreview = ref(null)

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const currentUser = ref({ ...props.user })

const errors = computed(() => page.props.errors || {})
const passwordErrors = computed(() => {
    const err = page.props.errors || {}
    return {
        current_password: err.current_password,
        password: err.password,
        password_confirmation: err.password_confirmation
    }
})
const primaryColor = computed(() => page.props.primaryColor || '#86efac')

const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 134, g: 239, b: 172 }
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
        primaryRgb: `${rgb.r}, ${rgb.g}, ${rgb.b}`
    }
})

const profileForm = ref({
    name: props.user.name,
    email: props.user.email,
    gender: props.user.gender
})

const passwordForm = ref({
    current_password: '',
    password: '',
    password_confirmation: ''
})

const kycForm = ref({
    first_name: '',
    last_name: '',
    date_of_birth: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    country: '',
    postal_code: '',
    document_type: '',
    document_number: '',
    front_image: null,
    back_image: null,
    selfie: null,
    front_image_name: '',
    back_image_name: '',
    selfie_name: ''
})

const handleAvatarChange = (event) => {
    const file = event.target.files[0]
    if (!file) return

    if (!file.type.startsWith('image/')) {
        showToast(t('selectValidImageFile') || 'Please select a valid image file', 'error')
        return
    }

    if (file.size > 2 * 1024 * 1024) {
        showToast(t('imageSizeTooLarge') || 'Image size must not exceed 2MB', 'error')
        return
    }

    const reader = new FileReader()
    reader.onload = (e) => {
        avatarPreview.value = e.target.result
    }
    reader.readAsDataURL(file)

    avatarUploading.value = true
    const formData = new FormData()
    formData.append('avatar', file)
    formData.append('name', profileForm.value.name)
    formData.append('email', profileForm.value.email)
    formData.append('gender', profileForm.value.gender || '')

    router.post('/user/settings/profile', formData, {
        preserveScroll: true,
        onSuccess: (page) => {
            avatarPreview.value = null
            if (page.props.user) {
                currentUser.value = { ...page.props.user }
            }
            showToast(t('avatarUpdatedSuccess') || 'Avatar updated successfully!', 'success')
        },
        onError: () => {
            avatarPreview.value = null
            showToast(t('avatarUpdateFailed') || 'Failed to update avatar', 'error')
        },
        onFinish: () => {
            avatarUploading.value = false
            event.target.value = ''
        }
    })
}

const removeAvatar = () => {
    avatarRemoving.value = true

    router.delete('/user/settings/avatar', {
        preserveScroll: true,
        onSuccess: (page) => {
            currentUser.value.avatar = null
            currentUser.value.avatar_url = null
            if (page.props.user) {
                currentUser.value = { ...page.props.user }
            }
            showToast(t('avatarRemovedSuccess') || 'Avatar removed successfully!', 'success')
        },
        onError: () => {
            showToast(t('avatarRemoveFailed') || 'Failed to remove avatar', 'error')
        },
        onFinish: () => {
            avatarRemoving.value = false
        }
    })
}

const copyWalletAddress = async () => {
    if (!props.user.wallet?.address) return

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(props.user.wallet.address)
            showToast(t('walletAddressCopied') || 'Wallet address copied!', 'success')
        } else {
            fallbackCopyToClipboard(props.user.wallet.address, t('walletAddressCopied') || 'Wallet address copied!')
        }
    } catch (err) {
        console.error('Failed to copy:', err)
        fallbackCopyToClipboard(props.user.wallet.address, t('walletAddressCopied') || 'Wallet address copied!')
    }
}

const copyReferralCode = async () => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(props.user.referral_code)
            showToast(t('referralCodeCopied') || 'Referral code copied!', 'success')
        } else {
            fallbackCopyToClipboard(props.user.referral_code, t('referralCodeCopied') || 'Referral code copied!')
        }
    } catch (err) {
        console.error('Failed to copy:', err)
        fallbackCopyToClipboard(props.user.referral_code, t('referralCodeCopied') || 'Referral code copied!')
    }
}

const fallbackCopyToClipboard = (text, successMessage) => {
    try {
        const textArea = document.createElement('textarea')
        textArea.value = text
        textArea.style.position = 'fixed'
        textArea.style.left = '-999999px'
        textArea.style.top = '-999999px'
        document.body.appendChild(textArea)
        textArea.focus()
        textArea.select()

        const successful = document.execCommand('copy')
        textArea.remove()

        if (successful) {
            showToast(successMessage, 'success')
        } else {
            throw new Error('Copy command was unsuccessful')
        }
    } catch (err) {
        console.error('Fallback copy failed:', err)
        showToast(t('failedToCopy') || 'Failed to copy. Please copy manually.', 'error')
    }
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    }).format(amount)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const handleFrontImageChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        kycForm.value.front_image = file
        kycForm.value.front_image_name = file.name
    }
}

const handleBackImageChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        kycForm.value.back_image = file
        kycForm.value.back_image_name = file.name
    }
}

const handleSelfieChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        kycForm.value.selfie = file
        kycForm.value.selfie_name = file.name
    }
}

const updateProfile = () => {
    isProcessing.value = true

    const formData = new FormData()
    formData.append('name', profileForm.value.name)
    formData.append('email', profileForm.value.email)
    formData.append('gender', profileForm.value.gender || '')

    router.post('/user/settings/profile', formData, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.user) {
                currentUser.value = { ...page.props.user }
                profileForm.value.name = page.props.user.name
                profileForm.value.email = page.props.user.email
                profileForm.value.gender = page.props.user.gender
            }
            showToast(t('profileUpdatedSuccess') || 'Profile updated successfully!', 'success')
        },
        onError: () => {
            showToast(t('profileUpdateFailed') || 'Failed to update profile', 'error')
        },
        onFinish: () => {
            isProcessing.value = false
        }
    })
}

const updatePassword = () => {
    // Validation
    if (!passwordForm.value.current_password) {
        showToast(t('currentPasswordRequired') || 'Current password is required', 'error')
        return
    }

    if (!passwordForm.value.password) {
        showToast(t('newPasswordRequired') || 'New password is required', 'error')
        return
    }

    if (passwordForm.value.password.length < 8) {
        showToast(t('passwordMinLength') || 'Password must be at least 8 characters', 'error')
        return
    }

    if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
        showToast(t('passwordMismatch') || 'Passwords do not match', 'error')
        return
    }

    passwordProcessing.value = true

    router.post('/user/settings/password', passwordForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            // Reset form
            passwordForm.value = {
                current_password: '',
                password: '',
                password_confirmation: ''
            }
            // Reset password visibility
            showCurrentPassword.value = false
            showNewPassword.value = false
            showConfirmPassword.value = false

            showToast(t('passwordUpdatedSuccess') || 'Password updated successfully!', 'success')
        },
        onError: (errors) => {
            console.error('Password update errors:', errors)
            if (errors.current_password) {
                showToast(errors.current_password, 'error')
            } else if (errors.password) {
                showToast(errors.password, 'error')
            } else {
                showToast(t('passwordUpdateFailed') || 'Failed to update password', 'error')
            }
        },
        onFinish: () => {
            passwordProcessing.value = false
        }
    })
}

const submitKyc = () => {
    kycProcessing.value = true

    const formData = new FormData()
    formData.append('first_name', kycForm.value.first_name)
    formData.append('last_name', kycForm.value.last_name)
    formData.append('date_of_birth', kycForm.value.date_of_birth)
    formData.append('phone', kycForm.value.phone)
    formData.append('address', kycForm.value.address)
    formData.append('city', kycForm.value.city)
    formData.append('state', kycForm.value.state)
    formData.append('country', kycForm.value.country)
    formData.append('postal_code', kycForm.value.postal_code)
    formData.append('document_type', kycForm.value.document_type)
    formData.append('document_number', kycForm.value.document_number)
    formData.append('front_image', kycForm.value.front_image)
    formData.append('back_image', kycForm.value.back_image)
    formData.append('selfie', kycForm.value.selfie)

    router.post('/user/settings/kyc', formData, {
        preserveScroll: true,
        onSuccess: () => {
            kycForm.value = {
                first_name: '',
                last_name: '',
                date_of_birth: '',
                phone: '',
                address: '',
                city: '',
                state: '',
                country: '',
                postal_code: '',
                document_type: '',
                document_number: '',
                front_image: null,
                back_image: null,
                selfie: null,
                front_image_name: '',
                back_image_name: '',
                selfie_name: ''
            }
            const fileInputs = document.querySelectorAll('input[type="file"]')
            fileInputs.forEach(input => input.value = '')

            showToast(t('kycSubmittedSuccess') || 'KYC submitted successfully!', 'success')
        },
        onError: () => {
            showToast(t('kycSubmitFailed') || 'Failed to submit KYC', 'error')
        },
        onFinish: () => {
            kycProcessing.value = false
        }
    })
}

const getKycStatusStyle = (status) => {
    const styles = {
        pending: { backgroundColor: '#64748b20', color: '#64748b' },
        reviewing: { backgroundColor: '#f59e0b20', color: '#f59e0b' },
        approved: { backgroundColor: '#10b98120', color: '#10b981' },
        rejected: { backgroundColor: '#ef444420', color: '#ef4444' }
    }
    return styles[status] || styles.pending
}

const getKycStatusLabel = (status) => {
    const labels = {
        pending: t('pending'),
        reviewing: t('reviewing'),
        approved: t('approved'),
        rejected: t('rejected')
    }
    return labels[status] || status
}

onMounted(() => {
    const flash = page.props.flash
    if (flash?.success) showToast(flash.success, 'success')
    if (flash?.error) showToast(flash.error, 'error')
    if (flash?.warning) showToast(flash.warning, 'warning')
    if (flash?.info) showToast(flash.info, 'info')
})
</script>

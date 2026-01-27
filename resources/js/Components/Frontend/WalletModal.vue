<template>
    <div
        v-if="show"
        class="fixed inset-0 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fade-in"
        :style="modalBackdropStyle"
        @click="handleBackdropClick"
    >
        <div
            class="relative w-full max-w-md mx-auto backdrop-blur-2xl border rounded-2xl p-5 sm:p-6 animate-scale-in"
            :style="modalStyle"
            @click.stop
        >
            <button
                @click="closeModal"
                class="absolute top-4 right-4 text-white/50 hover:text-white transition-all p-2 rounded-lg hover:bg-white/10 active:scale-95"
                :disabled="connecting"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div v-if="connecting" class="text-center py-10">
                <div class="w-16 h-16 mx-auto mb-5 border-4 border-white/20 rounded-full animate-spin"
                     :style="{ borderTopColor: themeColors.primary }"></div>
                <h3 class="text-xl font-black mb-2 text-white tracking-tight">{{ connectingMessage }}</h3>
                <p class="text-white/60 text-sm mb-5">{{ statusMessage }}</p>

                <div v-if="selectedWallet"
                     class="inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-white/5 border border-white/10">
                    <div v-if="selectedWallet === 'email'" class="w-9 h-9 rounded-lg flex items-center justify-center"
                         :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` }">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <img v-else :src="getWalletIcon(selectedWallet)" alt="Wallet Icon" class="wallet-svg w-9 h-9" />
                    <span class="font-bold text-sm" :style="{ color: themeColors.primary }">
                        {{ selectedWallet === 'email' ? t('walletModalEmailLogin') : getWalletName(selectedWallet) }}
                    </span>
                </div>
            </div>

            <div v-else-if="showMobileConnection" class="text-center py-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-xl flex items-center justify-center"
                     :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` }">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-black mb-2 text-white tracking-tight">Connect via MetaMask Mobile</h3>
                <p class="text-white/60 text-sm mb-6">Scan QR code or copy URL to open in MetaMask</p>

                <!-- QR Code -->
                <div v-if="qrCodeDataUrl" class="mb-6 flex justify-center">
                    <div class="p-4 bg-white rounded-xl">
                        <img :src="qrCodeDataUrl" alt="QR Code" class="w-48 h-48" />
                    </div>
                </div>

                <!-- Copy URL Button -->
                <div class="mb-6">
                    <div class="flex items-center gap-2 p-3 rounded-xl bg-white/5 border border-white/10 mb-3">
                        <input
                            type="text"
                            :value="mobileConnectionUrl"
                            readonly
                            class="flex-1 bg-transparent text-white text-sm font-mono text-center outline-none"
                        />
                        <button
                            @click="copyUrlToClipboard"
                            class="px-4 py-2 rounded-lg font-bold text-xs transition-all hover:scale-105 active:scale-95"
                            :style="urlCopied ? { background: '#10b981', color: '#fff' } : { background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)`, color: '#fff' }"
                        >
                            {{ urlCopied ? 'Copied!' : 'Copy' }}
                        </button>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="text-left bg-white/5 rounded-xl p-4 mb-6 border border-white/10">
                    <p class="text-white/80 text-sm mb-3 font-semibold">Steps:</p>
                    <ol class="text-white/60 text-xs space-y-2 list-decimal list-inside">
                        <li>Open MetaMask Mobile app</li>
                        <li>Tap the browser icon (🌐) at the bottom</li>
                        <li>Scan the QR code or paste the URL</li>
                        <li>Once loaded, click "Connect Wallet" → "MetaMask"</li>
                    </ol>
                </div>

                <button
                    @click="showMobileConnection = false; error = null"
                    class="px-6 py-3 rounded-xl font-bold text-sm transition-all hover:scale-105 active:scale-95"
                    :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)`, color: '#fff' }"
                >
                    Got it
                </button>
            </div>

            <div v-else-if="error" class="text-center py-10">
                <div class="w-16 h-16 mx-auto mb-5 rounded-xl bg-red-500/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-black mb-2 text-white tracking-tight">{{ t('walletModalConnectionFailed') }}</h3>
                <p class="text-red-400 text-sm mb-6 leading-relaxed max-w-sm mx-auto" v-html="formatErrorMessage(error)"></p>
                <div class="flex gap-3 justify-center">
                    <button
                        @click="retry"
                        class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all hover:scale-105 active:scale-95"
                        :style="{
                            background: `rgba(${themeColors.primaryRgb}, 0.2)`,
                            borderWidth: '2px',
                            borderStyle: 'solid',
                            borderColor: themeColors.primary,
                            color: themeColors.primary
                        }"
                    >
                        {{ t('walletModalTryAgain') }}
                    </button>
                    <a
                        v-if="error.includes('not installed') || error.includes('Not Installed')"
                        href="https://metamask.io/download/"
                        target="_blank"
                        class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all hover:scale-105 active:scale-95 text-white"
                        :style="{
                            background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)`
                        }"
                    >
                        Install MetaMask
                    </a>
                </div>
            </div>

            <div v-else-if="showMetaMaskSuccess" class="text-center py-10">
                <div class="w-16 h-16 mx-auto mb-5 rounded-xl flex items-center justify-center animate-pulse-slow"
                     :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` }">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-black mb-2 text-white tracking-tight">Successfully Connected!</h3>
                <p class="text-white/70 text-sm mb-6 leading-relaxed max-w-sm mx-auto">
                    You're now logged in. Please <strong>continue using MetaMask's browser</strong> to access your account.
                </p>
                <p class="text-white/50 text-xs mb-6 leading-relaxed max-w-sm mx-auto">
                    ⚠️ Don't switch back to Chrome - your session is only active in MetaMask's browser.
                </p>
                <button
                    @click="window.location.href = '/user/dashboard'"
                    class="px-6 py-3 rounded-xl font-black text-sm text-white transition-all hover:scale-105 active:scale-95"
                    :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` }"
                >
                    Go to Dashboard
                </button>
            </div>

            <div v-else-if="resetSuccess" class="text-center py-10">
                <div class="w-16 h-16 mx-auto mb-5 rounded-xl flex items-center justify-center"
                     :style="{ background: `rgba(${themeColors.primaryRgb}, 0.2)` }">
                    <svg class="w-8 h-8" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-black mb-2 text-white tracking-tight">{{ t('walletModalPasswordResetSent') }}</h3>
                <p class="text-white/70 text-sm mb-6 leading-relaxed max-w-sm mx-auto">{{ resetMessage }}</p>
                <button
                    @click="resetSuccess = false; showForgotPassword = false; showEmailForm = true"
                    class="px-6 py-2.5 rounded-xl font-black text-sm text-white transition-all hover:scale-105 active:scale-95"
                    :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` }"
                >
                    {{ t('walletModalBackToLogin') }}
                </button>
            </div>

            <div v-else>
                <div class="text-center mb-6">
                    <div
                        class="w-14 h-14 mx-auto mb-4 rounded-xl flex items-center justify-center animate-pulse-slow"
                        :style="modalIconStyle">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-black mb-2 text-white tracking-tight leading-tight">
                        {{ showForgotPassword ? t('walletModalForgotPasswordTitle') : showEmailForm ? t('walletModalManualLoginTitle') : t('walletModalConnectWalletTitle') }}
                    </h3>
                    <p class="text-white/70 text-sm leading-relaxed font-light">
                        {{ showForgotPassword ? t('walletModalForgotPasswordDesc') : showEmailForm ? t('walletModalManualLoginDesc') : t('walletModalConnectWalletDesc') }}
                    </p>
                </div>

                <div v-if="showForgotPassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-white/90 mb-2 tracking-tight">{{ t('walletModalEmailLabel') }}</label>
                        <input
                            v-model="forgotEmail"
                            type="email"
                            :placeholder="t('walletModalEmailPlaceholder')"
                            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white text-sm placeholder-white/40 focus:outline-none focus:border-white/50 focus:bg-white/[0.15] transition-all font-light"
                            @keyup.enter="handleForgotPassword"
                        />
                    </div>

                    <button
                        @click="handleForgotPassword"
                        :disabled="!forgotEmail"
                        class="w-full py-3 rounded-xl font-black text-sm text-white tracking-tight transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                        :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` }"
                    >
                        {{ t('walletModalResetPassword') }}
                    </button>

                    <button
                        @click="showForgotPassword = false; showEmailForm = true"
                        class="w-full py-2.5 text-white/60 hover:text-white transition-colors text-sm font-semibold tracking-tight"
                    >
                        {{ t('walletModalBackToLoginLink') }}
                    </button>
                </div>

                <div v-else-if="showEmailForm" class="space-y-4">
                    <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10 mb-5">
                        <button
                            @click="authMode = 'login'"
                            class="flex-1 py-2 rounded-lg font-bold text-sm transition-all"
                            :class="authMode === 'login' ? 'text-white' : 'text-white/50'"
                            :style="authMode === 'login' ? { background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` } : {}"
                        >
                            {{ t('walletModalLoginTab') }}
                        </button>
                        <button
                            @click="authMode = 'register'"
                            class="flex-1 py-2 rounded-lg font-bold text-sm transition-all"
                            :class="authMode === 'register' ? 'text-white' : 'text-white/50'"
                            :style="authMode === 'register' ? { background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` } : {}"
                        >
                            {{ t('walletModalRegisterTab') }}
                        </button>
                    </div>

                    <div v-if="authMode === 'login'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-white/90 mb-2 tracking-tight">{{ t('walletModalEmailLabel') }}</label>
                            <input
                                v-model="email"
                                type="email"
                                :placeholder="t('walletModalEmailPlaceholder')"
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white text-sm placeholder-white/40 focus:outline-none focus:border-white/50 focus:bg-white/[0.15] transition-all font-light"
                                @keyup.enter="handleEmailLogin"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-white/90 mb-2 tracking-tight">{{ t('walletModalPasswordLabel') }}</label>
                            <input
                                v-model="password"
                                type="password"
                                :placeholder="t('walletModalPasswordPlaceholder')"
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white text-sm placeholder-white/40 focus:outline-none focus:border-white/50 focus:bg-white/[0.15] transition-all font-light"
                                @keyup.enter="handleEmailLogin"
                            />
                        </div>

                        <div class="flex justify-end">
                            <button
                                @click="showForgotPassword = true; showEmailForm = false"
                                class="text-sm font-semibold tracking-tight transition-colors"
                                :style="{ color: themeColors.primary }"
                            >
                                {{ t('walletModalForgotPasswordLink') }}
                            </button>
                        </div>

                        <button
                            @click="handleEmailLogin"
                            :disabled="!email || !password"
                            class="w-full py-3 rounded-xl font-black text-sm text-white tracking-tight transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                            :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` }"
                        >
                            {{ t('walletModalLoginButton') }}
                        </button>
                    </div>

                    <div v-else-if="authMode === 'register'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-white/90 mb-2 tracking-tight">{{ t('walletModalEmailLabel') }}</label>
                            <input
                                v-model="registerEmail"
                                type="email"
                                :placeholder="t('walletModalEmailPlaceholder')"
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white text-sm placeholder-white/40 focus:outline-none focus:border-white/50 focus:bg-white/[0.15] transition-all font-light"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-white/90 mb-2 tracking-tight">{{ t('walletModalPasswordLabel') }}</label>
                            <input
                                v-model="registerPassword"
                                type="password"
                                :placeholder="t('walletModalPasswordPlaceholder')"
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white text-sm placeholder-white/40 focus:outline-none focus:border-white/50 focus:bg-white/[0.15] transition-all font-light"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-white/90 mb-2 tracking-tight">{{ t('walletModalConfirmPasswordLabel') }}</label>
                            <input
                                v-model="registerConfirmPassword"
                                type="password"
                                :placeholder="t('walletModalConfirmPasswordPlaceholder')"
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white text-sm placeholder-white/40 focus:outline-none focus:border-white/50 focus:bg-white/[0.15] transition-all font-light"
                            />
                        </div>

                        <button
                            @click="handleEmailRegister"
                            :disabled="!registerEmail || !registerPassword || !registerConfirmPassword"
                            class="w-full py-3 rounded-xl font-black text-sm text-white tracking-tight transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                            :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` }"
                        >
                            {{ t('walletModalRegisterButton') }}
                        </button>
                    </div>

                    <button
                        @click="showEmailForm = false"
                        class="w-full py-2.5 text-white/60 hover:text-white transition-colors text-sm font-semibold tracking-tight"
                    >
                        {{ t('walletModalBackToWallets') }}
                    </button>
                </div>

                <div v-else class="space-y-3">
                    <button
                        v-for="wallet in availableWallets"
                        :key="wallet.type"
                        @click="connectWallet(wallet.type)"
                        :disabled="connecting || isProcessing"
                        class="group w-full flex items-center gap-3 p-3.5 rounded-xl border transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                        :style="{
                            background: wallet.isInstalled ? 'rgba(255, 255, 255, 0.05)' : 'rgba(255, 255, 255, 0.02)',
                            borderColor: wallet.isInstalled ? 'rgba(255, 255, 255, 0.1)' : 'rgba(255, 255, 255, 0.05)'
                        }"
                    >
                        <img :src="wallet.icon" :alt="wallet.name" class="wallet-svg w-9 h-9" :class="{ 'opacity-50': !wallet.isInstalled }" />
                        <div class="flex-1 text-left">
                            <div class="font-bold text-sm text-white tracking-tight flex items-center gap-2">
                                {{ wallet.name }}
                                <span v-if="!wallet.isInstalled" class="text-xs text-yellow-400 font-normal">(Not Installed)</span>
                            </div>
                            <div class="text-xs text-white/60 font-light">
                                <span v-if="wallet.isInstalled">{{ wallet.description }}</span>
                                <span v-else-if="wallet.type === 'walletconnect'" class="text-white/70">
                                    {{ wallet.description }}
                                    <span v-if="isMobile()" class="block mt-1 text-green-400/80 font-semibold">✓ Recommended for mobile Chrome</span>
                                </span>
                                <span v-else-if="isMobile()" class="text-yellow-400/80">
                                    <span v-if="wallet.type === 'metamask'">
                                        Open MetaMask mobile app to connect.
                                        <a href="https://metamask.io/download/" target="_blank" class="underline hover:text-yellow-300 font-semibold" @click.stop>Get MetaMask Mobile</a>
                                    </span>
                                    <span v-else>
                                        Install {{ wallet.name }} to connect.
                                    </span>
                                </span>
                                <span v-else class="text-yellow-400/80">
                                    MetaMask extension is not installed.
                                    <a href="https://metamask.io/download/" target="_blank" class="underline hover:text-yellow-300 font-semibold" @click.stop>Install MetaMask</a>
                                    <span class="block mt-1 text-xs">After installing, refresh this page to connect.</span>
                                </span>
                            </div>
                        </div>
                        <svg v-if="wallet.isInstalled || isMobile()" class="w-5 h-5 text-white/40 group-hover:text-white/60 transition-colors" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5l7 7-7 7"></path>
                        </svg>
                        <svg v-else class="w-5 h-5 text-yellow-400/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </button>

                    <button
                        @click="showEmailForm = true"
                        class="group w-full flex items-center gap-3 p-3.5 rounded-xl border transition-all hover:scale-[1.02] active:scale-[0.98]"
                        :style="{
                            background: 'rgba(255, 255, 255, 0.05)',
                            borderColor: 'rgba(255, 255, 255, 0.1)'
                        }"
                    >
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                             :style="{ background: `linear-gradient(135deg, ${themeColors.primary} 0%, ${themeColors.primaryDark} 100%)` }">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="font-bold text-sm text-white tracking-tight">{{ t('walletModalEmailTitle') }}</div>
                            <div class="text-xs text-white/60 font-light">{{ t('walletModalEmailDesc') }}</div>
                        </div>
                        <svg class="w-5 h-5 text-white/40 group-hover:text-white/60 transition-colors" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>

                <p class="text-xs text-white/40 text-center mt-5 leading-relaxed font-light">
                    {{ t('walletModalTerms') }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {usePage} from '@inertiajs/vue3'
import {useTranslation} from '@/composables/useTranslation'
import {useToast} from "@/composables/useToast.js";
import QRCode from 'qrcode'
import EthereumProvider from '@walletconnect/ethereum-provider'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'success'])

const page = usePage()
const {t} = useTranslation()
const { showToast } = useToast();
const primaryColor = computed(() => page.props.primaryColor || '#86efac')

const hexToRgb = (hex) => {
    if (!hex || typeof hex !== 'string') {
        return {r: 134, g: 239, b: 172}
    }
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : {r: 134, g: 239, b: 172}
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

const modalBackdropStyle = computed(() => ({
    backgroundColor: 'rgba(0, 0, 0, 0.8)'
}))

const modalStyle = computed(() => ({
    background: 'linear-gradient(135deg, rgba(17, 24, 39, 0.95) 0%, rgba(31, 41, 55, 0.95) 100%)',
    borderColor: 'rgba(255, 255, 255, 0.1)',
    boxShadow: `0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(${themeColors.value.primaryRgb}, 0.1)`
}))

const modalIconStyle = computed(() => ({
    background: `linear-gradient(135deg, ${themeColors.value.primary} 0%, ${themeColors.value.primaryDark} 100%)`
}))

const walletIcons = {
    metamask: '/assets/metamask.svg',
    walletconnect: 'https://avatars.githubusercontent.com/u/37784886?s=200&v=4' // WalletConnect logo from GitHub
}

const connecting = ref(false)
const error = ref(null)
const selectedWallet = ref('')
const connectingMessage = ref(t('walletModalConnecting'))
const statusMessage = ref(t('walletModalConfirmWallet'))
const isProcessing = ref(false)
const showEmailForm = ref(false)
const showForgotPassword = ref(false)
const resetSuccess = ref(false)
const resetMessage = ref('')
const showMetaMaskSuccess = ref(false)
const email = ref('')
const password = ref('')
const forgotEmail = ref('')
const availableWallets = ref([])
const authMode = ref('login')
const registerEmail = ref('')
const registerPassword = ref('')
const registerConfirmPassword = ref('')
const showMobileConnection = ref(false)
const mobileConnectionUrl = ref('')
const qrCodeDataUrl = ref('')
const urlCopied = ref(false)

// WalletConnect state
let walletConnectProvider = null
const isWalletConnectInitializing = ref(false)

const getWalletConnectProvider = async () => {
    if (walletConnectProvider) {
        return walletConnectProvider
    }

    const projectId = import.meta.env.VITE_WALLETCONNECT_PROJECT_ID
    const chainIdEnv = import.meta.env.VITE_WALLETCONNECT_CHAIN_ID || import.meta.env.VITE_CHAIN_ID
    const chainId = chainIdEnv ? Number(chainIdEnv) : 1

    if (!projectId) {
        throw new Error('WalletConnect is not configured. To enable wallet login on mobile Chrome, please set VITE_WALLETCONNECT_PROJECT_ID in your environment. Get a free project ID at https://cloud.walletconnect.com')
    }

    try {
        isWalletConnectInitializing.value = true
        walletConnectProvider = await EthereumProvider.init({
            projectId,
            showQrModal: true,
            chains: [chainId],
            metadata: {
                name: 'MetaSets',
                description: 'MetaSets Platform',
                url: typeof window !== 'undefined' ? window.location.origin : '',
                icons: typeof window !== 'undefined' ? [`${window.location.origin}/assets/metamask.svg`] : []
            }
        })

        return walletConnectProvider
    } catch (err) {
        console.error('WalletConnect initialization error:', err)
        throw new Error('Failed to initialize WalletConnect. Please check your project ID and try again.')
    } finally {
        isWalletConnectInitializing.value = false
    }
}

// Detect if device is mobile
const isMobile = () => {
    if (typeof window === 'undefined') return false
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
           (window.innerWidth <= 768)
}

// Detect if MetaMask mobile app is installed or in-app browser is being used
const isMetaMaskMobileInstalled = () => {
    if (typeof window === 'undefined') return false
    const userAgent = navigator.userAgent.toLowerCase()
    // Check if using MetaMask Mobile's in-app browser
    if (userAgent.includes('metamask') || userAgent.includes('mmbrowser')) {
        return true
    }
    // Check if ethereum provider is injected (MetaMask Mobile browser injects it)
    if (window.ethereum && typeof window.ethereum.request === 'function') {
        return true
    }
    return false
}

// Detect if Trust Wallet is installed
const isTrustWalletInstalled = () => {
    if (typeof window === 'undefined') return false
    return window.ethereum?.isTrust ||
           window.trustwallet !== undefined
}

// Detect if Coinbase Wallet is installed
const isCoinbaseWalletInstalled = () => {
    if (typeof window === 'undefined') return false
    return window.ethereum?.isCoinbaseWallet ||
           window.coinbaseWalletExtension !== undefined
}

const walletConfigs = []

walletConfigs.push({
    type: 'metamask',
    name: 'MetaMask',
    description: t('Connect using MetaMask wallet'),
    provider: async () => {
        // First try MetaMask extension (desktop)
        if (typeof window !== 'undefined' && window.ethereum?.isMetaMask) {
            return window.ethereum
        }
        // If extension not available, use WalletConnect to connect to MetaMask Mobile
        // This works on mobile Chrome and desktop without extension
        try {
            return await getWalletConnectProvider()
        } catch (err) {
            // If WalletConnect not configured, return null to show error
            return null
        }
    },
    detectCondition: () => {
        if (typeof window === 'undefined') return false
        // Check for MetaMask desktop extension
        if (window.ethereum?.isMetaMask) return true
        // Check for MetaMask Mobile in-app browser (injects ethereum provider)
        if (isMobile() && window.ethereum && typeof window.ethereum.request === 'function') {
            // On mobile, if ethereum provider exists, it's likely MetaMask Mobile browser
            return true
        }
        // Check user agent for MetaMask Mobile browser
        const userAgent = navigator.userAgent.toLowerCase()
        if (isMobile() && (userAgent.includes('metamask') || userAgent.includes('mmbrowser'))) {
            return true
        }
        return false
    },
    mobileDeepLink: (url) => {
        // MetaMask mobile deep link
        const encodedUrl = encodeURIComponent(url)
        return `https://metamask.app.link/dapp/${encodedUrl}`
    },
    icon: walletIcons.metamask
})

// Always show WalletConnect option (required for mobile Chrome wallet login)
walletConfigs.push({
    type: 'walletconnect',
    name: 'WalletConnect',
    description: isMobile()
        ? 'Connect using WalletConnect (works on mobile Chrome)'
        : 'Connect using WalletConnect (desktop & mobile wallets)',
    provider: async () => {
        return await getWalletConnectProvider()
    },
    detectCondition: () => true,
    mobileDeepLink: null,
    icon: walletIcons.walletconnect
})


const handleEmailRegister = async () => {
    if (!registerEmail.value || !registerPassword.value) {
        error.value = t('walletModalFillAllFields') || 'Please fill all required fields'
        return
    }

    if (registerPassword.value !== registerConfirmPassword.value) {
        error.value = t('walletModalPasswordMismatch') || 'Passwords do not match'
        return
    }

    if (registerPassword.value.length < 8) {
        error.value = t('walletModalPasswordTooShort') || 'Password must be at least 8 characters'
        return
    }

    connecting.value = true
    selectedWallet.value = 'email'
    connectingMessage.value = t('walletModalCreatingAccount') || 'Creating account...'
    statusMessage.value = t('walletModalPleaseWait') || 'Please wait...'
    error.value = null

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        const referralCode = getReferralCode()

        const response = await fetch('/auth/web3/email-register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                email: registerEmail.value,
                password: registerPassword.value,
                password_confirmation: registerConfirmPassword.value,
                referral_code: referralCode
            })
        })

        const data = await response.json()

        if (!response.ok || !data.success) {
            throw new Error(data.message || t('walletModalRegistrationFailed'))
        }

        connectingMessage.value = t('walletModalSuccess') || 'Success!'
        if (data.requires_verification) {
            showToast(data.message, 'info');
            console.log(data.message);
            await new Promise(resolve => setTimeout(resolve, 1500))
            emit('close')
            window.location.href = data.redirect_url || '/'
        } else {
            statusMessage.value = t('walletModalRedirecting') || 'Redirecting...'
            await new Promise(resolve => setTimeout(resolve, 800))

            emit('success', data.user)
            window.location.href = data.redirect_url || '/user/dashboard'
        }

    } catch (err) {
        connecting.value = false
        error.value = err.message || t('walletModalRegistrationFailed')
    }
}

const detectAvailableWallets = () => {
    // Always show available wallets, but mark if they're installed
    availableWallets.value = walletConfigs.map(wallet => {
        const isInstalled = wallet.detectCondition ? wallet.detectCondition() : false
        console.log(`Wallet ${wallet.name} detected:`, isInstalled, 'window.ethereum:', typeof window !== 'undefined' ? window.ethereum : 'undefined')
        return {
            ...wallet,
            isInstalled: isInstalled
        }
    })
}

const getWalletIcon = (type) => {
    return walletIcons[type] || walletIcons.metamask
}

const getWalletName = (type) => {
    const wallet = walletConfigs.find(w => w.type === type)
    return wallet?.name || 'Wallet'
}

const getReferralCode = () => {
    const params = new URLSearchParams(window.location.search)
    return params.get('ref') || localStorage.getItem('referral_code') || null
}

const formatErrorMessage = (errorMsg) => {
    if (!errorMsg) return ''
    // Convert URLs to clickable links
    return errorMsg.replace(
        /(https?:\/\/[^\s]+)/g,
        '<a href="$1" target="_blank" class="underline text-yellow-400 hover:text-yellow-300">$1</a>'
    )
}

const copyUrlToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(mobileConnectionUrl.value)
        urlCopied.value = true
        showToast('URL copied to clipboard!', 'success')
        setTimeout(() => {
            urlCopied.value = false
        }, 2000)
    } catch (err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea')
        textArea.value = mobileConnectionUrl.value
        textArea.style.position = 'fixed'
        textArea.style.opacity = '0'
        document.body.appendChild(textArea)
        textArea.select()
        try {
            document.execCommand('copy')
            urlCopied.value = true
            showToast('URL copied to clipboard!', 'success')
            setTimeout(() => {
                urlCopied.value = false
            }, 2000)
        } catch (e) {
            showToast('Failed to copy URL', 'error')
        }
        document.body.removeChild(textArea)
    }
}

// Handle mobile wallet connection via deep link
const handleMobileWalletConnection = async (walletType, walletConfig) => {
    try {
        if (walletType !== 'metamask') {
            throw new Error('Mobile wallet connection only supported for MetaMask')
        }

        // Store original Chrome URL before opening MetaMask
        const originalChromeUrl = window.location.href
        sessionStorage.setItem('original_chrome_url', originalChromeUrl)

        // Get current page URL
        const origin = window.location.origin
        const pathname = window.location.pathname
        const fullUrl = origin + pathname

        // Check if using HTTP (local development)
        const isHttp = origin.startsWith('http://')
        const isLocalhost = origin.includes('localhost') || origin.includes('127.0.0.1') || /^http:\/\/192\.168\./.test(origin) || /^http:\/\/10\./.test(origin) || /^http:\/\/172\.(1[6-9]|2[0-9]|3[0-1])\./.test(origin)

        // MetaMask deep links cannot access local/private IPs
        // Show QR code and copy button for easier connection
        if (isHttp && isLocalhost) {
            connecting.value = false
            mobileConnectionUrl.value = fullUrl
            showMobileConnection.value = true

            // Generate QR code
            try {
                const qrUrl = await QRCode.toDataURL(fullUrl, {
                    width: 256,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#FFFFFF'
                    }
                })
                qrCodeDataUrl.value = qrUrl
            } catch (err) {
                console.error('QR code generation failed:', err)
            }
            return
        }

        // For HTTPS or public URLs, use deep link
        connecting.value = true
        selectedWallet.value = walletType
        connectingMessage.value = 'Opening MetaMask Mobile...'
        statusMessage.value = 'MetaMask will open and load this site. Then approve the connection.'
        error.value = null

        // Store connection attempt in sessionStorage for callback handling
        // Get original Chrome URL from sessionStorage (set earlier in handleMobileWalletConnection)
        const storedChromeUrl = sessionStorage.getItem('original_chrome_url') || document.referrer || fullUrl
        sessionStorage.setItem('wallet_connection_pending', JSON.stringify({
            walletType: walletType,
            timestamp: Date.now(),
            returnUrl: fullUrl,
            originalChromeUrl: storedChromeUrl,
            autoConnect: true
        }))

        // Detect device type for appropriate deep link
        const userAgent = navigator.userAgent.toLowerCase()
        const isIOS = /iphone|ipad|ipod/.test(userAgent)
        const isAndroid = /android/.test(userAgent)

        // Add query parameter to detect when site loads in MetaMask
        const urlWithParam = new URL(fullUrl)
        urlWithParam.searchParams.set('metamask_connect', 'true')
        const fullUrlWithParam = urlWithParam.toString()

        let deepLinkUrl = null

        if (isAndroid) {
            // Android: Use Intent URL
            deepLinkUrl = `intent://dapp?url=${encodeURIComponent(fullUrlWithParam)}#Intent;scheme=metamask;package=io.metamask;end`
        } else if (isIOS) {
            // iOS: Use universal link
            deepLinkUrl = `https://metamask.app.link/dapp/${encodeURIComponent(fullUrlWithParam)}`
        } else {
            // Fallback: Try universal link
            deepLinkUrl = `https://metamask.app.link/dapp/${encodeURIComponent(fullUrlWithParam)}`
        }

        // Open MetaMask Mobile app
        try {
            window.location.href = deepLinkUrl
        } catch (e) {
            window.open(deepLinkUrl, '_blank')
        }

        // Show instructions if app doesn't open after 5 seconds
        setTimeout(() => {
            if (document.hasFocus() && !window.ethereum) {
                connecting.value = false
                error.value = `MetaMask Mobile app not found. Please:\n1. Install MetaMask Mobile from App Store/Google Play\n2. Then try again\n\nOr open this site directly in MetaMask Mobile's browser:\n${fullUrl}`
            }
        }, 5000)

    } catch (err) {
        connecting.value = false
        error.value = err.message || 'Failed to open MetaMask Mobile. Please install the app and try again, or use email registration.'
    }
}

const handleEmailLogin = async () => {
    if (!email.value || !password.value) return

    try {
        connecting.value = true
        selectedWallet.value = 'email'
        connectingMessage.value = t('walletModalLoggingIn')
        statusMessage.value = t('walletModalAuthenticating')
        error.value = null

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        const referralCode = getReferralCode()

        const response = await fetch('/auth/email/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                email: email.value,
                password: password.value,
                referral_code: referralCode
            })
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            throw new Error(errorData.message || t('walletModalLoginFailed'))
        }

        const data = await response.json()

        if (!data.success) {
            throw new Error(data.message || t('walletModalLoginFailed'))
        }

        connectingMessage.value = t('walletModalSuccess')
        statusMessage.value = t('walletModalRedirecting')

        await new Promise(resolve => setTimeout(resolve, 800))

        emit('success', data.user)

        window.location.href = data.redirect_url || '/user/dashboard'

    } catch (err) {
        error.value = err.message || t('walletModalLoginFailed')
        connecting.value = false
        selectedWallet.value = ''
    }
}

const handleForgotPassword = async () => {
    if (!forgotEmail.value) return

    try {
        connecting.value = true
        error.value = null

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content

        const response = await fetch('/forgot-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                email: forgotEmail.value
            })
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            throw new Error(errorData.message || t('walletModalResetFailed'))
        }

        const data = await response.json()

        if (!data.success) {
            throw new Error(data.message || t('walletModalResetFailed'))
        }

        resetMessage.value = data.message || t('walletModalResetSuccess')
        resetSuccess.value = true
        connecting.value = false

    } catch (err) {
        error.value = err.message || t('walletModalResetFailed')
        connecting.value = false
    }
}

const connectWallet = async (walletType) => {
    if (isProcessing.value || connecting.value) return

    // Check if wallet is supported before proceeding
    const walletConfig = walletConfigs.find(w => w.type === walletType)
    if (!walletConfig) {
        error.value = t('walletModalNotAvailable', {wallet: walletType}) || 'Wallet not available'
        return
    }

    const isInstalled = walletType === 'walletconnect'
        ? true
        : (walletConfig.detectCondition ? walletConfig.detectCondition() : false)
    const mobile = isMobile()

    // Always allow connection attempt - we'll handle errors gracefully if wallet not available

    // Handle MetaMask-style mobile deep link (WalletConnect handles its own deep linking)
    if (walletType !== 'walletconnect' && mobile && walletConfig.mobileDeepLink) {
        // Check if already in MetaMask Mobile's browser (ethereum provider exists)
        const isInMetaMaskBrowser = window.ethereum && typeof window.ethereum.request === 'function'

        if (isInMetaMaskBrowser) {
            // Already in MetaMask Mobile browser - proceed with normal connection
            // This works even on HTTP
        } else if (!isInstalled) {
            // Not in MetaMask browser and wallet not detected - try deep link
            // Use Android Intent or iOS custom scheme (works on HTTP)
            return handleMobileWalletConnection(walletType, walletConfig)
        }
        // If detected on mobile, wallet might be injected by MetaMask Mobile browser
        // Continue with normal flow
    }

    isProcessing.value = true
    connecting.value = true
    error.value = null
    selectedWallet.value = walletType

    connectingMessage.value = t('walletModalConnecting')
    statusMessage.value = t('walletModalConfirmWallet')

    try {
        if (!walletConfig.provider) {
            throw new Error(t('walletModalNotAvailable', {wallet: walletType}))
        }

        const provider = await walletConfig.provider()

        if (!provider) {
            const walletName = walletConfig.name || walletType
            if (walletType === 'metamask') {
                // MetaMask: if extension not available and WalletConnect not configured, show helpful error
                throw new Error(`${walletName} extension is not installed and WalletConnect is not configured. To connect MetaMask on mobile Chrome, please set VITE_WALLETCONNECT_PROJECT_ID in your environment. Get a free project ID at https://cloud.walletconnect.com`)
            } else {
                throw new Error(`${walletName} is not available. Please try again or use a different wallet.`)
            }
        }

        connectingMessage.value = t('walletModalRequestingAccess')
        statusMessage.value = t('walletModalApproveConnection')

        // WalletConnect requires connect() before request()
        // Check if provider has connect method (WalletConnect) or use request directly (MetaMask extension)
        let accounts = []
        const timeout = 120000 // 2 minutes for WalletConnect (user needs time to scan QR)

        if (typeof provider.connect === 'function') {
            // WalletConnect: must connect first (this shows QR modal)
            connectingMessage.value = 'Waiting for wallet connection...'
            statusMessage.value = 'Scan QR code with your wallet app or approve connection'

            try {
                // connect() returns session info, not accounts directly
                const session = await Promise.race([
                    provider.connect(),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('Connection timeout. Please try again.')), timeout))
                ])

                // Check if already connected
                if (provider.accounts && provider.accounts.length > 0) {
                    accounts = provider.accounts
                } else {
                    // Get accounts after connection
                    accounts = await Promise.race([
                        provider.request({method: 'eth_accounts'}),
                        new Promise((_, reject) => setTimeout(() => reject(new Error('Failed to get accounts')), 10000))
                    ])
                }
            } catch (err) {
                // If user rejects or closes modal, handle gracefully
                if (err.message.includes('User rejected') || err.message.includes('rejected')) {
                    throw new Error('Connection rejected. Please try again.')
                }
                throw err
            }
        } else {
            // MetaMask extension: use request directly
            accounts = await Promise.race([
                provider.request({method: 'eth_requestAccounts'}),
                new Promise((_, reject) => setTimeout(() => reject(new Error('Connection timeout. Please try again.')), timeout))
            ])
        }

        if (!accounts || accounts.length === 0) {
            throw new Error(t('walletModalNoAccounts'))
        }

        // Normalize wallet address to lowercase for consistency
        const walletAddress = accounts[0].toLowerCase()

        connectingMessage.value = t('walletModalGettingNonce')
        statusMessage.value = t('walletModalPleaseWait')

        await directLoginWithSignature(walletAddress, walletType, provider)

    } catch (err) {
        isProcessing.value = false
        connecting.value = false

        if (err.code === 4001) {
            error.value = t('walletModalConnectionRejected')
        } else if (err.message.includes('timeout')) {
            error.value = t('walletModalConnectionTimeout')
        } else {
            error.value = err.message || t('walletModalConnectionError')
        }
    }
}

const directLoginWithSignature = async (walletAddress, walletType, provider) => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        const referralCode = getReferralCode()
        connectingMessage.value = t('walletModalGettingNonce') || 'Getting authentication challenge...'
        statusMessage.value = t('walletModalPleaseWait') || 'Please wait...'

        const nonceResponse = await Promise.race([
            fetch('/auth/web3/nonce', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    wallet_address: walletAddress
                })
            }),
            new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 15000))
        ])

        if (!nonceResponse.ok) {
            const errorData = await nonceResponse.json().catch(() => ({}))
            throw new Error(errorData.message || t('walletModalNonceFailed'))
        }

        const nonceData = await nonceResponse.json()

        if (!nonceData.success || !nonceData.nonce) {
            throw new Error(nonceData.message || t('walletModalNonceFailed'))
        }

        const nonce = nonceData.nonce
        connectingMessage.value = t('walletModalSigningMessage') || 'Requesting signature...'
        statusMessage.value = t('walletModalConfirmInWallet') || 'Please confirm in your wallet'

        // personal_sign expects [message, address] - address should be the signer's address
        // Note: MetaMask will use the currently selected account, but we pass it for clarity
        const signature = await Promise.race([
            provider.request({
                method: 'personal_sign',
                params: [nonce, walletAddress]
            }),
            new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 60000))
        ])

        // Ensure signature is in correct format
        if (!signature || !signature.startsWith('0x') || signature.length !== 132) {
            throw new Error('Invalid signature format received from wallet')
        }

        if (!signature) {
            throw new Error(t('walletModalSignatureFailed') || 'Signature failed')
        }

        connectingMessage.value = t('walletModalLoggingIn') || 'Logging in...'
        statusMessage.value = t('walletModalAuthenticatingWallet') || 'Authenticating...'

        const loginResponse = await Promise.race([
            fetch('/auth/web3/direct-login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    wallet_address: walletAddress,
                    signature: signature,
                    wallet_type: walletType,
                    referral_code: referralCode
                })
            }),
            new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 15000))
        ])

        if (!loginResponse.ok) {
            const errorData = await loginResponse.json().catch(() => ({}))
            throw new Error(errorData.message || t('walletModalLoginFailed'))
        }

        const data = await loginResponse.json()

        if (!data.success) {
            throw new Error(data.message || t('walletModalLoginFailed'))
        }

        // Check if we're in MetaMask's browser
        const isInMetaMaskBrowser = window.ethereum && typeof window.ethereum.request === 'function'
        const urlParams = new URLSearchParams(window.location.search)
        const cameFromMetaMask = urlParams.get('metamask_connect') === 'true'

        // Detect local HTTP environment (mobile local testing)
        const origin = window.location.origin
        const isHttpLocal =
            origin.startsWith('http://') &&
            (origin.includes('localhost') ||
                origin.includes('127.0.0.1') ||
                /^http:\/\/192\.168\./.test(origin) ||
                /^http:\/\/10\./.test(origin) ||
                /^http:\/\/172\.(1[6-9]|2[0-9]|3[0-1])\./.test(origin))

        // For non-local (real) environments: if in MetaMask's browser and we have an auth_token, redirect back to Chrome
        if (!isHttpLocal && (isInMetaMaskBrowser || cameFromMetaMask) && data.auth_token) {
            // Get original Chrome URL from sessionStorage
            const pendingConnection = sessionStorage.getItem('wallet_connection_pending')
            let originalChromeUrl = null

            if (pendingConnection) {
                try {
                    const connection = JSON.parse(pendingConnection)
                    originalChromeUrl = connection.originalChromeUrl
                } catch (e) {
                    console.error('Failed to parse pending connection:', e)
                }
            }

            // Fallback to referrer or current origin
            if (!originalChromeUrl) {
                originalChromeUrl = sessionStorage.getItem('original_chrome_url') || document.referrer || window.location.origin
            }

            // Build redirect URL with token
            const chromeUrl = new URL(originalChromeUrl)
            chromeUrl.searchParams.set('auth_token', data.auth_token)

            connectingMessage.value = 'Redirecting back to Chrome...'
            statusMessage.value = 'Please wait...'

            // Clear sessionStorage
            sessionStorage.removeItem('wallet_connection_pending')
            sessionStorage.removeItem('original_chrome_url')

            // Redirect to Chrome with token
            setTimeout(() => {
                window.location.href = chromeUrl.toString()
            }, 1000)
            return
        }

        // Normal browser - redirect immediately
        connectingMessage.value = t('walletModalSuccess') || 'Success!'
        statusMessage.value = t('walletModalRedirecting') || 'Redirecting...'

        await new Promise(resolve => setTimeout(resolve, 800))

        emit('success', data.user)

        window.location.href = data.redirect_url || '/user/dashboard'

    } catch (err) {
        console.error('Secure login error:', err)
        if (err.code === 4001) {
            throw new Error(t('walletModalSignatureRejected') || 'Signature request rejected')
        }

        throw err
    }
}

const retry = () => {
    error.value = null
    connecting.value = false
    isProcessing.value = false
    selectedWallet.value = ''
    showEmailForm.value = false
    showForgotPassword.value = false
    resetSuccess.value = false
    showMetaMaskSuccess.value = false
    email.value = ''
    password.value = ''
    forgotEmail.value = ''
    authMode.value = 'login'
    registerEmail.value = ''
    registerPassword.value = ''
    registerConfirmPassword.value = ''
}

const closeModal = () => {
    if (!connecting.value) {
        showEmailForm.value = false
        showForgotPassword.value = false
        resetSuccess.value = false
        email.value = ''
        password.value = ''
        forgotEmail.value = ''
        error.value = null
        emit('close')
    }
}

const handleBackdropClick = () => {
    if (!connecting.value) {
        emit('close')
    }
}

let initializationDone = false

onMounted(() => {
    if (initializationDone) return
    initializationDone = true

    // Check if URL has auth_token parameter (returning from MetaMask)
    const urlParams = new URLSearchParams(window.location.search)
    const authToken = urlParams.get('auth_token')

    if (authToken) {
        // Exchange token for session
        connecting.value = true
        connectingMessage.value = 'Authenticating...'
        statusMessage.value = 'Please wait...'

        fetch(`/auth/web3/exchange-token?token=${encodeURIComponent(authToken)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (response.ok) {
                // Remove token from URL
                const newUrl = new URL(window.location.href)
                newUrl.searchParams.delete('auth_token')
                window.history.replaceState({}, '', newUrl.toString())

                // Redirect to dashboard
                window.location.href = '/user/dashboard'
            } else {
                throw new Error('Token exchange failed')
            }
        })
        .catch(err => {
            console.error('Token exchange error:', err)
            connecting.value = false
            error.value = 'Failed to authenticate. Please try connecting again.'
            // Remove token from URL
            const newUrl = new URL(window.location.href)
            newUrl.searchParams.delete('auth_token')
            window.history.replaceState({}, '', newUrl.toString())
        })
        return
    }

    // Check if URL has metamask_connect parameter (site loaded in MetaMask)
    const isMetaMaskConnect = urlParams.get('metamask_connect') === 'true'

    // Check if returning from mobile wallet app or if auto-connect is enabled
    if (typeof window !== 'undefined') {
        const pendingConnection = sessionStorage.getItem('wallet_connection_pending')

        // Auto-connect if URL has metamask_connect param OR if pending connection exists
        if (isMetaMaskConnect || pendingConnection) {
            try {
                let connection = null
                if (pendingConnection) {
                    connection = JSON.parse(pendingConnection)
                } else if (isMetaMaskConnect) {
                    // Create connection object from URL param
                    connection = {
                        walletType: 'metamask',
                        timestamp: Date.now(),
                        autoConnect: true
                    }
                    sessionStorage.setItem('wallet_connection_pending', JSON.stringify(connection))
                }

                if (connection) {
                    const timeDiff = Date.now() - connection.timestamp
                    // If returned within 5 minutes, try to connect
                    if (timeDiff < 300000) {
                        // Wait for wallet to inject (MetaMask Mobile injects ethereum provider)
                        let checkCount = 0
                        const maxChecks = 60 // Check for 30 seconds (more time for mobile)
                        const checkInterval = setInterval(() => {
                            checkCount++
                            if (window.ethereum) {
                                clearInterval(checkInterval)
                                detectAvailableWallets()

                                // Auto-connect if enabled or if wallet is detected
                                if (connection.autoConnect || connection.walletType === 'metamask') {
                                    const walletConfig = walletConfigs.find(w => w.type === connection.walletType)
                                    if (walletConfig) {
                                        // Small delay to ensure provider is fully ready
                                        setTimeout(() => {
                                            // Reset connecting state and try connection
                                            connecting.value = true
                                            connectingMessage.value = 'Connecting to MetaMask...'
                                            statusMessage.value = 'Please approve the connection'
                                            connectWallet(connection.walletType)
                                        }, 1500)
                                    }
                                }
                                sessionStorage.removeItem('wallet_connection_pending')
                            } else if (checkCount >= maxChecks) {
                                clearInterval(checkInterval)
                                // Timeout - wallet didn't inject, but keep pending for manual retry
                                console.log('MetaMask provider not detected after timeout')
                                error.value = 'MetaMask opened but connection failed. If you see this site in MetaMask, click "Connect Wallet" again.'
                            }
                        }, 500)
                    }
                } else {
                    sessionStorage.removeItem('wallet_connection_pending')
                }
            } catch (e) {
                console.error('Error in auto-connect:', e)
                sessionStorage.removeItem('wallet_connection_pending')
            }
        }
    }

    // Check if we're in MetaMask browser (for debugging)
    if (typeof window !== 'undefined') {
        const checkMetaMask = () => {
            if (window.ethereum) {
                console.log('MetaMask provider detected:', {
                    isMetaMask: window.ethereum.isMetaMask,
                    userAgent: navigator.userAgent,
                    url: window.location.href
                })
            }
        }
        // Check immediately and after delays
        checkMetaMask()
        setTimeout(checkMetaMask, 1000)
        setTimeout(checkMetaMask, 3000)
    }

    // Initial detection
    setTimeout(() => {
        detectAvailableWallets()
    }, 500)

    // Listen for MetaMask installation
    if (typeof window !== 'undefined') {
        // Check if ethereum provider becomes available
        const checkEthereum = () => {
            if (window.ethereum) {
                detectAvailableWallets()
            }
        }

        // Check periodically for MetaMask installation (longer on mobile)
        const checkInterval = isMobile() ? 2000 : 1000
        const maxChecks = isMobile() ? 30 : 10 // Check for 60 seconds on mobile, 10 seconds on desktop

        let checkCount = 0
        const interval = setInterval(() => {
            checkCount++
            checkEthereum()
            if (checkCount >= maxChecks) {
                clearInterval(interval)
            }
        }, checkInterval)

        // Also listen for ethereum provider injection
        window.addEventListener('ethereum#initialized', () => {
            detectAvailableWallets()
            clearInterval(interval)
        }, { once: true })
    }
})

onUnmounted(() => {
    initializationDone = false
    isProcessing.value = false
    connecting.value = false
})
</script>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes scale-in {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes pulse-slow {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

.animate-scale-in {
    animation: scale-in 0.3s ease-out;
}

.animate-pulse-slow {
    animation: pulse-slow 3s ease-in-out infinite;
}

.backdrop-blur-2xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.wallet-svg {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.wallet-svg svg {
    width: 100%;
    height: 100%;
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in,
    .animate-scale-in,
    .animate-pulse-slow {
        animation: none !important;
    }
}
</style>

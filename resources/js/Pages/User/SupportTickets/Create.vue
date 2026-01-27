<template>
    <UserLayout
        :page-title="t('createSupportTicket') || 'Create Support Ticket'"
        :page-section="t('newRequest') || 'New Request'"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('createTicketForm')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <button
                    @click="router.visit('/user/support-tickets')"
                    class="flex items-center gap-1.5 text-slate-400 font-medium hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 rounded-lg px-2 py-1"
                    :style="`--tw-ring-color: ${themeColors.primary}`"
                    :aria-label="t('backToTickets') || 'Back to Tickets'"
                >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ t('backToTickets') || 'Back to Tickets' }}
                </button>

                <section :aria-labelledby="t('helpInformation')"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <h2 class="sr-only">{{ t('helpInformation') }}</h2>
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                             :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2)`" aria-hidden="true">
                            <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" :style="`color: ${themeColors.primary}`" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base sm:text-lg font-bold text-white mb-1.5">{{
                                    t('needHelp') || 'Need Help?'
                                }}</h3>
                            <p class="text-sm font-medium text-slate-300 leading-relaxed">{{
                                    t('supportDescription') || 'Our support team is here to assist you. Please provide as much detail as possible about your issue so we can help you quickly and effectively.'
                                }}</p>
                        </div>
                    </div>
                </section>

                <section :aria-labelledby="t('createTicketForm')"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <h2 class="text-lg sm:text-xl font-bold text-white mb-5 sm:mb-6">
                        {{ t('createNewTicket') || 'Create New Ticket' }}</h2>
                    <form @submit.prevent="submitTicket" class="space-y-5" :aria-label="t('ticketForm')">
                        <div>
                            <label for="subject" class="text-sm font-medium text-slate-400 mb-1.5 block">
                                {{ t('subject') || 'Subject' }} <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="subject"
                                v-model="ticketForm.subject"
                                type="text"
                                :placeholder="t('subjectPlaceholder') || 'Brief summary of your issue'"
                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                :class="ticketForm.errors.subject ? 'border-red-500' : ''"
                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                :aria-invalid="!!ticketForm.errors.subject"
                                :aria-describedby="ticketForm.errors.subject ? 'subject-error' : null"
                                required
                            />
                            <p v-if="ticketForm.errors.subject" id="subject-error"
                               class="text-red-400 text-xs font-medium mt-1" role="alert">{{
                                    ticketForm.errors.subject
                                }}</p>
                        </div>

                        <div>
                            <label for="category" class="text-sm font-medium text-slate-400 mb-1.5 block">
                                {{ t('category') || 'Category' }}
                            </label>
                            <select
                                id="category"
                                v-model="ticketForm.category"
                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
                                :class="ticketForm.errors.category ? 'border-red-500' : ''"
                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                :aria-invalid="!!ticketForm.errors.category"
                                :aria-describedby="ticketForm.errors.category ? 'category-error' : null"
                            >
                                <option value="">{{ t('selectCategory') || 'Select a category' }}</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                            <p v-if="ticketForm.errors.category" id="category-error"
                               class="text-red-400 text-xs font-medium mt-1" role="alert">{{
                                    ticketForm.errors.category
                                }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">
                                {{ t('priority') || 'Priority' }} <span class="text-red-400">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3" role="radiogroup"
                                 :aria-label="t('selectPriority') || 'Select Priority'">
                                <label
                                    v-for="priority in priorities"
                                    :key="priority.value"
                                    class="relative flex items-start p-3.5 sm:p-4 bg-slate-800/40 border border-slate-700/30 rounded-lg cursor-pointer hover:bg-slate-800/60 hover:border-slate-600/50 transition-all duration-300 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-slate-900"
                                    :class="ticketForm.priority === priority.value ? 'border-2' : ''"
                                    :style="ticketForm.priority === priority.value ? `border-color: ${priority.color}; --tw-ring-color: ${priority.color}` : `--tw-ring-color: ${themeColors.primary}`"
                                >
                                    <input
                                        v-model="ticketForm.priority"
                                        type="radio"
                                        :value="priority.value"
                                        :aria-label="`${priority.label}: ${priority.description}`"
                                        class="sr-only"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                                 :style="`background-color: ${priority.color}`"
                                                 :aria-hidden="true"></div>
                                            <span class="font-semibold text-white text-sm">{{
                                                    priority.label
                                                }}</span>
                                        </div>
                                        <p class="text-xs font-medium text-slate-400">{{
                                                priority.description
                                            }}</p>
                                    </div>
                                    <div v-if="ticketForm.priority === priority.value" class="flex-shrink-0"
                                         aria-hidden="true">
                                        <svg class="w-4.5 h-4.5" :style="`color: ${priority.color}`" fill="currentColor"
                                             viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </label>
                            </div>
                            <p v-if="ticketForm.errors.priority" class="text-red-400 text-xs font-medium mt-1"
                               role="alert">{{ ticketForm.errors.priority }}</p>
                        </div>

                        <div>
                            <label for="description" class="text-sm font-medium text-slate-400 mb-1.5 block">
                                {{ t('description') || 'Description' }} <span class="text-red-400">*</span>
                            </label>
                            <textarea
                                id="description"
                                v-model="ticketForm.description"
                                rows="7"
                                maxlength="5000"
                                :placeholder="t('descriptionPlaceholder') || 'Please describe your issue in detail. Include any relevant information, error messages, or steps to reproduce the problem.'"
                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all resize-none"
                                :class="ticketForm.errors.description ? 'border-red-500' : ''"
                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                :aria-invalid="!!ticketForm.errors.description"
                                :aria-describedby="ticketForm.errors.description ? 'description-error description-counter' : 'description-counter'"
                                required
                            ></textarea>
                            <div class="flex justify-between items-center mt-1.5">
                                <p v-if="ticketForm.errors.description" id="description-error"
                                   class="text-red-400 text-xs font-medium" role="alert">
                                    {{ ticketForm.errors.description }}</p>
                                <p id="description-counter" class="text-xs font-medium text-slate-500 ml-auto"
                                   :aria-live="ticketForm.description.length > 4500 ? 'polite' : 'off'">
                                    {{ ticketForm.description.length }} / 5000 {{ t('characters') || 'characters' }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">
                                {{ t('attachments') || 'Attachments' }} ({{ t('optional') || 'Optional' }})
                            </label>
                            <p class="text-xs font-medium text-slate-500 mb-2.5">{{
                                    t('attachmentHelp') || 'Upload screenshots, documents, or other files that might help us understand your issue better. Max 10MB per file.'
                                }}</p>

                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip"
                                @change="handleFileSelect"
                                class="sr-only"
                                :aria-label="t('uploadFiles') || 'Upload Files'"
                            />

                            <button
                                type="button"
                                @click="fileInput.click()"
                                class="w-full px-4 py-6 bg-slate-800/60 border-2 border-dashed border-slate-700/40 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 hover:border-slate-600/50 transition-all duration-300 flex flex-col items-center justify-center gap-2.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`--tw-ring-color: ${themeColors.primary}`"
                                :aria-label="t('clickToUpload') || 'Click to upload files'"
                            >
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                     aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <div class="text-center">
                                    <p class="font-semibold mb-0.5 text-sm">
                                        {{ t('clickToUpload') || 'Click to upload files' }}</p>
                                    <p class="text-xs font-medium">
                                        {{ t('fileFormats') || 'JPG, PNG, PDF, DOC, DOCX, TXT, ZIP up to 10MB' }}</p>
                                </div>
                            </button>

                            <div v-if="selectedFiles.length" class="mt-3 space-y-1.5">
                                <p class="text-sm font-medium text-slate-400">{{
                                        t('selectedFiles') || 'Selected Files'
                                    }} ({{ selectedFiles.length }})</p>
                                <div
                                    v-for="(file, index) in selectedFiles"
                                    :key="index"
                                    class="flex items-center justify-between p-3 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 transition-colors"
                                    role="listitem"
                                >
                                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                                             :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2)`"
                                             aria-hidden="true">
                                            <svg class="w-4.5 h-4.5" :style="`color: ${themeColors.primary}`" fill="none"
                                                 stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-white truncate">{{ file.name }}</p>
                                            <p class="text-xs font-medium text-slate-400">{{
                                                    formatFileSize(file.size)
                                                }}</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeFile(index)"
                                        class="text-slate-400 hover:text-red-400 transition-colors ml-2.5 p-1.5 rounded-lg hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-red-400"
                                        :aria-label="`${t('removeFile') || 'Remove file'}: ${file.name}`"
                                    >
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                             aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <p v-if="ticketForm.errors.attachments" class="text-red-400 text-xs font-medium mt-1"
                               role="alert">{{ ticketForm.errors.attachments }}</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2.5 pt-3" role="group"
                             :aria-label="t('formActions') || 'Form Actions'">
                            <button
                                type="button"
                                @click="router.visit('/user/support-tickets')"
                                class="flex-1 px-5 py-2.5 bg-slate-800/60 text-white rounded-lg font-semibold text-sm hover:bg-slate-800 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-slate-500"
                                :aria-label="t('cancel') || 'Cancel'"
                            >
                                {{ t('cancel') || 'Cancel' }}
                            </button>
                            <button
                                type="submit"
                                :disabled="ticketForm.processing || !ticketForm.subject || !ticketForm.description"
                                class="flex-1 px-5 py-2.5 rounded-lg font-semibold text-sm text-slate-900 transition-all hover:scale-105 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); --tw-ring-color: ${themeColors.primary}`"
                                :aria-label="ticketForm.processing ? (t('creatingTicket') || 'Creating Ticket...') : (t('submitTicket') || 'Submit Ticket')"
                            >
                                <span v-if="ticketForm.processing" class="flex items-center justify-center gap-1.5">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"
                                         aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ t('creatingTicket') || 'Creating Ticket...' }}
                                </span>
                                <span v-else class="flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                         aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    {{ t('submitTicket') || 'Submit Ticket' }}
                                </span>
                            </button>
                        </div>
                    </form>
                </section>

                <section :aria-labelledby="t('commonQuestions')"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6 hover:border-slate-600/50 transition-all duration-300">
                    <h3 class="text-base sm:text-lg font-bold text-white mb-4 sm:mb-5">
                        {{ t('commonQuestions') || 'Common Questions' }}</h3>
                    <div class="space-y-3" role="list" :aria-label="t('faqList') || 'Frequently Asked Questions'">
                        <div class="p-3.5 sm:p-4 bg-slate-800/40 rounded-lg hover:bg-slate-800/60 transition-colors"
                             role="listitem">
                            <h4 class="font-semibold text-white mb-1.5 flex items-center gap-1.5 text-sm">
                                <svg class="w-4.5 h-4.5 flex-shrink-0" :style="`color: ${themeColors.primary}`" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                {{ t('securityQuestion') || 'Is my information secure?' }}
                            </h4>
                            <p class="text-xs font-medium text-slate-400 leading-relaxed">{{
                                    t('securityAnswer') || 'Yes, all support tickets and attachments are encrypted and securely stored. Only authorized support staff can access your ticket.'
                                }}</p>
                        </div>
                        <div class="p-3.5 sm:p-4 bg-slate-800/40 rounded-lg hover:bg-slate-800/60 transition-colors"
                             role="listitem">
                            <h4 class="font-semibold text-white mb-1.5 flex items-center gap-1.5 text-sm">
                                <svg class="w-4.5 h-4.5 flex-shrink-0" :style="`color: ${themeColors.primary}`" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                {{ t('notificationQuestion') || 'Will I be notified of updates?' }}
                            </h4>
                            <p class="text-xs font-medium text-slate-400 leading-relaxed">{{
                                    t('notificationAnswer') || 'Yes, you\'ll receive email notifications whenever there\'s a new reply to your ticket. You can also check your ticket status anytime.'
                                }}</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';
import {usePage, useForm, router} from '@inertiajs/vue3';
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue";
import {useToast} from "@/composables/useToast.js";
import {useTranslation} from '@/composables/useTranslation';

const {showToast} = useToast();
const {t} = useTranslation();
const page = usePage();

const ticketForm = useForm({
    subject: '',
    description: '',
    category: '',
    priority: 'medium',
    attachments: []
});

const fileInput = ref(null);
const selectedFiles = ref([]);

const primaryColor = computed(() => page.props.primaryColor || '#86efac');

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

const categories = [
    {value: 'account', label: t('categoryAccount') || 'Account Issues'},
    {value: 'investment', label: t('categoryInvestment') || 'Investment Questions'},
    {value: 'withdrawal', label: t('categoryWithdrawal') || 'Withdrawal Problems'},
    {value: 'deposit', label: t('categoryDeposit') || 'Deposit Issues'},
    {value: 'staking', label: t('categoryStaking') || 'Staking Questions'},
    {value: 'referral', label: t('categoryReferral') || 'Referral Program'},
    {value: 'technical', label: t('categoryTechnical') || 'Technical Support'},
    {value: 'billing', label: t('categoryBilling') || 'Billing Inquiries'},
    {value: 'kyc', label: t('categoryKyc') || 'KYC Verification'},
    {value: 'security', label: t('categorySecurity') || 'Security Concerns'},
    {value: 'general', label: t('categoryGeneral') || 'General Questions'},
    {value: 'feature', label: t('categoryFeature') || 'Feature Requests'}
];

const priorities = [
    {
        value: 'low',
        label: t('priorityLow') || 'Low',
        color: '#6b7280',
        description: t('priorityLowDescription') || 'General questions or minor issues'
    },
    {
        value: 'medium',
        label: t('priorityMedium') || 'Medium',
        color: '#3b82f6',
        description: t('priorityMediumDescription') || 'Standard support request'
    },
    {
        value: 'high',
        label: t('priorityHigh') || 'High',
        color: '#f59e0b',
        description: t('priorityHighDescription') || 'Urgent but not critical'
    },
    {
        value: 'urgent',
        label: t('priorityUrgent') || 'Urgent',
        color: '#ef4444',
        description: t('priorityUrgentDescription') || 'Critical issue requiring immediate attention'
    }
];

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    const maxSize = 10 * 1024 * 1024;
    const validFiles = [];
    let hasInvalidFiles = false;

    files.forEach(file => {
        if (file.size > maxSize) {
            showToast(`${file.name} ${t('fileTooLarge') || 'is too large. Maximum size is 10MB'}`, 'error');
            hasInvalidFiles = true;
        } else {
            validFiles.push(file);
        }
    });

    if (validFiles.length > 0) {
        selectedFiles.value = [...selectedFiles.value, ...validFiles];
        ticketForm.attachments = selectedFiles.value;
        showToast(t('filesAttached') || `${validFiles.length} file(s) attached successfully`, 'success');
    }

    // Reset file input
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const removeFile = (index) => {
    const fileName = selectedFiles.value[index].name;
    selectedFiles.value.splice(index, 1);
    ticketForm.attachments = selectedFiles.value;
    showToast(t('fileRemoved') || `${fileName} removed`, 'info');
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const submitTicket = () => {
    // Validate required fields
    if (!ticketForm.subject || !ticketForm.subject.trim()) {
        showToast(t('subjectRequired') || 'Subject is required', 'error');
        return;
    }

    if (!ticketForm.description || !ticketForm.description.trim()) {
        showToast(t('descriptionRequired') || 'Description is required', 'error');
        return;
    }

    if (!ticketForm.category) {
        showToast(t('categoryRequired') || 'Please select a category', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('subject', ticketForm.subject);
    formData.append('description', ticketForm.description);
    formData.append('category', ticketForm.category);
    formData.append('priority', ticketForm.priority);

    selectedFiles.value.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file);
    });

    router.post('/user/support-tickets', formData, {
        preserveScroll: true,
        onSuccess: () => {
            ticketForm.reset();
            selectedFiles.value = [];
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            setTimeout(() => {
                router.visit('/user/support-tickets');
            }, 1500);
        },
        onError: (errors) => {
            if (errors.subject) {
                showToast(errors.subject, 'error');
            }
            if (errors.description) {
                showToast(errors.description, 'error');
            }
            if (errors.category) {
                showToast(errors.category, 'error');
            }
        }
    });
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

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

input[type="radio"]:checked + div {
    font-weight: 700;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in-up,
    .transition-all,
    .animate-spin {
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

.hover\:scale-105:hover {
    will-change: transform;
}

@media (max-width: 768px) {
    * {
        -webkit-overflow-scrolling: touch;
    }
}

@media print {
    .hover\:scale-105 {
        transform: none !important;
    }

    button,
    a[href^="/"] {
        display: none;
    }
}
</style>

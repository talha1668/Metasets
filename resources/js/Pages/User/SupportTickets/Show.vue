<template>
    <UserLayout :page-title="t('ticketDetails')" :page-section="t('support')">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 lg:py-8" :aria-label="t('ticketDetails')">
            <div class="max-w-[1400px] mx-auto space-y-5 sm:space-y-6">
                <button
                    @click="router.visit('/user/support-tickets')"
                    class="flex items-center gap-1.5 text-slate-400 font-medium hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 rounded-lg px-2 py-1"
                    :style="`--tw-ring-color: ${themeColors.primary}`"
                >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ t('backToTickets') }}
                </button>

                <section
                    class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 mb-5">
                        <div class="flex-1">
                            <div class="flex items-center gap-2.5 mb-3">
                                <span class="px-3 py-1.5 rounded-lg text-xs font-semibold"
                                      :style="`background-color: ${statusConfig[ticket.status]?.bgColor}; color: ${statusConfig[ticket.status]?.color}`">
                                    {{ t(statusConfig[ticket.status]?.labelKey) || ticket.status }}
                                </span>
                                <span class="px-3 py-1.5 rounded-lg text-xs font-semibold"
                                      :style="`background-color: rgba(${hexToRgb(priorityConfig[ticket.priority]?.color || '#6b7280').r}, ${hexToRgb(priorityConfig[ticket.priority]?.color || '#6b7280').g}, ${hexToRgb(priorityConfig[ticket.priority]?.color || '#6b7280').b}, 0.1); color: ${priorityConfig[ticket.priority]?.color || '#6b7280'}`">
                                    {{ t(priorityConfig[ticket.priority]?.labelKey) || ticket.priority }}
                                </span>
                            </div>
                            <h1 class="text-xl sm:text-2xl font-bold text-white mb-2.5">{{ ticket.subject }}</h1>
                            <div class="flex flex-wrap items-center gap-3 text-sm font-medium text-slate-400">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                    </svg>
                                    {{ ticket.ticket_number }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ formatDate(ticket.created_at) }}
                                </span>
                                <span v-if="ticket.category" class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    {{ ticket.category }}
                                </span>
                            </div>
                        </div>
                        <div v-if="ticket.assigned_to"
                             class="flex items-center gap-2.5 bg-slate-800/40 px-3.5 py-2.5 rounded-lg">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                 :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})`">
                                {{ ticket.assigned_to.name.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-400">{{ t('assignedTo') }}</p>
                                <p class="text-sm font-semibold text-white">{{ ticket.assigned_to.name }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6">
                    <h3 class="text-base font-bold text-white mb-3">{{ t('description') }}</h3>
                    <p class="text-slate-300 text-sm font-medium leading-relaxed whitespace-pre-wrap">{{
                            ticket.description
                        }}</p>

                    <div v-if="ticket.attachments && ticket.attachments.length" class="mt-5">
                        <h4 class="text-sm font-medium text-slate-400 mb-2.5">{{ t('attachments') }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                            <div
                                v-for="attachment in ticket.attachments"
                                :key="attachment.id"
                                @click="downloadAttachment(attachment.id)"
                                class="flex items-center gap-2.5 p-3 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 hover:border-slate-600/50 transition-all cursor-pointer"
                            >
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                                     :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2)`">
                                    <svg class="w-4.5 h-4.5" :style="`color: ${themeColors.primary}`" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">{{
                                            attachment.original_name
                                        }}</p>
                                    <p class="text-xs font-medium text-slate-400">
                                        {{ formatFileSize(attachment.file_size) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="text-lg font-bold text-white">{{ t('conversation') }}</h3>

                    <div v-if="ticket.replies && ticket.replies.length" class="space-y-3">
                        <div
                            v-for="reply in ticket.replies"
                            :key="reply.id"
                            class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5"
                            :class="reply.is_admin_reply ? 'border-l-4' : ''"
                            :style="reply.is_admin_reply ? `border-left-color: ${themeColors.primary}` : ''"
                        >
                            <div class="flex items-start gap-3 mb-3">
                                <div
                                    class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                                    :style="reply.is_admin_reply ? `background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark})` : 'background-color: #64748b'">
                                    {{ reply.user.name.charAt(0) }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2.5 mb-1.5">
                                        <p class="font-semibold text-white text-sm">{{ reply.user.name }}</p>
                                        <span v-if="reply.is_admin_reply"
                                              class="px-2 py-0.5 rounded text-xs font-semibold"
                                              :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2); color: ${themeColors.primary}`">
                                            {{ t('supportTeam') }}
                                        </span>
                                        <span class="text-xs font-medium text-slate-500">{{
                                                formatDate(reply.created_at)
                                            }}</span>
                                    </div>
                                    <p class="text-slate-300 text-sm font-medium leading-relaxed whitespace-pre-wrap">
                                        {{ reply.message }}</p>

                                    <div v-if="reply.attachments && reply.attachments.length" class="mt-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            <div
                                                v-for="attachment in reply.attachments"
                                                :key="attachment.id"
                                                @click="downloadAttachment(attachment.id)"
                                                class="flex items-center gap-2.5 p-2.5 bg-slate-800/40 rounded-lg border border-slate-700/30 hover:bg-slate-800/60 hover:border-slate-600/50 transition-all cursor-pointer"
                                            >
                                                <div
                                                    class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                                    :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2)`">
                                                    <svg class="w-3.5 h-3.5" :style="`color: ${themeColors.primary}`"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-white truncate">
                                                        {{ attachment.original_name }}</p>
                                                    <p class="text-xs font-medium text-slate-400">
                                                        {{ formatFileSize(attachment.file_size) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-6 text-center">
                        <svg class="w-10 h-10 mx-auto mb-2.5 text-slate-600" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-slate-400 text-sm font-medium">{{ t('noRepliesYet') }}</p>
                    </div>
                </section>

                <section v-if="ticket.status !== 'closed'"
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 sm:p-6">
                    <h3 class="text-base font-bold text-white mb-3">{{ t('addReply') }}</h3>
                    <form @submit.prevent="submitReply" class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('yourMessage') }}</label>
                            <textarea
                                v-model="replyForm.message"
                                rows="4"
                                :placeholder="t('typeYourReplyHere')"
                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-white font-medium placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all resize-none"
                                :class="replyForm.errors.message ? 'border-red-500' : ''"
                                :style="`--tw-ring-color: ${themeColors.primary}`"
                            ></textarea>
                            <p v-if="replyForm.errors.message" class="text-red-400 text-xs font-medium mt-1">
                                {{ replyForm.errors.message }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-400 mb-1.5 block">{{ t('attachmentsOptional') }}</label>
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip"
                                @change="handleFileSelect"
                                class="hidden"
                            />
                            <button
                                type="button"
                                @click="fileInput.click()"
                                class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/40 rounded-lg text-slate-400 font-medium text-sm hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                                :style="`--tw-ring-color: ${themeColors.primary}`"
                            >
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                {{ t('addFiles') }}
                            </button>

                            <div v-if="selectedFiles.length" class="mt-2.5 space-y-1.5">
                                <div
                                    v-for="(file, index) in selectedFiles"
                                    :key="index"
                                    class="flex items-center justify-between p-2.5 bg-slate-800/40 rounded-lg"
                                >
                                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                        <svg class="w-4.5 h-4.5 text-slate-400 flex-shrink-0" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
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
                                        class="text-slate-400 hover:text-red-400 transition-colors ml-2 focus:outline-none"
                                    >
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="replyForm.processing || !replyForm.message"
                            class="w-full px-5 py-2.5 rounded-lg font-semibold text-sm text-slate-900 transition-all hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900"
                            :style="`background: linear-gradient(135deg, ${themeColors.primary}, ${themeColors.primaryDark}); --tw-ring-color: ${themeColors.primary}`"
                        >
                            <span v-if="replyForm.processing">{{ t('sending') }}...</span>
                            <span v-else>{{ t('sendReply') }}</span>
                        </button>
                    </form>
                </section>

                <section v-else
                         class="bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg sm:rounded-xl p-5 text-center">
                    <svg class="w-10 h-10 mx-auto mb-2.5 text-slate-600" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <p class="text-slate-400 text-sm font-medium">{{ t('ticketClosedMessage') }}</p>
                </section>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/composables/useTranslation'
import { useToast } from "@/composables/useToast.js"
import UserLayout from "@/Layouts/UserLayout/UserLayout.vue"

const props = defineProps({
    ticket: {
        type: Object,
        required: true
    }
})

const page = usePage()
const { t } = useTranslation()
const { showToast } = useToast()

const fileInput = ref(null)
const selectedFiles = ref([])

const replyForm = ref({
    message: '',
    attachments: [],
    processing: false,
    errors: {}
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

const statusConfig = {
    open: {
        labelKey: 'statusOpen',
        bgColor: '#3b82f620',
        color: '#3b82f6'
    },
    in_progress: {
        labelKey: 'statusInProgress',
        bgColor: '#f59e0b20',
        color: '#f59e0b'
    },
    resolved: {
        labelKey: 'statusResolved',
        bgColor: '#10b98120',
        color: '#10b981'
    },
    closed: {
        labelKey: 'statusClosed',
        bgColor: '#64748b20',
        color: '#64748b'
    }
}

const priorityConfig = {
    low: {
        labelKey: 'priorityLow',
        color: '#64748b'
    },
    medium: {
        labelKey: 'priorityMedium',
        color: '#f59e0b'
    },
    high: {
        labelKey: 'priorityHigh',
        color: '#ef4444'
    },
    urgent: {
        labelKey: 'priorityUrgent',
        color: '#dc2626'
    }
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files)
    const maxSize = 5 * 1024 * 1024
    const invalidFiles = files.filter(file => file.size > maxSize)

    if (invalidFiles.length > 0) {
        showToast(t('fileSizeTooLarge') || 'Some files exceed the maximum size of 5MB', 'error')
        return
    }

    selectedFiles.value = [...selectedFiles.value, ...files]
    replyForm.value.attachments = selectedFiles.value
    showToast(t('filesSelected') || `${files.length} file(s) selected`, 'success')
}

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1)
    replyForm.value.attachments = selectedFiles.value
    showToast(t('fileRemoved') || 'File removed', 'info')
}

const submitReply = () => {
    if (!replyForm.value.message.trim()) {
        showToast(t('messageRequired') || 'Please enter a message', 'error')
        return
    }

    replyForm.value.processing = true
    replyForm.value.errors = {}

    const formData = new FormData()
    formData.append('message', replyForm.value.message)

    selectedFiles.value.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file)
    })

    router.post(`/user/support-tickets/${props.ticket.id}/reply`, formData, {
        preserveScroll: true,
        onSuccess: () => {
            showToast(t('replySubmitted') || 'Reply submitted successfully!', 'success')
            replyForm.value.message = ''
            selectedFiles.value = []
            replyForm.value.attachments = []
            if (fileInput.value) {
                fileInput.value.value = ''
            }
        },
        onError: (errors) => {
            replyForm.value.errors = errors
            showToast(t('replyFailed') || 'Failed to submit reply. Please try again.', 'error')
        },
        onFinish: () => {
            replyForm.value.processing = false
        }
    })
}

const downloadAttachment = (attachmentId) => {
    try {
        window.open(`/user/support-tickets/attachments/${attachmentId}/download`, '_blank')
        showToast(t('downloadStarted') || 'Download started', 'success')
    } catch (error) {
        showToast(t('downloadFailed') || 'Failed to download attachment', 'error')
    }
}

onMounted(() => {
    const flash = page.props.flash
    if (flash?.success) showToast(flash.success, 'success')
    if (flash?.error) showToast(flash.error, 'error')
    if (flash?.warning) showToast(flash.warning, 'warning')
    if (flash?.info) showToast(flash.info, 'info')
})
</script>

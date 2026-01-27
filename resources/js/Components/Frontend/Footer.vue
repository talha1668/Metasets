<template>
    <footer class="relative border-t backdrop-blur-xl py-6 mt-auto bg-gray-900/95 border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-sm text-gray-400">
                    © {{ currentYear }} {{ appName }}. {{ t('footerAllRightsReserved') }}
                </p>

                <div class="flex items-center gap-4 sm:gap-6">
                    <Link
                        :href="`/privacy-policy`"
                        class="text-sm text-gray-300 hover:text-white transition-colors font-medium"
                    >
                        {{ t('footerPrivacyPolicy') }}
                    </Link>
                    <Link
                        :href="`/terms`"
                        class="text-sm text-gray-300 hover:text-white transition-colors font-medium"
                    >
                        {{ t('footerTermsService') }}
                    </Link>
                    <Link
                        :href="`/cookies`"
                        class="text-sm text-gray-300 hover:text-white transition-colors font-medium"
                    >
                        {{ t('footerCookiePolicy') }}
                    </Link>
                </div>

                <div class="relative" ref="languageDropdown">
                    <button
                        ref="languageButton"
                        @click="toggleLanguageDropdown"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-300 border text-sm bg-gray-800/80 text-gray-300 border-gray-700 hover:bg-gray-700/80"
                    >
                        <span class="text-base">{{ currentLanguage.flag }}</span>
                        <span class="font-medium">{{ currentLanguage.code.toUpperCase() }}</span>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="{ 'rotate-180': showLanguageDropdown }"></i>
                    </button>

                    <Teleport to="body">
                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <div
                                v-show="showLanguageDropdown"
                                ref="dropdownMenu"
                                :style="dropdownStyle"
                                class="fixed w-48 backdrop-blur-xl border rounded-xl shadow-2xl z-[9999] bg-gray-900/95 border-gray-700"
                            >
                                <div class="py-2">
                                    <button
                                        v-for="lang in languages"
                                        :key="lang.code"
                                        @click="handleLanguageChange(lang.code)"
                                        class="w-full px-4 py-2 text-left flex items-center gap-3 transition-colors duration-200 hover:bg-gray-800/50"
                                        :class="currentLanguage.code === lang.code ? 'text-white bg-gray-800/70' : 'text-gray-300'"
                                    >
                                        <span class="text-lg">{{ lang.flag }}</span>
                                        <div>
                                            <div class="text-sm font-medium">{{ lang.name }}</div>
                                            <div class="text-xs text-gray-500">{{ lang.nativeName }}</div>
                                        </div>
                                        <i v-if="currentLanguage.code === lang.code" class="fas fa-check text-xs ml-auto"></i>
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </Teleport>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref, watch, nextTick} from 'vue'
import {Link, usePage} from '@inertiajs/vue3'
import {useTranslation} from '@/composables/useTranslation'

const page = usePage()
const {t, currentLanguage, languages, changeLanguage} = useTranslation()

const appName = computed(() => page.props.appName || 'TokenHive')
const currentYear = new Date().getFullYear()
const showLanguageDropdown = ref(false)
const languageDropdown = ref(null)
const languageButton = ref(null)
const dropdownMenu = ref(null)
const dropdownStyle = ref({})

const updateDropdownPosition = () => {
    if (!languageButton.value || !showLanguageDropdown.value) return

    const buttonRect = languageButton.value.getBoundingClientRect()
    const dropdownWidth = 192 // w-48 = 192px
    const spacing = 8 // mb-2 = 8px

    // Calculate position - dropdown appears above the button
    const top = buttonRect.top - spacing
    const left = buttonRect.right - dropdownWidth

    dropdownStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        transform: 'translateY(-100%)' // Move it up by its own height
    }
}

const toggleLanguageDropdown = () => {
    showLanguageDropdown.value = !showLanguageDropdown.value
    if (showLanguageDropdown.value) {
        nextTick(() => {
            updateDropdownPosition()
        })
    }
}

const handleLanguageChange = async (langCode) => {
    try {
        await changeLanguage(langCode)
        showLanguageDropdown.value = false
    } catch (error) {
        console.error('Failed to change language:', error)
    }
}

const handleClickOutside = (event) => {
    if (
        languageDropdown.value &&
        !languageDropdown.value.contains(event.target) &&
        dropdownMenu.value &&
        !dropdownMenu.value.contains(event.target)
    ) {
        showLanguageDropdown.value = false
    }
}

// Update position on scroll or resize
const handleScrollResize = () => {
    if (showLanguageDropdown.value) {
        updateDropdownPosition()
    }
}

watch(showLanguageDropdown, (newValue) => {
    if (newValue) {
        nextTick(() => {
            updateDropdownPosition()
        })
    }
})

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', handleScrollResize, true)
    window.addEventListener('resize', handleScrollResize)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', handleScrollResize, true)
    window.removeEventListener('resize', handleScrollResize)
})
</script>

<style scoped>
.rotate-180 {
    transform: rotate(180deg);
}
</style>

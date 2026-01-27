<template>
    <div class="text-center mb-6 animate-fade-in-up">
        <h2 class="text-xl sm:text-2xl font-bold text-white mb-2 sm:mb-3">{{ title }}</h2>
        <p class="text-sm sm:text-base font-medium text-slate-400 max-w-xl mx-auto">{{ subtitle }}</p>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        required: true
    }
})
</script>

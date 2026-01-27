<template>
    <LandingLayout>
        <HeroSection />
        <Footer />
        <CookieConsent />
    </LandingLayout>
    <ToastContainer />
</template>

<script setup>
import LandingLayout from '@/Components/Frontend/layout/LandingLayout.vue'
import HeroSection from '@/Components/Frontend/HeroSection.vue'
import Footer from '@/Components/Frontend/Footer.vue'
import CookieConsent from '@/Components/Frontend/CookieConsent.vue'
import ToastContainer from "@/Components/Toast/ToastContainer.vue";
</script>

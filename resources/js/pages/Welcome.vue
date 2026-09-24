<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CompareSection from '@/components/landing/CompareSection.vue';
import FaqSection from '@/components/landing/FaqSection.vue';
import FeatureGrid from '@/components/landing/FeatureGrid.vue';
import FinalCta from '@/components/landing/FinalCta.vue';
import HowItWorks from '@/components/landing/HowItWorks.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import LandingHeader from '@/components/landing/LandingHeader.vue';
import LandingHero from '@/components/landing/LandingHero.vue';
import PainPoints from '@/components/landing/PainPoints.vue';
import PricingSection from '@/components/landing/PricingSection.vue';
import TrustBar from '@/components/landing/TrustBar.vue';
</script>

<template>
    <Head title="Fluxa: Uang patungan, nol tebak-tebakan" />

    <div class="bg-background min-h-svh">
        <LandingHeader />

        <main
            id="atas"
            class="mx-auto w-full max-w-6xl space-y-16 px-4 pt-12 pb-16 md:space-y-20 md:px-8 md:pt-20"
        >
            <LandingHero />
            <TrustBar />
            <PainPoints />
            <HowItWorks />
            <FeatureGrid />
            <PricingSection />
            <CompareSection />
            <FaqSection />
            <FinalCta />
        </main>

        <LandingFooter />
    </div>
</template>

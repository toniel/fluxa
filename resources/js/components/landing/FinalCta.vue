<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';
</script>

<template>
    <section>
        <div
            class="bg-brand text-brand-foreground rounded-3xl p-10 text-center md:p-16"
        >
            <h2 class="text-3xl font-extrabold tracking-tight md:text-5xl">
                Uang bersama, dikelola bareng.
            </h2>
            <p class="mx-auto mt-4 max-w-md opacity-80">
                Bikin ruang keuangan pertamamu hari ini. Gratis, tanpa kartu
                kredit.
            </p>
            <Button
                as-child
                variant="secondary"
                class="mt-8 min-h-11 px-8 py-4 text-base"
            >
                <Link :href="register()">Mulai Gratis</Link>
            </Button>
        </div>
    </section>
</template>

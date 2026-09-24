<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';
</script>

<template>
    <footer class="border-t">
        <div
            class="text-muted-foreground mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-4 px-4 py-10 text-sm md:flex-row md:px-8"
        >
            <span class="text-foreground flex items-center gap-2 font-bold">
                <AppLogoIcon class="size-7" aria-hidden="true" />
                Fluxa
            </span>
            <p>© 2026 Fluxa.</p>
            <nav
                class="flex flex-wrap justify-center gap-1 font-semibold"
                aria-label="Bawah"
            >
                <Button as-child variant="ghost" class="min-h-11">
                    <Link href="#fitur">Fitur</Link>
                </Button>
                <Button as-child variant="ghost" class="min-h-11">
                    <Link href="#harga">Harga</Link>
                </Button>
                <Button as-child variant="ghost" class="min-h-11">
                    <Link :href="login()">Masuk</Link>
                </Button>
            </nav>
        </div>
    </footer>
</template>

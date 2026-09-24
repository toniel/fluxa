<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
</script>

<template>
    <header class="bg-background/90 sticky top-0 z-40 border-b">
        <div
            class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 md:px-8"
        >
            <Link
                href="#atas"
                class="flex items-center gap-2 text-lg font-bold tracking-tight"
                aria-label="Fluxa, ke atas"
            >
                <AppLogoIcon class="size-7" aria-hidden="true" />
                Fluxa
            </Link>
            <nav
                class="text-muted-foreground hidden items-center gap-7 text-sm font-semibold md:flex"
                aria-label="Bagian halaman"
            >
                <Link href="#fitur" class="hover:text-foreground transition"
                    >Fitur</Link
                >
                <Link
                    href="#cara-kerja"
                    class="hover:text-foreground transition"
                    >Cara kerja</Link
                >
                <Link href="#harga" class="hover:text-foreground transition"
                    >Harga</Link
                >
                <Link href="#faq" class="hover:text-foreground transition"
                    >FAQ</Link
                >
            </nav>
            <template v-if="$page.props.auth.user">
                <Button as-child class="min-h-11">
                    <Link :href="dashboard()">Buka beranda</Link>
                </Button>
            </template>
            <template v-else>
                <div class="flex items-center gap-2">
                    <Button as-child variant="ghost" class="min-h-11">
                        <Link :href="login()">Masuk</Link>
                    </Button>
                    <Button as-child class="hidden min-h-11 sm:inline-flex">
                        <Link :href="register()">Daftar</Link>
                    </Button>
                </div>
            </template>
        </div>
    </header>
</template>

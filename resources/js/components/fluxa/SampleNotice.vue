<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FlaskConical } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();

const base = computed(() => page.url.split('?')[0]);
</script>

<template>
    <aside
        class="border-money-out/40 bg-money-out/5 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border px-3 py-2 text-sm"
    >
        <span class="flex items-center gap-2 font-medium">
            <FlaskConical class="size-4 shrink-0" aria-hidden="true" />
            Data contoh
        </span>
        <span class="text-muted-foreground">
            Seluruh angka di halaman ini karangan, belum tersambung ke database.
        </span>
        <span class="flex items-center gap-2">
            <Link
                :href="base"
                class="focus-visible:ring-ring rounded underline underline-offset-4 focus-visible:ring-2 focus-visible:outline-none"
                >Ada isi</Link
            >
            <Link
                :href="`${base}?kosong`"
                class="focus-visible:ring-ring rounded underline underline-offset-4 focus-visible:ring-2 focus-visible:outline-none"
                >Kosong</Link
            >
            <Link
                :href="`${base}?galat`"
                class="focus-visible:ring-ring rounded underline underline-offset-4 focus-visible:ring-2 focus-visible:outline-none"
                >Gagal muat</Link
            >
        </span>
    </aside>
</template>

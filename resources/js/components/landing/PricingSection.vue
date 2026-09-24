<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { register } from '@/routes';
</script>

<template>
    <section id="harga" class="scroll-mt-20 space-y-3">
        <h2
            class="text-center text-3xl font-extrabold tracking-tight md:text-4xl"
        >
            Mulai gratis, naik saat butuh.
        </h2>
        <p class="text-muted-foreground text-center">
            Paket Free tidak dibatasi waktu.
        </p>
        <div class="mx-auto mt-12 grid max-w-3xl gap-5 md:grid-cols-2">
            <div class="bg-card rounded-3xl border p-8">
                <p
                    class="text-muted-foreground text-xs font-extrabold tracking-widest"
                >
                    FREE
                </p>
                <p
                    class="font-numeric mt-3 text-3xl font-extrabold tabular-nums"
                >
                    <MoneyText :value="0" />
                </p>
                <ul class="mt-6 space-y-2.5 text-sm">
                    <li>3 kantong, 3 anggota</li>
                    <li>Subdomain acak</li>
                    <li>Transaksi, transfer, kategori</li>
                    <li>Foto struk</li>
                </ul>
                <Button as-child variant="outline" class="mt-8 min-h-11 w-full">
                    <Link :href="register()">Mulai dengan Free</Link>
                </Button>
            </div>
            <div
                class="bg-card border-primary relative rounded-3xl border-2 p-8"
            >
                <span
                    class="bg-money-out absolute -top-3 left-1/2 -translate-x-1/2 rounded-full px-4 py-1 text-xs font-extrabold whitespace-nowrap text-white"
                    >Paling dipilih</span
                >
                <p
                    class="text-muted-foreground text-xs font-extrabold tracking-widest"
                >
                    PRO
                </p>
                <p
                    class="font-numeric mt-3 text-3xl font-extrabold tabular-nums"
                >
                    <MoneyText :value="35000" />
                    <span class="text-muted-foreground text-base font-semibold"
                        >/bulan</span
                    >
                </p>
                <ul class="mt-6 space-y-2.5 text-sm">
                    <li>Semua fitur Free</li>
                    <li>Tanpa batas kantong dan anggota</li>
                    <li>Subdomain pilihan sendiri</li>
                    <li>Export laporan</li>
                </ul>
                <Button as-child class="mt-8 min-h-11 w-full">
                    <Link :href="register()">Naik ke Pro</Link>
                </Button>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ArrowDownLeft, ArrowUpRight } from '@lucide/vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';

defineProps<{
    total: number | string;
    income: number | string;
    expense: number | string;
    period: string;
}>();
</script>

<template>
    <!--
        Satu-satunya angka besar di layar. Lingkaran samar di kanan atas adalah
        motif identitas yang berulang di kartu-kartu hijau lain; ia dipotong oleh
        overflow-hidden supaya tidak pernah menambah lebar halaman.
    -->
    <section
        class="bg-brand text-brand-foreground relative overflow-hidden rounded-2xl p-4"
    >
        <div
            class="pointer-events-none absolute -top-16 -right-10 size-48 rounded-full bg-white/5"
        />

        <div class="relative">
            <p class="text-xs opacity-80">Total saldo semua kantong</p>
            <p class="font-numeric mt-1 text-3xl font-bold tabular-nums">
                <MoneyText :value="total" />
            </p>

            <div class="mt-4 grid grid-cols-2 gap-2">
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="flex items-center gap-1 text-[11px] opacity-85">
                        <ArrowDownLeft class="size-3.5" aria-hidden="true" />
                        Pemasukan
                    </p>
                    <p class="font-numeric mt-0.5 font-semibold tabular-nums">
                        <MoneyText :value="income" />
                    </p>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="flex items-center gap-1 text-[11px] opacity-85">
                        <ArrowUpRight class="size-3.5" aria-hidden="true" />
                        Pengeluaran
                    </p>
                    <p class="font-numeric mt-0.5 font-semibold tabular-nums">
                        <MoneyText :value="expense" />
                    </p>
                </div>
            </div>

            <p class="mt-3 text-[11px] opacity-75">{{ period }}</p>
        </div>
    </section>
</template>

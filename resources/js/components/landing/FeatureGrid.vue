<script setup lang="ts">
import {
    ArrowLeftRight,
    Camera,
    CreditCard,
    Smartphone,
    Users,
    Wallet,
} from '@lucide/vue';

const features = [
    {
        icon: Wallet,
        title: 'Kantong terpisah',
        description:
            'Tiap pos uang punya saldo dan riwayat sendiri: kas harian, tabungan, dana acara.',
    },
    {
        icon: Users,
        title: 'Anggota dan peran',
        description:
            'Pemilik, admin, anggota. Yang sensitif seperti ubah role dan billing tetap terkunci.',
    },
    {
        icon: ArrowLeftRight,
        title: 'Transfer antar kantong',
        description:
            'Pindah saldo tanpa mengotori laporan pemasukan dan pengeluaran.',
    },
    {
        icon: CreditCard,
        title: 'Kartu kredit dan paylater',
        description:
            'Utang tercatat sebagai utang. Pelunasan mengurangi dua sisi sekaligus.',
    },
    {
        icon: Camera,
        title: 'Foto struk',
        description:
            'Lampirkan bukti belanja di tiap transaksi. Tidak ada lagi nota hilang.',
    },
    {
        icon: Smartphone,
        title: 'Enak di HP',
        description:
            'Dibuat mobile-first karena dibuka dari saku, bukan meja. Tanpa install.',
    },
];
</script>

<template>
    <section id="fitur" class="scroll-mt-20 space-y-3">
        <h2
            class="mx-auto max-w-2xl text-center text-3xl font-extrabold tracking-tight md:text-4xl"
        >
            Semua yang dibutuhkan kas bersama yang transparan.
        </h2>
        <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <li
                v-for="feature in features"
                :key="feature.title"
                class="bg-card rounded-3xl border p-7"
            >
                <span
                    class="bg-brand/10 flex size-11 items-center justify-center rounded-2xl"
                    aria-hidden="true"
                >
                    <component
                        :is="feature.icon"
                        class="text-money-in size-5"
                    />
                </span>
                <h3 class="mt-4 font-extrabold">{{ feature.title }}</h3>
                <p class="text-muted-foreground mt-2 text-sm leading-relaxed">
                    {{ feature.description }}
                </p>
            </li>
        </ul>
    </section>
</template>

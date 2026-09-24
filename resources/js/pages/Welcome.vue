<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Camera,
    CreditCard,
    Tags,
    UserPlus,
    Wallet,
} from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { dashboard, login, register } from '@/routes';

// Kartu hero meniru kartu saldo aplikasi (latar hijau tua, angka Archivo
// tabular) supaya pengunjung mengenali produknya, bukan ilustrasi generik.
// Nominal Rp 0: setiap tenant memang mulai dari nol, jadi bukan klaim.
const steps = [
    {
        title: 'Buat kantong',
        description:
            'Pisahkan uang ke kantong: tunai, bank, e-wallet, kartu kredit.',
    },
    {
        title: 'Catat',
        description:
            'Pemasukan, pengeluaran, transfer, plus foto struk sebagai bukti.',
    },
    {
        title: 'Undang',
        description:
            'Kirim email, tentukan peran admin atau anggota, kelola bersama.',
    },
];

const features = [
    {
        icon: Wallet,
        title: 'Kantong terpisah',
        description: 'Tiap pos uang punya saldo sendiri dan riwayat sendiri.',
    },
    {
        icon: ArrowLeftRight,
        title: 'Transfer antar kantong',
        description: 'Pindah saldo tanpa mengotori laporan pemasukan.',
    },
    {
        icon: Tags,
        title: 'Kategori',
        description: 'Kelompokkan belanja supaya laporan mudah dibaca.',
    },
    {
        icon: CreditCard,
        title: 'Kartu kredit dan paylater',
        description: 'Utang tercatat, pelunasan mengurangi dua sisi.',
    },
    {
        icon: Camera,
        title: 'Foto struk',
        description: 'Lampirkan bukti belanja di tiap transaksi.',
    },
    {
        icon: UserPlus,
        title: 'Peran per anggota',
        description: 'Pemilik, admin, anggota. Yang sensitif tetap terkunci.',
    },
];

const faqs = [
    {
        question: 'Apakah tenant lain bisa melihat data kami?',
        answer: 'Tidak. Setiap tenant punya ruang datanya sendiri.',
    },
    {
        question: 'Bagaimana cara mengundang anggota?',
        answer: 'Dari halaman Anggota, kirim email dan pilih peran. Undangan kedaluwarsa dalam 7 hari.',
    },
    {
        question: 'Apakah perlu kartu kredit untuk daftar?',
        answer: 'Tidak. Daftar gratis dengan email atau akun Google.',
    },
];
</script>

<template>
    <Head title="Fluxa: kas bersama, banyak tangan" />

    <div class="bg-background min-h-svh">
        <header
            class="mx-auto flex max-w-3xl items-center justify-between gap-3 p-4"
        >
            <span class="flex items-center gap-2 font-bold">
                <AppLogoIcon class="size-7" aria-hidden="true" />
                Fluxa
            </span>
            <nav class="flex items-center gap-2" aria-label="Akun">
                <template v-if="$page.props.auth.user">
                    <Button as-child class="min-h-11">
                        <Link :href="dashboard()">Buka beranda</Link>
                    </Button>
                </template>
                <template v-else>
                    <Button as-child variant="ghost" class="min-h-11">
                        <Link :href="login()">Masuk</Link>
                    </Button>
                    <Button as-child class="min-h-11">
                        <Link :href="register()">Daftar</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main class="mx-auto max-w-3xl space-y-10 p-4 pb-12">
            <section class="space-y-5">
                <div class="space-y-3">
                    <h1 class="text-3xl font-bold tracking-tight">
                        Satu kas, banyak tangan.
                    </h1>
                    <p class="text-muted-foreground max-w-xl">
                        Fluxa mencatat uang bersama keluarga, RT, atau
                        komunitas. Tiap kantong, transaksi, dan anggota terlihat
                        semua orang. Tanpa tebak-tebakan.
                    </p>
                </div>

                <div class="bg-brand text-brand-foreground rounded-2xl p-5">
                    <p class="text-xs opacity-80">Contoh kartu saldo bersama</p>
                    <p
                        class="font-numeric mt-1 text-3xl font-bold tabular-nums"
                    >
                        <MoneyText :value="0" />
                    </p>
                    <p class="mt-1 text-sm opacity-80">
                        Setiap tenant mulai dari Rp 0. Catatan pertamamu yang
                        mengisinya.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <Button as-child class="min-h-11 sm:w-auto">
                        <Link :href="register()">Buat akun gratis</Link>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        class="min-h-11 sm:w-auto"
                    >
                        <Link href="#harga">Lihat paket Pro</Link>
                    </Button>
                </div>
            </section>

            <section class="space-y-3" aria-label="Cara pakai">
                <h2 class="text-lg font-bold">Mulai dalam tiga langkah</h2>
                <ol class="space-y-2">
                    <li
                        v-for="(step, i) in steps"
                        :key="step.title"
                        class="bg-card flex gap-3 rounded-2xl border p-4"
                    >
                        <span
                            class="bg-brand text-brand-foreground flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                            aria-hidden="true"
                            >{{ i + 1 }}</span
                        >
                        <span>
                            <span class="block text-sm font-semibold">{{
                                step.title
                            }}</span>
                            <span class="text-muted-foreground block text-sm">{{
                                step.description
                            }}</span>
                        </span>
                    </li>
                </ol>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold">Yang bisa dilakukan</h2>
                <ul class="grid gap-2 sm:grid-cols-2">
                    <li
                        v-for="feature in features"
                        :key="feature.title"
                        class="bg-card flex gap-3 rounded-2xl border p-4"
                    >
                        <component
                            :is="feature.icon"
                            class="text-money-in size-5 shrink-0"
                            aria-hidden="true"
                        />
                        <span>
                            <span class="block text-sm font-semibold">{{
                                feature.title
                            }}</span>
                            <span class="text-muted-foreground block text-sm">{{
                                feature.description
                            }}</span>
                        </span>
                    </li>
                </ul>
            </section>

            <section id="harga" class="scroll-mt-4 space-y-3">
                <h2 class="text-lg font-bold">Harga</h2>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="bg-card rounded-2xl border p-4">
                        <p class="text-sm font-semibold">Free</p>
                        <p
                            class="font-numeric mt-1 text-2xl font-bold tabular-nums"
                        >
                            <MoneyText :value="0" />
                        </p>
                        <ul
                            class="text-muted-foreground mt-3 space-y-1 text-sm"
                        >
                            <li>3 kantong, 3 anggota</li>
                            <li>Subdomain acak</li>
                        </ul>
                    </div>
                    <div class="bg-card rounded-2xl border p-4">
                        <p class="text-sm font-semibold">Pro</p>
                        <p
                            class="font-numeric mt-1 text-2xl font-bold tabular-nums"
                        >
                            <MoneyText :value="35000" />
                            <span
                                class="text-muted-foreground text-sm font-normal"
                                >/bulan</span
                            >
                        </p>
                        <ul
                            class="text-muted-foreground mt-3 space-y-1 text-sm"
                        >
                            <li>Tanpa batas kantong dan anggota</li>
                            <li>Subdomain pilihan sendiri</li>
                            <li>Export laporan</li>
                        </ul>
                    </div>
                </div>
                <Button as-child class="min-h-11 w-full sm:w-auto">
                    <Link :href="register()">Daftar gratis</Link>
                </Button>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold">Sering ditanyakan</h2>
                <div class="bg-card divide-y rounded-2xl border px-4">
                    <div v-for="faq in faqs" :key="faq.question" class="py-3">
                        <p class="text-sm font-semibold">{{ faq.question }}</p>
                        <p class="text-muted-foreground mt-0.5 text-sm">
                            {{ faq.answer }}
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t">
            <div
                class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 p-4"
            >
                <span class="flex items-center gap-2 text-sm font-semibold">
                    <AppLogoIcon class="size-5" aria-hidden="true" />
                    Fluxa
                </span>
                <nav class="flex items-center gap-1 text-sm" aria-label="Bawah">
                    <Button as-child variant="ghost" class="min-h-11">
                        <Link :href="login()">Masuk</Link>
                    </Button>
                    <Button as-child variant="ghost" class="min-h-11">
                        <Link :href="register()">Daftar</Link>
                    </Button>
                </nav>
            </div>
        </footer>
    </div>
</template>

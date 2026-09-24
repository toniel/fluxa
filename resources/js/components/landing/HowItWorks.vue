<script setup lang="ts">
const steps = [
    {
        title: 'Daftar dan buat ruang',
        description:
            'Satu ruang keuangan untuk keluarga atau komunitasmu, lengkap dengan kantong pertama.',
    },
    {
        title: 'Undang anggota',
        description:
            'Kirim email, tentukan peran admin atau anggota. Mereka gabung dan langsung bisa melihat.',
    },
    {
        title: 'Catat dan pindahkan',
        description:
            'Pemasukan, pengeluaran, transfer antar kantong, plus foto struk sebagai bukti.',
    },
    {
        title: 'Pantau bareng',
        description:
            'Satu dashboard yang sama untuk semua anggota. Tidak ada yang ditutupi.',
    },
];
</script>

<template>
    <section id="cara-kerja" class="scroll-mt-20 space-y-3">
        <h2
            class="text-center text-3xl font-extrabold tracking-tight md:text-4xl"
        >
            Jalan dalam empat langkah
        </h2>
        <ol class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <li
                v-for="(step, i) in steps"
                :key="step.title"
                class="bg-card rounded-3xl border p-7"
            >
                <span
                    class="bg-brand text-brand-foreground flex size-10 items-center justify-center rounded-xl font-extrabold"
                    aria-hidden="true"
                    >{{ i + 1 }}</span
                >
                <h3 class="mt-4 text-lg font-extrabold">{{ step.title }}</h3>
                <p class="text-muted-foreground mt-2 text-sm leading-relaxed">
                    {{ step.description }}
                </p>
            </li>
        </ol>
    </section>
</template>

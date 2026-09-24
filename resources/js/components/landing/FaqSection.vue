<script setup lang="ts">
const faqs = [
    {
        question: 'Apakah data keuangan saya aman?',
        answer: 'Setiap tenant punya ruang data sendiri yang terisolasi. Anggota hanya melihat tenant yang ia ikuti.',
    },
    {
        question: 'Bisa untuk RT atau organisasi, bukan cuma keluarga?',
        answer: 'Bisa. Justru itu target utamanya: kas yang butuh dilihat banyak orang.',
    },
    {
        question: 'Apakah paket gratis selamanya?',
        answer: 'Ya. Paket Free tidak dibatasi waktu, cocok untuk kebutuhan kecil.',
    },
    {
        question: 'Bagaimana kalau mau berhenti pakai?',
        answer: 'Akun dan datamu bisa dihapus kapan saja dari pengaturan. Tidak ada denda, tidak ada drama.',
    },
    {
        question: 'Apakah perlu kartu kredit untuk daftar?',
        answer: 'Tidak. Cukup email atau akun Google.',
    },
];
</script>

<template>
    <section id="faq" class="mx-auto w-full max-w-3xl scroll-mt-20 space-y-3">
        <h2
            class="text-center text-3xl font-extrabold tracking-tight md:text-4xl"
        >
            Sering ditanyakan
        </h2>
        <div class="mt-10 space-y-3">
            <details
                v-for="faq in faqs"
                :key="faq.question"
                class="group bg-card rounded-2xl border p-5"
            >
                <summary
                    class="focus-visible:ring-ring flex cursor-pointer items-center justify-between font-bold focus-visible:ring-2 focus-visible:outline-none"
                >
                    {{ faq.question }}
                    <span
                        class="text-muted-foreground ml-3 shrink-0 transition group-open:rotate-180"
                        aria-hidden="true"
                        >▾</span
                    >
                </summary>
                <p class="text-muted-foreground mt-3 text-sm leading-relaxed">
                    {{ faq.answer }}
                </p>
            </details>
        </div>
    </section>
</template>

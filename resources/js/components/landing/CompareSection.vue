<script setup lang="ts">
const rows = [
    {
        oldTitle: 'Chat grup + buku tulis',
        oldPoints: [
            'Catatan keburu ketimbun chat',
            'Rawan hilang dan salah hitung',
            'Cuma bendahara yang pegang',
        ],
        newPoints: [
            'Riwayat rapi dan tersimpan',
            'Hitungan otomatis, anti selisih',
            'Semua anggota bisa pantau',
        ],
    },
    {
        oldTitle: 'Spreadsheet',
        oldPoints: [
            'Ribet dibuka di HP',
            'Rumus gampang error',
            'Tanpa kontrol akses per anggota',
        ],
        newPoints: [
            'Tampilan mobile yang rapi',
            'Siap pakai, tanpa rumus',
            'Peran dan hak akses jelas',
        ],
    },
];
</script>

<template>
    <section class="space-y-8">
        <div class="space-y-3 text-center">
            <h2 class="text-3xl font-extrabold tracking-tight md:text-4xl">
                Bandingin sendiri.
            </h2>
            <p class="text-muted-foreground">Cara lama versus cara Fluxa.</p>
        </div>
        <div
            v-for="row in rows"
            :key="row.oldTitle"
            class="grid items-stretch gap-4 md:grid-cols-[1fr_auto_1fr]"
        >
            <div class="bg-card rounded-3xl border p-7">
                <h3 class="text-lg font-extrabold">{{ row.oldTitle }}</h3>
                <ul class="text-muted-foreground mt-4 space-y-2 text-sm">
                    <li v-for="point in row.oldPoints" :key="point">
                        {{ point }}
                    </li>
                </ul>
            </div>
            <div class="flex items-center justify-center" aria-hidden="true">
                <span
                    class="bg-brand text-brand-foreground flex size-10 items-center justify-center rounded-full text-xs font-extrabold"
                    >VS</span
                >
            </div>
            <div class="bg-card border-primary rounded-3xl border-2 p-7">
                <h3 class="text-lg font-extrabold">Fluxa.</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li v-for="point in row.newPoints" :key="point">
                        {{ point }}
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>

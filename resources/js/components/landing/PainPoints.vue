<script setup lang="ts">
const pains = [
    {
        quote: '"Uang kas sudah terkumpul, tapi tidak ada yang tahu pasti sisanya berapa."',
        description:
            'Catatan tercecer di chat grup dan ingatan bendahara. Pas ditanya, jawabnya minta waktu cek dulu.',
    },
    {
        quote: '"Sudah coba catat di spreadsheet, tapi cuma bendahara yang bisa buka."',
        description:
            'Anggota lain cuma bisa percaya. Kalau ada selisih, susah dilacak ujungnya.',
    },
    {
        quote: '"Iuran nunggak berbulan-bulan, sungkan nagihnya."',
        description:
            'Tidak ada catatan resmi siapa sudah bayar dan siapa belum. Dibiarkan jadi boncos.',
    },
];
</script>

<template>
    <section class="space-y-3">
        <h2
            class="text-center text-3xl font-extrabold tracking-tight md:text-4xl"
        >
            Kedengarannya familiar?
        </h2>
        <div class="mt-10 grid gap-5 md:grid-cols-3">
            <div
                v-for="pain in pains"
                :key="pain.quote"
                class="bg-card rounded-3xl border p-7"
            >
                <p class="text-lg leading-snug font-extrabold">
                    {{ pain.quote }}
                </p>
                <p class="text-muted-foreground mt-3 text-sm leading-relaxed">
                    {{ pain.description }}
                </p>
            </div>
        </div>
        <p class="text-muted-foreground mx-auto mt-10 max-w-xl text-center">
            Bukan karena anggotamu tidak amanah, tapi karena belum ada sistem
            yang transparan. Fluxa dibuat untuk itu.
        </p>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import type { ChartData, ChartOptions } from 'chart.js';
import { useAppearance } from '@/composables/useAppearance';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { chartPalette, chartTextColor, rupiahTooltip } from '@/lib/chart';

type Slice = { category: string; total: number | string };

const props = defineProps<{ slices: Slice[] }>();

// Dibaca di sini supaya ganti tema me-render ulang chart dengan warna baru.
const { resolvedAppearance } = useAppearance();

const parsed = computed(() =>
    props.slices.map((s) => ({
        category: s.category,
        total: Number.parseFloat(String(s.total)),
    })),
);

// Warna dibaca di dalam computed: kelas dark di <html> sudah diganti
// sebelum render berikutnya berjalan.
const theme = computed(() => {
    const dark = resolvedAppearance.value === 'dark';

    return { dark, text: chartTextColor(), palette: chartPalette() };
});

const total = computed(() => parsed.value.reduce((acc, r) => acc + r.total, 0));

const data = computed<ChartData<'doughnut'>>(() => ({
    labels: parsed.value.map((r) => r.category),
    datasets: [
        {
            data: parsed.value.map((r) => r.total),
            backgroundColor: parsed.value.map(
                (_, i) => theme.value.palette[i % theme.value.palette.length],
            ),
            borderWidth: 2,
        },
    ],
}));

const options = computed<ChartOptions<'doughnut'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: rupiahTooltip } },
    },
}));
</script>

<template>
    <div class="space-y-4">
        <div
            class="relative mx-auto h-36 w-full max-w-55"
            role="img"
            :aria-label="`Pengeluaran per kategori, total ${total}`"
        >
            <Doughnut :data="data" :options="options" />
        </div>

        <!--
            Legenda membawa nama, persentase, dan nominal sekaligus, jadi isi
            donat tetap terbaca penuh tanpa mengandalkan warna.
        -->
        <ul class="space-y-2">
            <li
                v-for="(row, i) in parsed"
                :key="row.category"
                class="flex items-center gap-2 text-sm"
            >
                <span
                    class="size-2.5 shrink-0 rounded-full"
                    :style="{
                        backgroundColor:
                            theme.palette[i % theme.palette.length],
                    }"
                    aria-hidden="true"
                />
                <span class="min-w-0 flex-1 truncate">{{ row.category }}</span>
                <span
                    class="text-muted-foreground font-numeric shrink-0 tabular-nums"
                    >{{
                        total ? Math.round((row.total / total) * 100) : 0
                    }}%</span
                >
                <MoneyText
                    :value="row.total"
                    class="w-28 shrink-0 text-right text-sm"
                />
            </li>
        </ul>
    </div>
</template>

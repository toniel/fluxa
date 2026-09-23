<script setup lang="ts">
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import type { ChartData, ChartOptions } from 'chart.js';
import { useAppearance } from '@/composables/useAppearance';
import {
    chartGridColor,
    chartPalette,
    chartTextColor,
    rupiahTooltip,
} from '@/lib/chart';
import { formatRupiah } from '@/lib/currency';

type Bucket = {
    label: string;
    income: number | string;
    expense: number | string;
};

const props = defineProps<{ buckets: Bucket[] }>();

// Dibaca di sini supaya ganti tema me-render ulang chart dengan warna baru.
const { resolvedAppearance } = useAppearance();

const parsed = computed(() =>
    props.buckets.map((b) => ({
        label: b.label,
        income: Number.parseFloat(String(b.income)),
        expense: Number.parseFloat(String(b.expense)),
    })),
);

// Warna dibaca di dalam computed supaya ganti tema ikut me-render ulang:
// kelas dark di <html> sudah diganti sebelum render berikutnya berjalan.
const theme = computed(() => {
    const dark = resolvedAppearance.value === 'dark';

    return {
        dark,
        text: chartTextColor(),
        grid: chartGridColor(),
        palette: chartPalette(),
    };
});

const data = computed<ChartData<'bar'>>(() => ({
    labels: parsed.value.map((b) => b.label),
    datasets: [
        {
            label: 'Masuk',
            data: parsed.value.map((b) => b.income),
            backgroundColor: theme.value.palette[0],
            borderRadius: 6,
        },
        {
            label: 'Keluar',
            data: parsed.value.map((b) => b.expense),
            backgroundColor: theme.value.palette[3],
            borderRadius: 6,
        },
    ],
}));

const options = computed<ChartOptions<'bar'>>(() => {
    const text = theme.value.text;
    const grid = theme.value.grid;

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: text, boxWidth: 12, usePointStyle: true },
            },
            tooltip: {
                callbacks: { label: rupiahTooltip },
            },
        },
        scales: {
            x: { ticks: { color: text }, grid: { display: false } },
            y: {
                ticks: {
                    color: text,
                    callback: (value) =>
                        formatRupiah(typeof value === 'number' ? value : 0),
                },
                grid: { color: grid },
                border: { display: false },
            },
        },
    };
});

const description = computed(() =>
    parsed.value
        .map(
            (r) =>
                `${r.label} masuk ${formatRupiah(r.income)}, keluar ${formatRupiah(r.expense)}`,
        )
        .join('; '),
);
</script>

<template>
    <div
        class="relative h-48"
        role="img"
        :aria-label="`Arus kas per minggu: ${description}`"
    >
        <Bar :data="data" :options="options" />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';

type Slice = { category: string; total: number | string };

const props = defineProps<{ slices: Slice[] }>();

// Slot warna tidak pernah diputar ulang: kategori ke-3 tetap memakai warna
// ke-3 walau kategori lain hilang dari daftar.
const PALETTE = [
    'var(--cat-1)',
    'var(--cat-2)',
    'var(--cat-3)',
    'var(--cat-4)',
    'var(--cat-5)',
];

const RADIUS = 52;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;
const GAP = 3;

const rows = computed(() => {
    const parsed = props.slices.map((s, i) => ({
        category: s.category,
        total: Number.parseFloat(String(s.total)),
        color: PALETTE[i % PALETTE.length],
    }));

    const sum = parsed.reduce((acc, r) => acc + r.total, 0) || 1;
    let offset = 0;

    return parsed.map((r) => {
        const fraction = r.total / sum;
        const length = Math.max(fraction * CIRCUMFERENCE - GAP, 0);
        const row = {
            ...r,
            percent: Math.round(fraction * 100),
            dash: `${length} ${CIRCUMFERENCE - length}`,
            offset: -offset,
        };
        offset += fraction * CIRCUMFERENCE;

        return row;
    });
});
</script>

<template>
    <div class="space-y-4">
        <svg
            viewBox="0 0 140 140"
            class="mx-auto block size-36"
            role="presentation"
        >
            <g transform="rotate(-90 70 70)">
                <circle
                    v-for="row in rows"
                    :key="row.category"
                    cx="70"
                    cy="70"
                    :r="RADIUS"
                    fill="none"
                    :stroke="row.color"
                    stroke-width="18"
                    :stroke-dasharray="row.dash"
                    :stroke-dashoffset="row.offset"
                />
            </g>
        </svg>

        <!--
            Legenda membawa nama, persentase, dan nominal sekaligus, jadi isi
            donat tetap terbaca penuh tanpa mengandalkan warna.
        -->
        <ul class="space-y-2">
            <li
                v-for="row in rows"
                :key="row.category"
                class="flex items-center gap-2 text-sm"
            >
                <span
                    class="size-2.5 shrink-0 rounded-full"
                    :style="{ backgroundColor: row.color }"
                    aria-hidden="true"
                />
                <span class="min-w-0 flex-1 truncate">{{ row.category }}</span>
                <span
                    class="text-muted-foreground font-numeric shrink-0 tabular-nums"
                    >{{ row.percent }}%</span
                >
                <MoneyText
                    :value="row.total"
                    class="w-28 shrink-0 text-right text-sm"
                />
            </li>
        </ul>
    </div>
</template>

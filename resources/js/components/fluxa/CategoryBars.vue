<script setup lang="ts">
import { computed } from 'vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';

type Slice = {
    category: string;
    total: number | string;
};

const props = defineProps<{
    slices: Slice[];
}>();

const rows = computed(() => {
    const parsed = props.slices.map((slice) => ({
        category: slice.category,
        total: Number.parseFloat(String(slice.total)),
    }));

    const max = Math.max(...parsed.map((row) => row.total), 0);

    return parsed.map((row) => ({
        ...row,
        percent: max > 0 ? Math.max((row.total / max) * 100, 1.5) : 0,
    }));
});
</script>

<template>
    <!--
        Batang berperingkat, bukan donat: di lebar 360px sebuah donat memaksa
        legenda terpisah dan potongan kecil jadi tak terbaca, sedangkan batang
        menaruh nama dan nominal pada baris yang sama.

        Satu warna untuk semua batang karena ini satu seri (pengeluaran), bukan
        identitas kategori. Amber dipakai sebagai arah uang keluar.
    -->
    <ul class="space-y-2.5">
        <li v-for="row in rows" :key="row.category" class="space-y-1">
            <div class="flex items-baseline justify-between gap-3 text-sm">
                <span class="min-w-0 truncate">{{ row.category }}</span>
                <MoneyText :value="row.total" class="shrink-0 text-sm" />
            </div>
            <div class="bg-muted h-2 overflow-hidden rounded-sm">
                <div
                    class="bg-chart-out h-full rounded-r-[4px]"
                    :style="{ width: `${row.percent}%` }"
                />
            </div>
        </li>
    </ul>
</template>

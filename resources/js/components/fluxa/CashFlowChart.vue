<script setup lang="ts">
import { computed } from 'vue';
import { formatRupiah } from '@/lib/currency';

type Bucket = {
    label: string;
    income: number | string;
    expense: number | string;
};

const props = defineProps<{ buckets: Bucket[] }>();

const rows = computed(() => {
    const parsed = props.buckets.map((b) => ({
        label: b.label,
        income: Number.parseFloat(String(b.income)),
        expense: Number.parseFloat(String(b.expense)),
    }));

    const max = Math.max(...parsed.flatMap((r) => [r.income, r.expense]), 1);

    return parsed.map((r) => ({
        ...r,
        incomePct: (r.income / max) * 100,
        expensePct: (r.expense / max) * 100,
    }));
});
</script>

<template>
    <!--
        Dua seri berdampingan per minggu. Hijau/amber, bukan hijau/merah seperti
        rujukannya: pasangan hijau-merah hanya terpisah ΔE 5.0 di mata deutan,
        sehingga kedua batang melebur bagi sebagian pembaca.
    -->
    <div class="space-y-3">
        <div
            class="flex h-32 items-end gap-2"
            role="img"
            :aria-label="`Arus kas per minggu: ${rows.map((r) => `${r.label} masuk ${formatRupiah(r.income)}, keluar ${formatRupiah(r.expense)}`).join('; ')}`"
        >
            <div
                v-for="row in rows"
                :key="row.label"
                class="flex h-full flex-1 flex-col justify-end gap-1"
            >
                <div class="flex h-full items-end justify-center gap-[2px]">
                    <div
                        class="bg-chart-in w-2.5 rounded-t-[4px]"
                        :style="{
                            height: `${Math.max(row.incomePct, row.income > 0 ? 2 : 0)}%`,
                        }"
                    />
                    <div
                        class="bg-chart-out w-2.5 rounded-t-[4px]"
                        :style="{
                            height: `${Math.max(row.expensePct, row.expense > 0 ? 2 : 0)}%`,
                        }"
                    />
                </div>
                <p class="text-muted-foreground text-center text-[10px]">
                    {{ row.label }}
                </p>
            </div>
        </div>

        <ul class="text-muted-foreground flex items-center gap-4 text-xs">
            <li class="flex items-center gap-1.5">
                <span
                    class="bg-chart-in size-2.5 rounded-full"
                    aria-hidden="true"
                />
                Pemasukan
            </li>
            <li class="flex items-center gap-1.5">
                <span
                    class="bg-chart-out size-2.5 rounded-full"
                    aria-hidden="true"
                />
                Pengeluaran
            </li>
        </ul>
    </div>
</template>

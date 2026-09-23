<script setup lang="ts">
import {
    CreditCard,
    HandCoins,
    Landmark,
    PiggyBank,
    Smartphone,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { formatRupiah } from '@/lib/currency';

const props = withDefaults(
    defineProps<{
        name: string;
        type: string;
        balance: number | string;
        // Absent saat belum ada data transaksi, yang menyembunyikan baris jumlah.
        txCount?: number;
        archived?: boolean;
        logoUrl?: string;
        // Absent untuk kantong aset atau kartu tanpa limit.
        creditLimit?: string | null;
    }>(),
    { txCount: undefined, archived: false, logoUrl: '', creditLimit: null },
);

/**
 * Tipe kantong menentukan ikon sekaligus warnanya, jadi satu jenis kantong
 * selalu tampil sama di seluruh aplikasi.
 */
const styles = {
    cash: {
        icon: Wallet,
        label: 'Tunai',
        tint: 'bg-cat-1/12 text-cat-1',
        blob: 'bg-cat-1/12',
    },
    bank: {
        icon: Landmark,
        label: 'Rekening bank',
        tint: 'bg-cat-2/12 text-cat-2',
        blob: 'bg-cat-2/12',
    },
    ewallet: {
        icon: Smartphone,
        label: 'E-wallet',
        tint: 'bg-cat-5/12 text-cat-5',
        blob: 'bg-cat-5/12',
    },
    credit_card: {
        icon: CreditCard,
        label: 'Kartu kredit',
        tint: 'bg-cat-4/12 text-cat-4',
        blob: 'bg-cat-4/12',
    },
    paylater: {
        icon: HandCoins,
        label: 'Paylater',
        tint: 'bg-cat-4/12 text-cat-4',
        blob: 'bg-cat-4/12',
    },
    other: {
        icon: PiggyBank,
        label: 'Lainnya',
        tint: 'bg-cat-3/12 text-cat-3',
        blob: 'bg-cat-3/12',
    },
} as const;

const style = computed(
    () => styles[props.type as keyof typeof styles] ?? styles.other,
);

// Saldo minus diberi warna arah keluar supaya kantong yang jebol terlihat
// sebelum tanda minusnya sempat dibaca.
const direction = computed(() =>
    Number.parseFloat(String(props.balance)) < 0 ? 'out' : 'neutral',
);

// Persen limit terpakai; null kalau tak ada limit atau limit nol.
const utilization = computed(() => {
    const limit = Number.parseFloat(props.creditLimit ?? '');

    if (!Number.isFinite(limit) || limit <= 0) {
        return null;
    }

    return Math.max(
        0,
        Math.round((Number.parseFloat(String(props.balance)) / limit) * 100),
    );
});
</script>

<template>
    <article
        class="bg-card relative overflow-hidden rounded-2xl border p-4"
        :class="archived && 'opacity-70'"
    >
        <!-- Blob dipotong oleh overflow-hidden, jadi ia tidak pernah menambah
             lebar halaman betapapun besarnya. -->
        <div
            class="pointer-events-none absolute -top-8 -right-8 size-28 rounded-full"
            :class="style.blob"
            aria-hidden="true"
        />

        <div class="relative flex items-start justify-between gap-3">
            <span
                v-if="logoUrl"
                class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-white"
            >
                <img
                    :src="logoUrl"
                    :alt="`Logo ${name}`"
                    class="size-full object-contain"
                />
            </span>
            <span
                v-else
                class="flex size-11 shrink-0 items-center justify-center rounded-full"
                :class="style.tint"
                aria-hidden="true"
            >
                <component :is="style.icon" class="size-5" />
            </span>

            <span
                class="bg-card/85 text-muted-foreground shrink-0 rounded-full px-2.5 py-1 text-[11px] font-medium"
            >
                {{ style.label }}
            </span>
        </div>

        <div class="relative mt-3">
            <p class="text-muted-foreground truncate text-sm">{{ name }}</p>
            <p class="text-xl font-bold">
                <MoneyText :value="balance" :direction="direction" />
            </p>
            <p
                v-if="utilization !== null"
                class="text-muted-foreground mt-0.5 text-xs"
            >
                Terpakai {{ utilization }}% dari
                {{ formatRupiah(creditLimit ?? 0) }}
            </p>
            <p
                v-else-if="txCount !== undefined"
                class="text-muted-foreground mt-0.5 text-xs"
            >
                {{ txCount }} transaksi
            </p>
        </div>

        <div
            v-if="$slots.actions"
            class="relative mt-3 flex items-center gap-1 border-t pt-2"
        >
            <slot name="actions" />
        </div>
    </article>
</template>

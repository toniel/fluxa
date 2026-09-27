<script setup lang="ts">
import {
    CreditCard,
    HandCoins,
    Landmark,
    Smartphone,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { accountIcon } from '@/lib/accountIcons';
import { colorTint, isColorSlot } from '@/lib/categoryVisual';

const props = withDefaults(
    defineProps<{
        name: string;
        type: string;
        balance: number | string;
        // Penampilan kustom kantong; memakai bawaan tipe bila kosong.
        icon?: string | null;
        color?: string | null;
    }>(),
    { icon: null, color: null },
);

const icons = {
    cash: Wallet,
    bank: Landmark,
    ewallet: Smartphone,
    credit_card: CreditCard,
    paylater: HandCoins,
} as const;

const typeIcon = computed(
    () => icons[props.type as keyof typeof icons] ?? Wallet,
);

const icon = computed(() => accountIcon(props.icon) ?? typeIcon.value);

const tint = computed(() =>
    props.color && isColorSlot(props.color)
        ? colorTint[props.color]
        : 'bg-brand/12 text-brand',
);

// Saldo minus diberi warna arah keluar supaya kantong yang jebol terlihat
// tanpa harus membaca tanda minusnya lebih dulu.
const direction = computed(() =>
    Number.parseFloat(String(props.balance)) < 0 ? 'out' : 'neutral',
);
</script>

<template>
    <div class="bg-card rounded-xl border p-3">
        <span
            class="flex size-9 items-center justify-center rounded-full"
            :class="tint"
            aria-hidden="true"
        >
            <component :is="icon" class="size-4" />
        </span>
        <p class="text-muted-foreground mt-2 truncate text-xs">{{ name }}</p>
        <p class="truncate font-semibold">
            <MoneyText :value="balance" :direction="direction" />
        </p>
    </div>
</template>

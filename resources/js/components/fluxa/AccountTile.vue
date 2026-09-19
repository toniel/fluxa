<script setup lang="ts">
import { Landmark, Smartphone, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import IconBadge from '@/components/fluxa/IconBadge.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';

const props = defineProps<{
    name: string;
    type: string;
    balance: number | string;
}>();

const icons = { cash: Wallet, bank: Landmark, ewallet: Smartphone } as const;

const icon = computed(() => icons[props.type as keyof typeof icons] ?? Wallet);

// Saldo minus diberi warna arah keluar supaya kantong yang jebol terlihat
// tanpa harus membaca tanda minusnya lebih dulu.
const direction = computed(() =>
    Number.parseFloat(String(props.balance)) < 0 ? 'out' : 'neutral',
);
</script>

<template>
    <div class="bg-card rounded-xl border p-3">
        <IconBadge :icon="icon" tone="brand" size="sm" />
        <p class="text-muted-foreground mt-2 truncate text-xs">{{ name }}</p>
        <p class="truncate font-semibold">
            <MoneyText :value="balance" :direction="direction" />
        </p>
    </div>
</template>

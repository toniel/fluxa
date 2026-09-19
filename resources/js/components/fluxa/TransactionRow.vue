<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import IconBadge from '@/components/fluxa/IconBadge.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';

defineProps<{
    icon: LucideIcon;
    title: string;
    meta: string;
    amount: number | string;
    direction: 'in' | 'out';
}>();
</script>

<template>
    <div class="flex items-center gap-3 py-3">
        <IconBadge :icon="icon" :tone="direction" size="sm" />

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium">{{ title }}</p>
            <p class="text-muted-foreground truncate text-xs">{{ meta }}</p>
        </div>

        <MoneyText
            :value="amount"
            :direction="direction"
            signed
            class="shrink-0 text-sm font-semibold"
        />
    </div>
</template>

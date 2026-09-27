<script setup lang="ts">
import CategoryBadge from '@/components/fluxa/CategoryBadge.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';

withDefaults(
    defineProps<{
        title: string;
        meta: string;
        amount: number | string;
        direction: 'in' | 'out';
        icon?: string | null;
        emoji?: string | null;
        iconUrl?: string | null;
        color?: string | null;
    }>(),
    { icon: null, emoji: null, iconUrl: null, color: null },
);
</script>

<template>
    <div class="flex items-center gap-3 py-3">
        <CategoryBadge
            :icon="icon"
            :emoji="emoji"
            :icon-url="iconUrl"
            :color="color"
            :tone="direction"
            size="sm"
        />

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

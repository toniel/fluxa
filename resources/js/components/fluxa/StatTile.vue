<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';

defineProps<{
    label: string;
    value: number | string;
    direction?: 'in' | 'out' | 'neutral';
    icon?: LucideIcon;
}>();
</script>

<template>
    <div class="bg-card rounded-lg border p-3">
        <p
            class="text-muted-foreground flex items-center gap-1.5 text-xs font-medium"
        >
            <component
                :is="icon"
                v-if="icon"
                class="size-3.5"
                aria-hidden="true"
            />
            {{ label }}
        </p>
        <p class="mt-1 text-lg font-semibold">
            <MoneyText :value="value" :direction="direction" />
        </p>
    </div>
</template>

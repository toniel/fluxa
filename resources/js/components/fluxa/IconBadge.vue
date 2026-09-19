<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

withDefaults(
    defineProps<{
        icon: LucideIcon;
        tone?: 'brand' | 'in' | 'out' | 'neutral';
        size?: 'sm' | 'md';
    }>(),
    { tone: 'neutral', size: 'md' },
);

const tones: Record<string, string> = {
    brand: 'bg-accent text-brand',
    in: 'bg-money-in/10 text-money-in',
    out: 'bg-money-out/10 text-money-out',
    neutral: 'bg-muted text-muted-foreground',
};
</script>

<template>
    <span
        class="flex shrink-0 items-center justify-center rounded-full"
        :class="[tones[tone], size === 'sm' ? 'size-9' : 'size-10']"
        aria-hidden="true"
    >
        <component :is="icon" :class="size === 'sm' ? 'size-4' : 'size-5'" />
    </span>
</template>

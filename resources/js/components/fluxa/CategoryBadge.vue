<script setup lang="ts">
import { computed } from 'vue';
import { categoryIcon } from '@/lib/categoryIcons';
import { colorTint, isColorSlot } from '@/lib/categoryVisual';
import type { ColorSlot } from '@/lib/categoryVisual';

const props = withDefaults(
    defineProps<{
        icon?: string | null;
        emoji?: string | null;
        iconUrl?: string | null;
        /** Warna default ketika kategori tak punya slot warna. */
        tone?: 'brand' | 'in' | 'out' | 'neutral';
        /** Slot warna kategori; menimpa tone. */
        color?: string | null;
        alt?: string;
        size?: 'sm' | 'md';
    }>(),
    {
        icon: null,
        emoji: null,
        iconUrl: null,
        tone: 'neutral',
        color: null,
        alt: 'Ikon kategori',
        size: 'md',
    },
);

const tones: Record<string, string> = {
    brand: 'bg-accent text-brand',
    in: 'bg-money-in/12 text-money-in',
    out: 'bg-money-out/12 text-money-out',
    neutral: 'bg-muted text-muted-foreground',
};

const slot = computed<ColorSlot | null>(() =>
    isColorSlot(props.color) ? props.color : null,
);

const containerClass = computed(() =>
    props.iconUrl
        ? 'bg-muted border'
        : slot.value
          ? colorTint[slot.value]
          : tones[props.tone],
);

const container = computed(() => (props.size === 'sm' ? 'size-9' : 'size-10'));
const inner = computed(() => (props.size === 'sm' ? 'size-4' : 'size-5'));
</script>

<template>
    <span
        class="flex shrink-0 items-center justify-center overflow-hidden rounded-full"
        :class="[container, containerClass]"
        aria-hidden="true"
    >
        <img
            v-if="iconUrl"
            :src="iconUrl"
            :alt="alt"
            class="size-full object-cover"
        />
        <span
            v-else-if="emoji"
            class="text-lg leading-none"
            :class="props.size === 'sm' && 'text-base'"
            >{{ emoji }}</span
        >
        <component v-else :is="categoryIcon(icon)" :class="inner" />
    </span>
</template>

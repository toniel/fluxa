<script setup lang="ts">
import { categoryIcon } from '@/lib/categoryIcons';

/**
 * Kisi tombol ikon untuk form. Label per ikon dipakai sebagai aria-label
 * supaya pilihan terbaca namanya, bukan sekadar gambar.
 */
withDefaults(
    defineProps<{
        icons: string[];
        labels?: Record<string, string>;
        modelValue?: string;
    }>(),
    { labels: () => ({}), modelValue: '' },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();
</script>

<template>
    <div class="flex flex-wrap gap-1.5">
        <button
            v-for="icon in icons"
            :key="icon"
            type="button"
            class="focus-visible:ring-ring flex size-12 items-center justify-center rounded-full border p-0 focus-visible:ring-2 focus-visible:outline-none"
            :class="
                modelValue === icon
                    ? 'border-primary bg-primary/10 text-primary'
                    : 'border-input text-muted-foreground hover:bg-accent'
            "
            :aria-label="labels[icon] ?? icon"
            :aria-pressed="modelValue === icon"
            :title="labels[icon] ?? icon"
            @click="emit('update:modelValue', icon)"
        >
            <component :is="categoryIcon(icon)" class="size-5" />
        </button>
    </div>
</template>

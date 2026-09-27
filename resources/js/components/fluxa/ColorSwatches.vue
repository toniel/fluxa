<script setup lang="ts">
/**
 * Pilih slot warna palet kategorikal. Warna digambar lewat var CSS langsung,
 * jadi satu set warna ikut terang/gelap persis seperti donat dashboard.
 */
withDefaults(
    defineProps<{
        colors: string[];
        modelValue?: string;
    }>(),
    { modelValue: '' },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            v-for="color in colors"
            :key="color"
            type="button"
            class="focus-visible:ring-ring size-9 rounded-full border p-0 focus-visible:ring-2 focus-visible:outline-none"
            :class="
                modelValue === color
                    ? 'ring-ring ring-2 ring-offset-2'
                    : 'border-input'
            "
            :style="{ backgroundColor: `var(--${color})` }"
            :aria-label="`Warna ${color}`"
            :aria-pressed="modelValue === color"
            @click="emit('update:modelValue', color)"
        />
        <button
            v-if="modelValue"
            type="button"
            class="text-muted-foreground focus-visible:ring-ring min-h-9 rounded-full px-3 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
            @click="emit('update:modelValue', '')"
        >
            Tanpa warna
        </button>
    </div>
</template>

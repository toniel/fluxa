<script setup lang="ts">
defineProps<{
    title: string;
}>();
</script>

<template>
    <section class="bg-card rounded-lg border">
        <header
            class="flex items-center justify-between gap-3 border-b px-4 py-3"
        >
            <h2 class="text-sm font-semibold">{{ title }}</h2>
            <slot name="action" />
        </header>
        <div class="p-4">
            <slot />
        </div>
    </section>
</template>

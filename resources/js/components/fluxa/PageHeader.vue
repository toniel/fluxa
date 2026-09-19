<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0 space-y-0.5">
            <h1 class="truncate text-xl font-semibold tracking-tight">
                {{ title }}
            </h1>
            <p v-if="description" class="text-muted-foreground text-sm">
                {{ description }}
            </p>
        </div>
        <slot name="action" />
    </header>
</template>

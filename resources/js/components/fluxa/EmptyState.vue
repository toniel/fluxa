<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    icon?: LucideIcon;
    title: string;
    description: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed px-6 py-12 text-center"
    >
        <component
            :is="icon"
            v-if="icon"
            class="text-muted-foreground size-7"
            aria-hidden="true"
        />
        <div class="space-y-1">
            <p class="font-medium">{{ title }}</p>
            <p class="text-muted-foreground max-w-xs text-sm text-balance">
                {{ description }}
            </p>
        </div>
        <slot />
    </div>
</template>

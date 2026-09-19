<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    title: string;
    description: string;
}>();

function reload(): void {
    router.reload();
}
</script>

<template>
    <div
        class="border-destructive/30 bg-destructive/5 flex flex-col items-center gap-3 rounded-lg border px-6 py-12 text-center"
    >
        <TriangleAlert class="text-destructive size-7" aria-hidden="true" />
        <div class="space-y-1">
            <p class="font-medium">{{ title }}</p>
            <p class="text-muted-foreground max-w-xs text-sm text-balance">
                {{ description }}
            </p>
        </div>
        <Button variant="outline" class="min-h-11" @click="reload">
            Muat ulang
        </Button>
    </div>
</template>

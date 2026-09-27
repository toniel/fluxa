<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import CategoryBadge from '@/components/fluxa/CategoryBadge.vue';

type Option = {
    id: number;
    name: string;
    type: string;
    icon?: string | null;
    emoji?: string | null;
    icon_url?: string | null;
    color?: string | null;
};

defineProps<{
    categories: Option[];
    selectedId: number | null;
    emptyHint: string;
    error?: string;
}>();

const emit = defineEmits<{
    (e: 'select', id: number): void;
}>();
</script>

<template>
    <fieldset class="grid gap-2">
        <legend class="text-sm leading-none font-medium">Kategori</legend>
        <p v-if="!categories.length" class="text-muted-foreground text-sm">
            {{ emptyHint }}
        </p>
        <div v-else class="flex flex-wrap gap-1.5">
            <button
                v-for="category in categories"
                :key="category.id"
                type="button"
                class="focus-visible:ring-ring flex min-h-11 items-center gap-2 rounded-full border px-3 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                :class="
                    selectedId === category.id
                        ? 'border-primary bg-primary/10 text-primary'
                        : 'border-input text-muted-foreground hover:bg-accent'
                "
                :aria-pressed="selectedId === category.id"
                @click="emit('select', category.id)"
            >
                <CategoryBadge
                    v-if="category.icon_url || category.emoji || category.icon"
                    :icon="category.icon"
                    :emoji="category.emoji"
                    :icon-url="category.icon_url"
                    :color="category.color"
                    size="sm"
                />
                {{ category.name }}
            </button>
        </div>
        <InputError :message="error" />
    </fieldset>
</template>

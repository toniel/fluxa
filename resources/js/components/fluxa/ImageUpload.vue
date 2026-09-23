<script setup lang="ts">
import { ImagePlus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

withDefaults(
    defineProps<{
        inputId: string;
        inputName: string;
        label: string;
        previewAlt: string;
        initialPreview?: string;
        error?: string;
        /** Logo muat penuh (contain), foto struk dipotong mengisi (cover). */
        cover?: boolean;
    }>(),
    { initialPreview: '', error: undefined, cover: true },
);

const emit = defineEmits<{
    (e: 'select', file: File): void;
    (e: 'clear'): void;
}>();

const preview = ref<string>('');
const fileInput = ref<HTMLInputElement | null>(null);

function onChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (file === null) {
        return;
    }

    if (preview.value.startsWith('blob:')) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = URL.createObjectURL(file);
    emit('select', file);
}

function clear(): void {
    if (preview.value.startsWith('blob:')) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = '';

    if (fileInput.value !== null) {
        fileInput.value.value = '';
    }

    emit('clear');
}

function shownPreview(initialPreview: string): string {
    return preview.value || initialPreview;
}
</script>

<template>
    <div class="grid gap-2">
        <Label :for="inputId">{{ label }}</Label>
        <div class="flex items-center gap-3">
            <span
                class="bg-muted text-muted-foreground flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border"
            >
                <img
                    v-if="shownPreview(initialPreview)"
                    :src="shownPreview(initialPreview)"
                    :alt="previewAlt"
                    class="size-full"
                    :class="cover ? 'object-cover' : 'bg-white object-contain'"
                />
                <ImagePlus v-else class="size-6" aria-hidden="true" />
            </span>
            <Input
                :id="inputId"
                ref="fileInput"
                :name="inputName"
                type="file"
                accept="image/*"
                class="file:text-foreground min-h-11 file:border-0 file:bg-transparent file:text-sm file:font-medium"
                @change="onChange"
            />
            <Button
                v-if="shownPreview(initialPreview)"
                type="button"
                variant="ghost"
                class="min-h-11 shrink-0"
                @click="clear"
            >
                Hapus
            </Button>
        </div>
        <InputError :message="error" />
    </div>
</template>

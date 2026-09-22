<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const open = defineModel<boolean>('open', { default: false });

withDefaults(
    defineProps<{
        title: string;
        description: string;
        confirmLabel?: string;
    }>(),
    { confirmLabel: 'Hapus' },
);

const emit = defineEmits<{
    (e: 'confirm'): void;
}>();

function confirm(): void {
    emit('confirm');
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader class="text-left">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="flex-col gap-2 sm:justify-end">
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11 sm:w-auto"
                    @click="open = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    class="min-h-11 sm:w-auto"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

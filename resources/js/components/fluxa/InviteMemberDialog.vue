<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = withDefaults(
    defineProps<{
        action: string;
        roles?: string[];
    }>(),
    { roles: () => ['admin', 'member'] },
);

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    email: '',
    role: 'member',
});

const roleHint: Record<string, string> = {
    admin: 'Admin — kelola kantong & anggota',
    member: 'Anggota — catat transaksi & lihat laporan',
};

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.reset();
    form.clearErrors();
});

// transform() merusak tipe form.errors, baca lewat cast (lihat CRUD_FLOW §6).
const errorFor = computed<Record<string, string | undefined>>(
    () => form.errors as Record<string, string | undefined>,
);

function submit(): void {
    form.post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="top-auto bottom-0 left-0 max-h-[92svh] w-full max-w-none translate-x-0 translate-y-0 gap-0 overflow-y-auto rounded-t-3xl p-0 sm:max-w-none md:top-1/2 md:bottom-auto md:left-1/2 md:max-w-md md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-3xl"
        >
            <DialogHeader class="border-b px-5 py-4 text-left">
                <DialogTitle class="text-lg font-bold">
                    Undang anggota
                </DialogTitle>
                <DialogDescription class="sr-only">
                    Kirim undangan lewat email dengan role tertentu.
                </DialogDescription>
            </DialogHeader>

            <form
                id="invite-member-form"
                class="space-y-4 px-5 py-4"
                @submit.prevent="submit"
            >
                <div class="space-y-1.5">
                    <Label for="invite-email" class="text-xs font-semibold"
                        >Email</Label
                    >
                    <Input
                        id="invite-email"
                        v-model="form.email"
                        type="email"
                        required
                        class="min-h-11 rounded-xl"
                        placeholder="nama@email.com"
                        autocomplete="off"
                    />
                    <p
                        v-if="errorFor.email"
                        class="text-destructive text-xs"
                        role="alert"
                    >
                        {{ errorFor.email }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="invite-role" class="text-xs font-semibold"
                        >Role</Label
                    >
                    <select
                        id="invite-role"
                        v-model="form.role"
                        class="border-input bg-card focus-visible:ring-ring min-h-11 w-full rounded-xl border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none"
                    >
                        <option v-for="role in roles" :key="role" :value="role">
                            {{ roleHint[role] ?? role }}
                        </option>
                    </select>
                    <p
                        v-if="errorFor.role"
                        class="text-destructive text-xs"
                        role="alert"
                    >
                        {{ errorFor.role }}
                    </p>
                </div>

                <p class="text-muted-foreground text-xs">
                    Undangan dikirim lewat email dan kedaluwarsa dalam 7 hari.
                    Mengundang email yang sama akan mengirim ulang dengan tautan
                    baru.
                </p>
            </form>

            <DialogFooter
                class="flex-row justify-end gap-2 border-t px-5 py-4 pb-[calc(1rem+env(safe-area-inset-bottom))]"
            >
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    @click="open = false"
                >
                    Batal
                </Button>
                <Button
                    type="submit"
                    form="invite-member-form"
                    class="min-h-11"
                    :disabled="form.processing"
                >
                    Kirim undangan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

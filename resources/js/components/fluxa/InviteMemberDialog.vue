<script setup lang="ts">
import { ref, watch } from 'vue';
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
import { notYet } from '@/lib/notYet';

const open = defineModel<boolean>('open', { default: false });

const email = ref('');
const role = ref<'admin' | 'member'>('member');

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    email.value = '';
    role.value = 'member';
});

function submit(): void {
    notYet('Kirim undangan');
    open.value = false;
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

            <form class="space-y-4 px-5 py-4" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <Label for="invite-email" class="text-xs font-semibold"
                        >Email</Label
                    >
                    <Input
                        id="invite-email"
                        v-model="email"
                        type="email"
                        required
                        class="min-h-11 rounded-xl"
                        placeholder="nama@email.com"
                    />
                </div>

                <div class="space-y-1.5">
                    <Label for="invite-role" class="text-xs font-semibold"
                        >Role</Label
                    >
                    <select
                        id="invite-role"
                        v-model="role"
                        class="border-input bg-card focus-visible:ring-ring min-h-11 w-full rounded-xl border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none"
                    >
                        <option value="admin">
                            Admin — kelola kantong &amp; anggota
                        </option>
                        <option value="member">
                            Member — catat transaksi &amp; lihat laporan
                        </option>
                    </select>
                </div>

                <p class="text-muted-foreground text-xs">
                    Undangan dikirim lewat email. Anggota muncul dengan status
                    Menunggu sampai menerima.
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
                <Button type="button" class="min-h-11" @click="submit">
                    Kirim undangan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

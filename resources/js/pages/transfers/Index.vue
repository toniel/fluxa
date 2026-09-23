<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftRight, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import IconBadge from '@/components/fluxa/IconBadge.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { Button } from '@/components/ui/button';
import {
    create as createRoute,
    destroy,
    edit as editRoute,
    index as indexRoute,
    show as showRoute,
} from '@/routes/transfers';

const props = defineProps<{
    transfers: App.Data.TransferData[];
    can: { create: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Transfer', href: indexRoute.url() }],
    },
});

// Kolom bertipe date dibaca sebagai Y-m-d tanpa offset, jadi pin jam tengah
// malam lokal supaya format tidak mundur sehari di zona WIB.
const asLocalDate = (ymd: string): Date => new Date(`${ymd}T00:00:00`);

const rowDate = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const deleteForm = useForm({});
const deleteTarget = ref<App.Data.TransferData | null>(null);

const deleteOpen = computed({
    get: () => deleteTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            deleteTarget.value = null;
        }
    },
});

function confirmDelete(): void {
    const transfer = deleteTarget.value;

    if (transfer === null) {
        return;
    }

    deleteForm.delete(destroy.url(transfer.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Transfer" />

    <div class="space-y-4 p-4">
        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            title="Hapus transfer"
            :description="`Transfer \u201C${deleteTarget?.description || `${deleteTarget?.from_account_name} ke ${deleteTarget?.to_account_name}` || ''}\u201D akan dihapus dan kedua saldo dikembalikan.`"
            confirm-label="Hapus transfer"
            @confirm="confirmDelete"
        />

        <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight">Transfer</h1>
                <p class="text-muted-foreground text-sm">
                    {{ transfers.length }} transfer antar kantong
                </p>
            </div>
            <Button v-if="can.create" as-child class="min-h-11 shrink-0">
                <Link :href="createRoute.url()">
                    <Plus class="size-4" aria-hidden="true" />
                    Tambah
                </Link>
            </Button>
        </header>

        <EmptyState
            v-if="!transfers.length"
            :icon="ArrowLeftRight"
            title="Belum ada transfer"
            description="Pindahkan saldo antar kantong tanpa mengotori pemasukan dan pengeluaran."
        >
            <Button v-if="can.create" as-child class="min-h-11">
                <Link :href="createRoute.url()">
                    <Plus class="size-4" aria-hidden="true" />
                    Buat transfer
                </Link>
            </Button>
        </EmptyState>

        <ul v-else class="bg-card divide-y rounded-2xl border px-3">
            <li
                v-for="item in transfers"
                :key="item.id"
                class="flex items-center gap-3 py-3"
            >
                <IconBadge :icon="ArrowLeftRight" tone="neutral" />

                <Link
                    :href="showRoute.url(item.id)"
                    class="min-w-0 flex-1 rounded-xl"
                >
                    <span class="block min-w-0">
                        <span class="block truncate text-sm font-medium">
                            {{ item.description || 'Transfer' }}
                        </span>
                        <span
                            class="text-muted-foreground block truncate text-xs"
                        >
                            {{ item.from_account_name }} ke
                            {{ item.to_account_name }} ·
                            {{
                                rowDate.format(asLocalDate(item.transfer_date))
                            }}
                        </span>
                    </span>
                </Link>

                <MoneyText
                    :value="item.amount"
                    direction="neutral"
                    class="shrink-0 text-sm font-semibold"
                />

                <div class="flex shrink-0 items-center">
                    <Button
                        v-if="item.can_edit"
                        as-child
                        variant="ghost"
                        size="icon"
                        class="size-11"
                        :aria-label="`Ubah transfer ${item.description || item.id}`"
                    >
                        <Link :href="editRoute.url(item.id)">
                            <Pencil class="size-4" aria-hidden="true" />
                        </Link>
                    </Button>
                    <Button
                        v-if="item.can_delete"
                        variant="ghost"
                        size="icon"
                        class="text-money-out hover:text-money-out size-11"
                        :aria-label="`Hapus transfer ${item.description || item.id}`"
                        @click="deleteTarget = item"
                    >
                        <Trash2 class="size-4" aria-hidden="true" />
                    </Button>
                </div>
            </li>
        </ul>
    </div>
</template>

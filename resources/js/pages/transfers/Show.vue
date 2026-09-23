<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftRight, Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import IconBadge from '@/components/fluxa/IconBadge.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { Button } from '@/components/ui/button';
import {
    destroy,
    edit as editRoute,
    index as indexRoute,
} from '@/routes/transfers';

const props = defineProps<{
    transfer: App.Data.TransferData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transfer', href: indexRoute.url() },
            { title: 'Detail' },
        ],
    },
});

const dayLabel = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

// Kolom bertipe date dibaca sebagai Y-m-d tanpa offset, jadi pin jam tengah
// malam lokal supaya format tidak mundur sehari di zona WIB.
const formattedDate = dayLabel.format(
    new Date(`${props.transfer.transfer_date}T00:00:00`),
);

const rows = computed(() => [
    { label: 'Dari kantong', value: props.transfer.from_account_name },
    { label: 'Ke kantong', value: props.transfer.to_account_name },
    { label: 'Tanggal', value: formattedDate },
    { label: 'Dicatat oleh', value: props.transfer.creator_name },
]);

const deleteForm = useForm({});
const deleteOpen = ref(false);

function confirmDelete(): void {
    deleteForm.delete(destroy.url(props.transfer.id));
}
</script>

<template>
    <Head :title="transfer.description || 'Detail transfer'" />

    <div class="mx-auto max-w-2xl space-y-4 p-4">
        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            title="Hapus transfer"
            :description="`Transfer \u201C${transfer.description || `${transfer.from_account_name} ke ${transfer.to_account_name}`}\u201D akan dihapus dan kedua saldo dikembalikan.`"
            confirm-label="Hapus transfer"
            @confirm="confirmDelete"
        />

        <section class="bg-card rounded-2xl border p-4">
            <div class="flex items-center gap-3">
                <IconBadge :icon="ArrowLeftRight" tone="neutral" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">
                        {{ transfer.description || 'Transfer' }}
                    </p>
                    <p class="text-muted-foreground text-xs">
                        {{ formattedDate }}
                    </p>
                </div>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight">
                <MoneyText :value="transfer.amount" direction="neutral" />
            </p>
        </section>

        <section class="bg-card rounded-2xl border px-4 py-1">
            <dl class="divide-y">
                <div
                    v-for="row in rows"
                    :key="row.label"
                    class="flex items-baseline justify-between gap-3 py-3"
                >
                    <dt class="text-muted-foreground shrink-0 text-sm">
                        {{ row.label }}
                    </dt>
                    <dd class="min-w-0 truncate text-right text-sm font-medium">
                        {{ row.value }}
                    </dd>
                </div>
            </dl>
        </section>

        <div class="flex gap-2">
            <Button v-if="transfer.can_edit" as-child class="min-h-11 flex-1">
                <Link :href="editRoute.url(transfer.id)">
                    <Pencil class="size-4" aria-hidden="true" />
                    Ubah
                </Link>
            </Button>
            <Button
                v-if="transfer.can_delete"
                variant="outline"
                class="text-money-out hover:text-money-out min-h-11 flex-1"
                @click="deleteOpen = true"
            >
                <Trash2 class="size-4" aria-hidden="true" />
                Hapus
            </Button>
        </div>
    </div>
</template>

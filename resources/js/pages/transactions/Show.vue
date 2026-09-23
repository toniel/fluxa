<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import IconBadge from '@/components/fluxa/IconBadge.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { Button } from '@/components/ui/button';
import { categoryIcon } from '@/lib/categoryIcons';
import {
    destroy,
    edit as editRoute,
    index as indexRoute,
} from '@/routes/transactions';

const props = defineProps<{
    transaction: App.Data.TransactionData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transaksi', href: indexRoute.url() },
            { title: 'Detail' },
        ],
    },
});

const direction = computed(() =>
    props.transaction.type === 'income' ? 'in' : 'out',
);

const dayLabel = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

// Kolom bertipe date dibaca sebagai Y-m-d tanpa offset, jadi pin jam tengah
// malam lokal supaya format tidak mundur sehari di zona WIB.
const formattedDate = dayLabel.format(
    new Date(`${props.transaction.transaction_date}T00:00:00`),
);

const typeText = computed(() =>
    props.transaction.type === 'income'
        ? 'Pemasukan'
        : props.transaction.type === 'bill_payment'
          ? 'Bayar tagihan'
          : 'Pengeluaran',
);

const shortDate = (ymd: string): string =>
    dayLabel.format(new Date(`${ymd}T00:00:00`));

const rows = computed(() => [
    { label: 'Jenis', value: typeText.value },
    { label: 'Kantong', value: props.transaction.account_name },
    ...(props.transaction.type === 'bill_payment'
        ? [
              {
                  label: 'Bayar dari',
                  value: props.transaction.linked_account_name ?? '-',
              },
          ]
        : [
              {
                  label: 'Kategori',
                  value: props.transaction.category_name ?? 'Tanpa kategori',
              },
          ]),
    { label: 'Tanggal', value: formattedDate },
    { label: 'Dicatat oleh', value: props.transaction.creator_name },
]);

const deleteForm = useForm({});
const deleteOpen = ref(false);

function confirmDelete(): void {
    deleteForm.delete(destroy.url(props.transaction.id));
}
</script>

<template>
    <Head
        :title="
            transaction.description ||
            transaction.category_name ||
            'Detail transaksi'
        "
    />

    <div class="mx-auto max-w-2xl space-y-4 p-4">
        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            title="Hapus transaksi"
            :description="`Transaksi \u201C${transaction.description || transaction.account_name}\u201D akan dihapus dan saldo kantong dikembalikan.`"
            confirm-label="Hapus transaksi"
            @confirm="confirmDelete"
        />

        <section class="bg-card rounded-2xl border p-4">
            <div class="flex items-center gap-3">
                <IconBadge
                    :icon="categoryIcon(transaction.category_icon)"
                    :tone="direction"
                />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">
                        {{
                            transaction.description ||
                            transaction.category_name ||
                            'Tanpa deskripsi'
                        }}
                    </p>
                    <p class="text-muted-foreground text-xs">
                        {{ formattedDate }}
                    </p>
                </div>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight">
                <MoneyText
                    :value="transaction.amount"
                    :direction="direction"
                    signed
                />
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

        <section
            v-if="transaction.statement_period_end && transaction.due_date"
            class="bg-card rounded-2xl border p-4"
        >
            <h2 class="text-sm font-semibold">Tagihan periode ini</h2>
            <p class="text-muted-foreground mt-1 text-sm">
                Masuk statement sampai
                {{ shortDate(transaction.statement_period_end) }}, jatuh tempo
                {{ shortDate(transaction.due_date) }}.
            </p>
        </section>

        <section v-if="transaction.receipt_url" class="space-y-2">
            <h2 class="px-1 text-sm font-semibold">Foto struk</h2>
            <a
                :href="transaction.receipt_url"
                target="_blank"
                rel="noopener"
                class="bg-card block overflow-hidden rounded-2xl border"
            >
                <img
                    :src="transaction.receipt_url"
                    alt="Foto struk transaksi"
                    class="max-h-96 w-full object-contain"
                    loading="lazy"
                />
            </a>
        </section>

        <div class="flex gap-2">
            <Button
                v-if="transaction.can_edit"
                as-child
                class="min-h-11 flex-1"
            >
                <Link :href="editRoute.url(transaction.id)">
                    <Pencil class="size-4" aria-hidden="true" />
                    Ubah
                </Link>
            </Button>
            <Button
                v-if="transaction.can_delete"
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

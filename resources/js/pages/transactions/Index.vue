<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, ReceiptText, Search, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import TransactionRow from '@/components/fluxa/TransactionRow.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { categoryIcon } from '@/lib/categoryIcons';
import {
    create as createRoute,
    destroy,
    edit as editRoute,
    index as indexRoute,
    show as showRoute,
} from '@/routes/transactions';

const props = defineProps<{
    transactions: App.Data.TransactionData[];
    accounts: { id: number; name: string; balance: string }[];
    categories: { id: number; name: string; type: string }[];
    can: { create: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Transaksi', href: indexRoute.url() }],
    },
});

const search = ref('');
const type = ref<'all' | 'income' | 'expense'>('all');
const account = ref('');
const category = ref('');

const filtered = computed(() =>
    props.transactions.filter((t) => {
        const q = search.value.trim().toLowerCase();
        const hay =
            `${t.description ?? ''} ${t.account_name} ${t.category_name ?? ''}`.toLowerCase();

        return (
            (!q || hay.includes(q)) &&
            (type.value === 'all' || t.type === type.value) &&
            (!account.value || t.account_name === account.value) &&
            (!category.value || (t.category_name ?? '') === category.value)
        );
    }),
);

const sum = (rows: App.Data.TransactionData[], kind: 'income' | 'expense') =>
    rows
        .filter((t) => t.type === kind)
        .reduce((acc, t) => acc + Number.parseFloat(t.amount), 0);

const income = computed(() => sum(filtered.value, 'income'));
const expense = computed(() => sum(filtered.value, 'expense'));

const accountNames = computed(() =>
    [...new Set(props.transactions.map((t) => t.account_name))].sort(),
);
const categoryNames = computed(() =>
    [
        ...new Set(
            props.transactions.map((t) => t.category_name ?? 'Tanpa kategori'),
        ),
    ].sort(),
);

const hasFilter = computed(
    () =>
        Boolean(search.value || account.value || category.value) ||
        type.value !== 'all',
);

function resetFilters(): void {
    search.value = '';
    type.value = 'all';
    account.value = '';
    category.value = '';
}

const dayLabel = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

// Kolom bertipe date dibaca sebagai Y-m-d tanpa offset, jadi pin jam tengah
// malam lokal supaya format tidak mundur sehari di zona WIB.
const asLocalDate = (ymd: string): Date => new Date(`${ymd}T00:00:00`);

const groups = computed(() => {
    const map = new Map<string, App.Data.TransactionData[]>();

    for (const item of filtered.value) {
        map.set(item.transaction_date, [
            ...(map.get(item.transaction_date) ?? []),
            item,
        ]);
    }

    return [...map.entries()]
        .sort((a, b) => b[0].localeCompare(a[0]))
        .map(([date, items]) => ({
            date,
            items,
            net: sum(items, 'income') - sum(items, 'expense'),
        }));
});

const deleteForm = useForm({});
const deleteTarget = ref<App.Data.TransactionData | null>(null);

const deleteOpen = computed({
    get: () => deleteTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            deleteTarget.value = null;
        }
    },
});

function confirmDelete(): void {
    const transaction = deleteTarget.value;

    if (transaction === null) {
        return;
    }

    deleteForm.delete(destroy.url(transaction.id), { preserveScroll: true });
}

const selectClass =
    'border-input bg-card focus-visible:ring-ring min-h-11 w-full rounded-lg border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none';
</script>

<template>
    <Head title="Transaksi" />

    <div class="space-y-4 p-4">
        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            title="Hapus transaksi"
            :description="`Transaksi \u201C${deleteTarget?.description || deleteTarget?.account_name || ''}\u201D akan dihapus dan saldo kantong dikembalikan.`"
            confirm-label="Hapus transaksi"
            @confirm="confirmDelete"
        />

        <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight">Transaksi</h1>
                <p class="text-muted-foreground text-sm">
                    {{ filtered.length }} dari
                    {{ transactions.length }} transaksi
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
            v-if="!transactions.length"
            :icon="ReceiptText"
            title="Belum ada transaksi"
            description="Catat pemasukan atau pengeluaran pertama untuk mulai melihat ringkasannya."
        >
            <Button v-if="can.create" as-child class="min-h-11">
                <Link :href="createRoute.url()">
                    <Plus class="size-4" aria-hidden="true" />
                    Catat transaksi
                </Link>
            </Button>
        </EmptyState>

        <template v-else>
            <section class="bg-card space-y-3 rounded-2xl border p-3">
                <div class="relative">
                    <Search
                        class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        class="min-h-11 rounded-lg pl-9"
                        placeholder="Cari deskripsi"
                        aria-label="Cari deskripsi transaksi"
                    />
                </div>

                <div
                    class="bg-muted grid grid-cols-3 gap-1 rounded-lg p-1"
                    role="group"
                    aria-label="Saring menurut jenis"
                >
                    <button
                        v-for="option in [
                            { value: 'all', label: 'Semua' },
                            { value: 'income', label: 'Pemasukan' },
                            { value: 'expense', label: 'Pengeluaran' },
                        ]"
                        :key="option.value"
                        type="button"
                        class="focus-visible:ring-ring min-h-11 rounded-md px-2 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                        :class="
                            type === option.value
                                ? 'bg-card shadow-sm'
                                : 'text-muted-foreground'
                        "
                        :aria-pressed="type === option.value"
                        @click="type = option.value as typeof type"
                    >
                        {{ option.label }}
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <select
                        v-model="account"
                        :class="selectClass"
                        aria-label="Saring menurut kantong"
                    >
                        <option value="">Semua kantong</option>
                        <option
                            v-for="name in accountNames"
                            :key="name"
                            :value="name"
                        >
                            {{ name }}
                        </option>
                    </select>
                    <select
                        v-model="category"
                        :class="selectClass"
                        aria-label="Saring menurut kategori"
                    >
                        <option value="">Semua kategori</option>
                        <option
                            v-for="name in categoryNames"
                            :key="name"
                            :value="name"
                        >
                            {{ name }}
                        </option>
                    </select>
                </div>

                <Button
                    v-if="hasFilter"
                    variant="ghost"
                    class="min-h-11 w-full"
                    @click="resetFilters"
                >
                    Bersihkan saringan
                </Button>
            </section>

            <div class="grid grid-cols-2 gap-2">
                <div class="bg-card rounded-xl border p-3">
                    <p class="text-muted-foreground text-xs">Pemasukan</p>
                    <p class="mt-0.5 font-semibold">
                        <MoneyText :value="income" direction="in" />
                    </p>
                </div>
                <div class="bg-card rounded-xl border p-3">
                    <p class="text-muted-foreground text-xs">Pengeluaran</p>
                    <p class="mt-0.5 font-semibold">
                        <MoneyText :value="expense" direction="out" />
                    </p>
                </div>
            </div>

            <EmptyState
                v-if="!filtered.length"
                :icon="Search"
                title="Tidak ada yang cocok"
                description="Coba longgarkan saringan yang dipakai."
            >
                <Button
                    variant="outline"
                    class="min-h-11"
                    @click="resetFilters"
                >
                    Bersihkan saringan
                </Button>
            </EmptyState>

            <section
                v-for="group in groups"
                v-else
                :key="group.date"
                class="space-y-2"
            >
                <div class="flex items-baseline justify-between gap-3 px-1">
                    <h2
                        class="text-muted-foreground text-xs font-semibold tracking-wide uppercase"
                    >
                        {{ dayLabel.format(asLocalDate(group.date)) }}
                    </h2>
                    <MoneyText
                        :value="Math.abs(group.net)"
                        :direction="group.net < 0 ? 'out' : 'in'"
                        signed
                        class="shrink-0 text-xs"
                    />
                </div>

                <ul class="bg-card divide-y rounded-2xl border px-3">
                    <li
                        v-for="item in group.items"
                        :key="item.id"
                        class="flex items-center gap-1"
                    >
                        <Link
                            :href="showRoute.url(item.id)"
                            class="min-w-0 flex-1 rounded-xl"
                        >
                            <TransactionRow
                                :icon="categoryIcon(item.category_icon)"
                                :title="
                                    item.description ||
                                    item.category_name ||
                                    'Tanpa deskripsi'
                                "
                                :meta="`${item.category_name ?? 'Tanpa kategori'} · ${item.account_name}`"
                                :amount="item.amount"
                                :direction="
                                    item.type === 'income' ? 'in' : 'out'
                                "
                            />
                        </Link>
                        <div class="flex shrink-0 items-center">
                            <Button
                                v-if="item.receipt_url"
                                as-child
                                variant="ghost"
                                size="icon"
                                class="size-11"
                                :aria-label="`Lihat struk ${item.description || item.id}`"
                            >
                                <a
                                    :href="item.receipt_url"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <ReceiptText
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                </a>
                            </Button>
                            <Button
                                v-if="item.can_edit"
                                as-child
                                variant="ghost"
                                size="icon"
                                class="size-11"
                                :aria-label="`Ubah transaksi ${item.description || item.id}`"
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
                                :aria-label="`Hapus transaksi ${item.description || item.id}`"
                                @click="deleteTarget = item"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                            </Button>
                        </div>
                    </li>
                </ul>
            </section>
        </template>
    </div>
</template>

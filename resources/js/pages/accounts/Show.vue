<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    Pencil,
    ReceiptText,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AccountCard from '@/components/fluxa/AccountCard.vue';
import AccountHistoryRow from '@/components/fluxa/AccountHistoryRow.vue';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import { Button } from '@/components/ui/button';
import {
    archive as archiveRoute,
    destroy,
    edit as editRoute,
    index as indexRoute,
} from '@/routes/accounts';

const props = defineProps<{
    account: App.Data.AccountData;
    history: App.Data.AccountHistoryData[];
    can: { manage: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kantong', href: indexRoute.url() },
            { title: 'Riwayat' },
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
const asLocalDate = (ymd: string): Date => new Date(`${ymd}T00:00:00`);

const groups = computed(() => {
    const map = new Map<string, App.Data.AccountHistoryData[]>();

    for (const entry of props.history) {
        map.set(entry.date, [...(map.get(entry.date) ?? []), entry]);
    }

    return [...map.entries()]
        .sort((a, b) => b[0].localeCompare(a[0]))
        .map(([date, items]) => ({ date, items }));
});

const archiveForm = useForm({});
const deleteForm = useForm({});
const deleteOpen = ref(false);

function toggleArchive(): void {
    archiveForm.patch(archiveRoute.url(props.account.id));
}

function confirmDelete(): void {
    deleteForm.delete(destroy.url(props.account.id));
}
</script>

<template>
    <Head :title="`Kantong ${account.name}`" />

    <div class="space-y-4 p-4">
        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            title="Hapus kantong"
            :description="`Kantong \u201C${account.name}\u201D akan dihapus permanen, saldo ikut hilang.`"
            confirm-label="Hapus kantong"
            @confirm="confirmDelete"
        />

        <AccountCard
            :name="account.name"
            :type="account.type"
            :balance="account.balance"
            :logo-url="account.logo_url"
            :credit-limit="account.credit_detail?.credit_limit ?? null"
            :archived="account.is_archived"
        >
            <template v-if="can.manage" #actions>
                <Button
                    as-child
                    variant="ghost"
                    size="icon"
                    class="size-11"
                    :aria-label="`Ubah kantong ${account.name}`"
                >
                    <Link :href="editRoute.url(account.id)">
                        <Pencil class="size-4" aria-hidden="true" />
                    </Link>
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-11"
                    :aria-label="`${account.is_archived ? 'Kembalikan' : 'Arsipkan'} kantong ${account.name}`"
                    @click="toggleArchive"
                >
                    <ArchiveRestore
                        v-if="account.is_archived"
                        class="size-4"
                        aria-hidden="true"
                    />
                    <Archive v-else class="size-4" aria-hidden="true" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="text-money-out hover:text-money-out size-11"
                    :aria-label="`Hapus kantong ${account.name}`"
                    @click="deleteOpen = true"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                </Button>
            </template>
        </AccountCard>

        <section class="space-y-2">
            <h2 class="px-1 text-sm font-semibold">
                Riwayat mutasi ({{ history.length }})
            </h2>

            <EmptyState
                v-if="!history.length"
                :icon="ReceiptText"
                title="Belum ada mutasi"
                description="Transaksi dan transfer kantong ini akan tercatat di sini."
            />

            <div v-else class="space-y-4">
                <section
                    v-for="group in groups"
                    :key="group.date"
                    class="space-y-2"
                >
                    <h3
                        class="text-muted-foreground px-1 text-xs font-semibold tracking-wide uppercase"
                    >
                        {{ dayLabel.format(asLocalDate(group.date)) }}
                    </h3>

                    <ul class="bg-card divide-y rounded-2xl border px-3">
                        <li v-for="entry in group.items" :key="entry.key">
                            <AccountHistoryRow :entry="entry" />
                        </li>
                    </ul>
                </section>
            </div>
        </section>
    </div>
</template>

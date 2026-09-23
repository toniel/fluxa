<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    ChevronRight,
    Pencil,
    Plus,
    Trash2,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AccountCard from '@/components/fluxa/AccountCard.vue';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { Button } from '@/components/ui/button';
import {
    archive as archiveRoute,
    create as createRoute,
    destroy,
    edit as editRoute,
    index as indexRoute,
} from '@/routes/accounts';

const props = defineProps<{
    accounts: App.Data.AccountData[];
    can: { create: boolean; manage: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kantong', href: indexRoute.url() }],
    },
});

const active = computed(() => props.accounts.filter((a) => !a.is_archived));
const archived = computed(() => props.accounts.filter((a) => a.is_archived));

const total = computed(() =>
    active.value.reduce((sum, a) => sum + Number.parseFloat(a.balance), 0),
);

// Kantong terarsip disembunyikan secara bawaan: ia jarang dibuka, tapi
// menghapusnya dari halaman akan membuat saldo lama terasa hilang.
const showArchived = ref(false);

const archiveForm = useForm({});
const deleteForm = useForm({});

// Bukan window.confirm: konfirmasi memakai dialog aplikasi sendiri di bawah,
// yang konsisten di HP maupun desktop.
const deleteTarget = ref<App.Data.AccountData | null>(null);

const deleteOpen = computed({
    get: () => deleteTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            deleteTarget.value = null;
        }
    },
});

function toggleArchive(account: App.Data.AccountData): void {
    archiveForm.patch(archiveRoute.url(account.id), { preserveScroll: true });
}

function confirmDelete(): void {
    const account = deleteTarget.value;

    if (account === null) {
        return;
    }

    deleteForm.delete(destroy.url(account.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Kantong" />

    <div class="space-y-4 p-4">
        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            title="Hapus kantong"
            :description="`Kantong \u201C${deleteTarget?.name ?? ''}\u201D akan dihapus permanen, saldo ikut hilang.`"
            confirm-label="Hapus kantong"
            @confirm="confirmDelete"
        />

        <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight">Kantong</h1>
                <p class="text-muted-foreground text-sm">
                    {{ active.length }} kantong aktif · total
                    <MoneyText :value="total" />
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
            v-if="!accounts.length"
            :icon="Wallet"
            title="Belum ada kantong"
            description="Buat kantong pertama supaya transaksi punya tempat masuk dan keluar."
        >
            <Button v-if="can.create" as-child class="min-h-11">
                <Link :href="createRoute.url()">
                    <Plus class="size-4" aria-hidden="true" />
                    Tambah kantong
                </Link>
            </Button>
        </EmptyState>

        <template v-else>
            <div class="space-y-3">
                <AccountCard
                    v-for="account in active"
                    :key="account.id"
                    :name="account.name"
                    :type="account.type"
                    :balance="account.balance"
                    :logo-url="account.logo_url"
                    :credit-limit="account.credit_detail?.credit_limit ?? null"
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
                            :aria-label="`Arsipkan kantong ${account.name}`"
                            @click="toggleArchive(account)"
                        >
                            <Archive class="size-4" aria-hidden="true" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-money-out hover:text-money-out size-11"
                            :aria-label="`Hapus kantong ${account.name}`"
                            @click="deleteTarget = account"
                        >
                            <Trash2 class="size-4" aria-hidden="true" />
                        </Button>
                    </template>
                </AccountCard>
            </div>

            <section v-if="archived.length" class="space-y-3">
                <button
                    type="button"
                    class="focus-visible:ring-ring text-muted-foreground flex min-h-11 w-full items-center gap-1.5 rounded text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                    :aria-expanded="showArchived"
                    @click="showArchived = !showArchived"
                >
                    <ChevronRight
                        class="size-4 transition-transform"
                        :class="showArchived && 'rotate-90'"
                        aria-hidden="true"
                    />
                    Kantong diarsipkan ({{ archived.length }})
                </button>

                <div v-if="showArchived" class="space-y-3">
                    <AccountCard
                        v-for="account in archived"
                        :key="account.id"
                        :name="account.name"
                        :type="account.type"
                        :balance="account.balance"
                        :logo-url="account.logo_url"
                        :credit-limit="
                            account.credit_detail?.credit_limit ?? null
                        "
                        archived
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
                                :aria-label="`Kembalikan kantong ${account.name}`"
                                @click="toggleArchive(account)"
                            >
                                <ArchiveRestore
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="text-money-out hover:text-money-out size-11"
                                :aria-label="`Hapus kantong ${account.name}`"
                                @click="deleteTarget = account"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                            </Button>
                        </template>
                    </AccountCard>
                </div>
            </section>
        </template>
    </div>
</template>

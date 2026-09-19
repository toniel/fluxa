<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronRight, Plus, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import AccountCard from '@/components/fluxa/AccountCard.vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as accountsRoute } from '@/routes/accounts';

type Account = {
    id: number;
    name: string;
    type: string;
    balance: string;
    is_archived: boolean;
    tx_count: number;
};

const props = defineProps<{ state: string; accounts: Account[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Kantong', href: accountsRoute() }] },
});

const active = computed(() => props.accounts.filter((a) => !a.is_archived));
const archived = computed(() => props.accounts.filter((a) => a.is_archived));

const total = computed(() =>
    active.value.reduce((sum, a) => sum + Number.parseFloat(a.balance), 0),
);

// Kantong terarsip disembunyikan secara bawaan: ia jarang dibuka, tapi
// menghapusnya dari halaman akan membuat saldo lama terasa hilang.
const showArchived = ref(false);
</script>

<template>
    <Head title="Kantong" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight">Kantong</h1>
                <p class="text-muted-foreground text-sm">
                    {{ active.length }} kantong aktif · total
                    <MoneyText :value="total" />
                </p>
            </div>
            <Button class="min-h-11 shrink-0" @click="notYet('Tambah kantong')">
                <Plus class="size-4" aria-hidden="true" />
                Tambah
            </Button>
        </header>

        <ErrorState
            v-if="state === 'failed'"
            title="Daftar kantong gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <EmptyState
            v-else-if="!accounts.length"
            :icon="Wallet"
            title="Belum ada kantong"
            description="Buat kantong pertama supaya transaksi punya tempat masuk dan keluar."
        >
            <Button class="min-h-11" @click="notYet('Tambah kantong')">
                <Plus class="size-4" aria-hidden="true" />
                Tambah kantong
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
                    :tx-count="account.tx_count"
                />
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
                        :tx-count="account.tx_count"
                        archived
                    />
                </div>
            </section>
        </template>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Archive, Plus, Wallet } from '@lucide/vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import PageHeader from '@/components/fluxa/PageHeader.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as accountsRoute } from '@/routes/accounts';

type Account = {
    id: number;
    name: string;
    type: string;
    balance: string;
    is_archived: boolean;
};

defineProps<{ state: string; accounts: Account[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Kantong', href: accountsRoute() }] },
});

const typeLabel: Record<string, string> = {
    cash: 'Tunai',
    bank: 'Bank',
    ewallet: 'E-wallet',
    other: 'Lainnya',
};
</script>

<template>
    <Head title="Kantong" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <PageHeader
            title="Kantong"
            description="Tempat uang disimpan: kas, rekening, atau e-wallet."
        >
            <template #action>
                <Button class="min-h-11" @click="notYet('Tambah kantong')">
                    <Plus class="size-4" aria-hidden="true" />
                    Tambah
                </Button>
            </template>
        </PageHeader>

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

        <ul v-else class="space-y-2">
            <li
                v-for="account in accounts"
                :key="account.id"
                class="bg-card flex items-center justify-between gap-3 rounded-lg border p-3"
                :class="account.is_archived && 'opacity-60'"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        class="bg-muted text-muted-foreground flex size-10 shrink-0 items-center justify-center rounded-md"
                        aria-hidden="true"
                    >
                        <Archive v-if="account.is_archived" class="size-5" />
                        <Wallet v-else class="size-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="flex items-center gap-2">
                            <span class="truncate font-medium">{{
                                account.name
                            }}</span>
                            <Badge
                                v-if="account.is_archived"
                                variant="secondary"
                                >Diarsipkan</Badge
                            >
                        </span>
                        <span class="text-muted-foreground block text-xs">{{
                            typeLabel[account.type] ?? account.type
                        }}</span>
                    </span>
                </div>
                <MoneyText
                    :value="account.balance"
                    class="shrink-0 font-semibold"
                />
            </li>
        </ul>
    </div>
</template>

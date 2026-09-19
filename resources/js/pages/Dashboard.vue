<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Wallet } from '@lucide/vue';
import CashFlowChart from '@/components/fluxa/CashFlowChart.vue';
import CategoryDonut from '@/components/fluxa/CategoryDonut.vue';
import AccountTile from '@/components/fluxa/AccountTile.vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import HeroBalance from '@/components/fluxa/HeroBalance.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import TransactionRow from '@/components/fluxa/TransactionRow.vue';
import { Button } from '@/components/ui/button';
import { categoryIcon } from '@/lib/categoryIcons';
import { notYet } from '@/lib/notYet';
import { index as accountsRoute } from '@/routes/accounts';
import { dashboard } from '@/routes';
import { index as transactionsRoute } from '@/routes/transactions';

type Slice = { category: string; icon: string; total: string };
type Bucket = { label: string; income: string; expense: string };
type Account = {
    id: number;
    name: string;
    type: string;
    balance: string;
    is_archived: boolean;
};
type Recent = {
    id: number;
    type: 'income' | 'expense';
    amount: string;
    description: string;
    date: string;
    account: string;
    category: string;
    icon: string;
};

defineProps<{
    state: string;
    summary: {
        total_balance: string;
        income_this_month: string;
        expense_this_month: string;
        period_label: string;
        greeting_name: string;
        cashflow: Bucket[];
    };
    breakdown: Slice[];
    accounts: Account[];
    recent: Recent[];
    tenant: { name: string };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Beranda', href: dashboard() }] },
});

const dateLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
});
</script>

<template>
    <Head title="Beranda" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <ErrorState
            v-if="state === 'failed'"
            title="Ringkasan gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <template v-else>
            <header>
                <p class="text-muted-foreground text-sm">
                    Halo, {{ summary.greeting_name }}
                </p>
                <h1 class="text-xl font-bold tracking-tight">
                    {{ tenant.name }}
                </h1>
            </header>

            <HeroBalance
                :total="summary.total_balance"
                :income="summary.income_this_month"
                :expense="summary.expense_this_month"
                :period="summary.period_label"
            />

            <section class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold">Kantong</h2>
                    <Link
                        :href="accountsRoute()"
                        class="text-money-in focus-visible:ring-ring min-h-11 rounded px-1 py-2 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                        >Lihat semua</Link
                    >
                </div>

                <div v-if="accounts.length" class="grid grid-cols-2 gap-2">
                    <AccountTile
                        v-for="account in accounts.filter(
                            (a) => !a.is_archived,
                        )"
                        :key="account.id"
                        :name="account.name"
                        :type="account.type"
                        :balance="account.balance"
                    />
                </div>
                <EmptyState
                    v-else
                    :icon="Wallet"
                    title="Belum ada kantong"
                    description="Kantong adalah tempat uang disimpan: kas, rekening bank, atau e-wallet."
                >
                    <Button class="min-h-11" @click="notYet('Tambah kantong')">
                        <Plus class="size-4" aria-hidden="true" />
                        Tambah kantong
                    </Button>
                </EmptyState>
            </section>

            <section class="bg-card space-y-4 rounded-2xl border p-4">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="font-semibold">Arus kas</h2>
                    <span class="text-muted-foreground text-xs">{{
                        summary.period_label
                    }}</span>
                </div>
                <CashFlowChart :buckets="summary.cashflow" />
            </section>

            <section class="bg-card space-y-4 rounded-2xl border p-4">
                <h2 class="font-semibold">Pengeluaran per kategori</h2>
                <CategoryDonut v-if="breakdown.length" :slices="breakdown" />
                <EmptyState
                    v-else
                    title="Belum ada pengeluaran bulan ini"
                    description="Begitu ada transaksi keluar, rinciannya muncul di sini."
                />
            </section>

            <section class="bg-card rounded-2xl border p-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold">Transaksi terbaru</h2>
                    <Link
                        :href="transactionsRoute()"
                        class="text-money-in focus-visible:ring-ring min-h-11 rounded px-1 py-2 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                        >Semua</Link
                    >
                </div>

                <ul v-if="recent.length" class="divide-y">
                    <li v-for="item in recent" :key="item.id">
                        <TransactionRow
                            :icon="categoryIcon(item.icon)"
                            :title="item.description"
                            :meta="`${item.category} · ${item.account} · ${dateLabel.format(new Date(item.date))}`"
                            :amount="item.amount"
                            :direction="item.type === 'income' ? 'in' : 'out'"
                        />
                    </li>
                </ul>
                <EmptyState
                    v-else
                    title="Belum ada transaksi"
                    description="Catatan pemasukan dan pengeluaran akan tampil di sini."
                />
            </section>
        </template>
    </div>
</template>

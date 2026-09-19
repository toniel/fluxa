<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, Plus, Wallet } from '@lucide/vue';
import CategoryBars from '@/components/fluxa/CategoryBars.vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import ListCard from '@/components/fluxa/ListCard.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import StatTile from '@/components/fluxa/StatTile.vue';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as accountsRoute } from '@/routes/accounts';
import { dashboard } from '@/routes';
import { index as transactionsRoute } from '@/routes/transactions';

type Slice = { category: string; total: string };
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
};

defineProps<{
    state: string;
    summary: {
        total_balance: string;
        income_this_month: string;
        expense_this_month: string;
        period_label: string;
    };
    breakdown: Slice[];
    accounts: Account[];
    recent: Recent[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Beranda', href: dashboard() }] },
});

const typeLabel: Record<string, string> = {
    cash: 'Tunai',
    bank: 'Bank',
    ewallet: 'E-wallet',
    other: 'Lainnya',
};
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
            <!--
                Saldo total adalah satu-satunya angka besar di layar: itu yang
                dicari orang saat membuka aplikasi, sisanya mendukung.
            -->
            <section class="bg-brand text-brand-foreground rounded-xl p-4">
                <p class="text-xs opacity-80">Saldo semua kantong</p>
                <p class="font-numeric mt-1 text-3xl font-bold tabular-nums">
                    <MoneyText :value="summary.total_balance" />
                </p>
                <p class="mt-3 text-xs opacity-80">
                    {{ summary.period_label }}
                </p>
            </section>

            <div class="grid grid-cols-2 gap-3">
                <StatTile
                    label="Pemasukan"
                    :value="summary.income_this_month"
                    direction="in"
                    :icon="ArrowDownLeft"
                />
                <StatTile
                    label="Pengeluaran"
                    :value="summary.expense_this_month"
                    direction="out"
                    :icon="ArrowUpRight"
                />
            </div>

            <ListCard title="Pengeluaran per kategori">
                <CategoryBars v-if="breakdown.length" :slices="breakdown" />
                <EmptyState
                    v-else
                    title="Belum ada pengeluaran bulan ini"
                    description="Begitu ada transaksi keluar, rinciannya muncul di sini."
                />
            </ListCard>

            <ListCard title="Kantong">
                <template #action>
                    <Link
                        :href="accountsRoute()"
                        class="focus-visible:ring-ring rounded text-sm underline underline-offset-4 focus-visible:ring-2 focus-visible:outline-none"
                        >Semua</Link
                    >
                </template>

                <ul v-if="accounts.length" class="divide-y">
                    <li
                        v-for="account in accounts.filter(
                            (a) => !a.is_archived,
                        )"
                        :key="account.id"
                        class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0"
                    >
                        <span class="min-w-0">
                            <span class="block truncate text-sm">{{
                                account.name
                            }}</span>
                            <span class="text-muted-foreground text-xs">{{
                                typeLabel[account.type] ?? account.type
                            }}</span>
                        </span>
                        <MoneyText
                            :value="account.balance"
                            class="shrink-0 text-sm font-medium"
                        />
                    </li>
                </ul>
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
            </ListCard>

            <ListCard title="Transaksi terakhir">
                <template #action>
                    <Link
                        :href="transactionsRoute()"
                        class="focus-visible:ring-ring rounded text-sm underline underline-offset-4 focus-visible:ring-2 focus-visible:outline-none"
                        >Semua</Link
                    >
                </template>

                <ul v-if="recent.length" class="divide-y">
                    <li
                        v-for="item in recent"
                        :key="item.id"
                        class="flex items-start justify-between gap-3 py-2.5 first:pt-0 last:pb-0"
                    >
                        <span class="min-w-0">
                            <span class="block truncate text-sm">{{
                                item.description
                            }}</span>
                            <span class="text-muted-foreground text-xs">
                                {{ item.category }} · {{ item.account }}
                            </span>
                        </span>
                        <MoneyText
                            :value="item.amount"
                            :direction="item.type === 'income' ? 'in' : 'out'"
                            signed
                            class="shrink-0 text-sm font-medium"
                        />
                    </li>
                </ul>
                <EmptyState
                    v-else
                    title="Belum ada transaksi"
                    description="Catatan pemasukan dan pengeluaran akan tampil di sini."
                />
            </ListCard>
        </template>
    </div>
</template>

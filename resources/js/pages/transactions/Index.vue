<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus, ReceiptText } from '@lucide/vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import PageHeader from '@/components/fluxa/PageHeader.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as transactionsRoute } from '@/routes/transactions';

type Transaction = {
    id: number;
    type: 'income' | 'expense';
    amount: string;
    description: string;
    date: string;
    account: string;
    category: string;
    creator: string;
    can_edit: boolean;
};

const props = defineProps<{ state: string; transactions: Transaction[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Transaksi', href: transactionsRoute() }],
    },
});

const dateLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
});

function groupedByDate(): { date: string; items: Transaction[] }[] {
    const groups = new Map<string, Transaction[]>();

    for (const item of props.transactions) {
        const bucket = groups.get(item.date) ?? [];
        bucket.push(item);
        groups.set(item.date, bucket);
    }

    return [...groups.entries()].map(([date, items]) => ({ date, items }));
}
</script>

<template>
    <Head title="Transaksi" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <PageHeader
            title="Transaksi"
            description="Pemasukan dan pengeluaran seluruh anggota."
        >
            <template #action>
                <Button class="min-h-11" @click="notYet('Catat transaksi')">
                    <Plus class="size-4" aria-hidden="true" />
                    Catat
                </Button>
            </template>
        </PageHeader>

        <ErrorState
            v-if="state === 'failed'"
            title="Daftar transaksi gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <EmptyState
            v-else-if="!transactions.length"
            :icon="ReceiptText"
            title="Belum ada transaksi"
            description="Catat pemasukan atau pengeluaran pertama untuk mulai melihat ringkasannya."
        >
            <Button class="min-h-11" @click="notYet('Catat transaksi')">
                <Plus class="size-4" aria-hidden="true" />
                Catat transaksi
            </Button>
        </EmptyState>

        <!--
            Dikelompokkan per tanggal, bukan tabel dengan kolom tanggal berulang:
            di HP tanggal sebagai judul kelompok memakan jauh lebih sedikit lebar
            daripada satu kolom tersendiri.
        -->
        <section
            v-for="group in groupedByDate()"
            v-else
            :key="group.date"
            class="space-y-2"
        >
            <h2 class="text-muted-foreground px-1 text-xs font-medium">
                {{ dateLabel.format(new Date(group.date)) }}
            </h2>
            <ul class="bg-card divide-y rounded-lg border">
                <li
                    v-for="item in group.items"
                    :key="item.id"
                    class="flex items-start justify-between gap-3 p-3"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">
                            {{ item.description }}
                        </p>
                        <p class="text-muted-foreground mt-0.5 text-xs">
                            {{ item.category }} · {{ item.account }}
                        </p>
                        <p class="text-muted-foreground mt-0.5 text-xs">
                            oleh {{ item.creator }}
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <MoneyText
                            :value="item.amount"
                            :direction="item.type === 'income' ? 'in' : 'out'"
                            signed
                            class="font-semibold"
                        />
                        <Button
                            v-if="item.can_edit"
                            variant="ghost"
                            size="sm"
                            class="min-h-11 px-2 text-xs md:min-h-9"
                            @click="notYet('Ubah transaksi')"
                        >
                            Ubah
                        </Button>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowLeftRight, ArrowRight, Plus } from '@lucide/vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import PageHeader from '@/components/fluxa/PageHeader.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as transfersRoute } from '@/routes/transfers';

type Transfer = {
    id: number;
    amount: string;
    description: string;
    date: string;
    from: string;
    to: string;
    creator: string;
};

defineProps<{ state: string; transfers: Transfer[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Transfer', href: transfersRoute() }] },
});

const dateLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});
</script>

<template>
    <Head title="Transfer" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <PageHeader
            title="Transfer"
            description="Perpindahan uang antar kantong. Tidak dihitung sebagai pemasukan atau pengeluaran."
        >
            <template #action>
                <Button class="min-h-11" @click="notYet('Buat transfer')">
                    <Plus class="size-4" aria-hidden="true" />
                    Transfer
                </Button>
            </template>
        </PageHeader>

        <ErrorState
            v-if="state === 'failed'"
            title="Daftar transfer gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <EmptyState
            v-else-if="!transfers.length"
            :icon="ArrowLeftRight"
            title="Belum ada transfer"
            description="Pindahkan uang antar kantong, misalnya dari rekening bank ke kas harian."
        >
            <Button class="min-h-11" @click="notYet('Buat transfer')">
                <Plus class="size-4" aria-hidden="true" />
                Buat transfer
            </Button>
        </EmptyState>

        <ul v-else class="space-y-2">
            <li
                v-for="transfer in transfers"
                :key="transfer.id"
                class="bg-card space-y-2 rounded-lg border p-3"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="min-w-0 truncate font-medium">
                        {{ transfer.description }}
                    </p>
                    <MoneyText
                        :value="transfer.amount"
                        class="shrink-0 font-semibold"
                    />
                </div>
                <!--
                    Arah transfer ditulis sebagai satu baris "dari -> ke" supaya
                    tetap terbaca di layar sempit tanpa dipecah jadi dua kolom.
                -->
                <p
                    class="text-muted-foreground flex flex-wrap items-center gap-1.5 text-xs"
                >
                    <span>{{ transfer.from }}</span>
                    <ArrowRight class="size-3.5 shrink-0" aria-hidden="true" />
                    <span>{{ transfer.to }}</span>
                </p>
                <p class="text-muted-foreground text-xs">
                    {{ dateLabel.format(new Date(transfer.date)) }} · oleh
                    {{ transfer.creator }}
                </p>
            </li>
        </ul>
    </div>
</template>

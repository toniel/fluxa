<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Check, TriangleAlert } from '@lucide/vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import PageHeader from '@/components/fluxa/PageHeader.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as billingRoute } from '@/routes/billing';

type Billing = {
    plan: { name: string; slug: string; price: string; billing_period: string };
    status: string;
    period_end: string | null;
    usage: {
        members: number;
        max_members: number;
        accounts: number;
        max_accounts: number;
    };
    upgrade: { name: string; price: string; billing_period: string };
};

defineProps<{ state: string; billing: Billing }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Langganan', href: billingRoute() }] },
});

const periodLabel: Record<string, string> = {
    monthly: 'bulan',
    yearly: 'tahun',
};
</script>

<template>
    <Head title="Langganan" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <PageHeader
            title="Langganan"
            description="Paket yang sedang dipakai tenant ini."
        />

        <ErrorState
            v-if="state === 'failed'"
            title="Data langganan gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <template v-else>
            <section class="bg-card space-y-3 rounded-lg border p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-muted-foreground text-xs">Paket aktif</p>
                        <p class="text-lg font-semibold">
                            {{ billing.plan.name }}
                        </p>
                    </div>
                    <Badge variant="secondary">Aktif</Badge>
                </div>
                <p class="font-numeric text-2xl font-bold tabular-nums">
                    <MoneyText :value="billing.plan.price" />
                    <span class="text-muted-foreground text-sm font-normal"
                        >/{{
                            periodLabel[billing.plan.billing_period] ??
                            billing.plan.billing_period
                        }}</span
                    >
                </p>
            </section>

            <!--
                Pemakaian ditampilkan sebagai angka berbanding batas, bukan
                progress bar dekoratif: yang perlu diketahui adalah apakah batas
                sudah terlewat, dan berapa selisihnya.
            -->
            <section class="bg-card space-y-3 rounded-lg border p-4">
                <h2 class="text-sm font-semibold">Pemakaian</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt>Anggota</dt>
                        <dd class="font-numeric tabular-nums">
                            {{ billing.usage.members }} /
                            {{ billing.usage.max_members }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt>Kantong</dt>
                        <dd
                            class="font-numeric flex items-center gap-1.5 tabular-nums"
                            :class="
                                billing.usage.accounts >
                                    billing.usage.max_accounts &&
                                'text-money-out font-medium'
                            "
                        >
                            <TriangleAlert
                                v-if="
                                    billing.usage.accounts >
                                    billing.usage.max_accounts
                                "
                                class="size-4"
                                aria-hidden="true"
                            />
                            {{ billing.usage.accounts }} /
                            {{ billing.usage.max_accounts }}
                        </dd>
                    </div>
                </dl>
                <p
                    v-if="billing.usage.accounts > billing.usage.max_accounts"
                    class="text-muted-foreground text-xs"
                >
                    Jumlah kantong melebihi batas paket Free. Kantong lama tetap
                    bisa dibaca, tapi tidak bisa menambah yang baru.
                </p>
            </section>

            <section class="border-brand/30 space-y-3 rounded-lg border p-4">
                <div>
                    <p class="text-muted-foreground text-xs">Naik ke</p>
                    <p class="text-lg font-semibold">
                        {{ billing.upgrade.name }}
                    </p>
                </div>
                <p class="font-numeric text-2xl font-bold tabular-nums">
                    <MoneyText :value="billing.upgrade.price" />
                    <span class="text-muted-foreground text-sm font-normal"
                        >/{{
                            periodLabel[billing.upgrade.billing_period] ??
                            billing.upgrade.billing_period
                        }}</span
                    >
                </p>
                <ul class="space-y-1.5 text-sm">
                    <li
                        v-for="feature in [
                            'Anggota tanpa batas',
                            'Kantong dan kategori tanpa batas',
                            'Subdomain pilihan sendiri',
                            'Ekspor laporan',
                        ]"
                        :key="feature"
                        class="flex items-start gap-2"
                    >
                        <Check
                            class="text-money-in mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        {{ feature }}
                    </li>
                </ul>
                <Button
                    class="min-h-11 w-full"
                    @click="notYet('Pembayaran langganan')"
                >
                    Naik ke paket Pro
                </Button>
                <p class="text-muted-foreground text-xs">
                    Pembayaran belum tersambung. Integrasinya dikerjakan di
                    tahap terakhir.
                </p>
            </section>
        </template>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Check, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as billingRoute } from '@/routes/billing';

type Row = {
    feature: string;
    free: string | boolean;
    pro: string | boolean;
};

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
    comparison: Row[];
};

defineProps<{ state: string; billing: Billing }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Langganan', href: billingRoute() }] },
});

const periodLabel: Record<string, string> = {
    monthly: 'bulan',
    yearly: 'tahun',
};

// "Nilai" dari sudut pandang tabel: teks apa adanya, atau centang/strip untuk
// baris yang murni ada/tidaknya sebuah fitur.
function isBoolean(value: string | boolean): value is boolean {
    return typeof value === 'boolean';
}
</script>

<template>
    <Head title="Langganan" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <header>
            <h1 class="text-xl font-bold tracking-tight">Langganan</h1>
            <p class="text-muted-foreground text-sm">
                Paket yang sedang dipakai tenant ini
            </p>
        </header>

        <ErrorState
            v-if="state === 'failed'"
            title="Data langganan gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <template v-else>
            <section class="bg-brand text-brand-foreground rounded-2xl p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs opacity-80">Plan aktif</p>
                        <p class="text-lg font-bold">
                            {{ billing.plan.name }}
                        </p>
                    </div>
                    <Badge class="bg-white/15 text-white">Aktif</Badge>
                </div>
                <p class="font-numeric mt-2 text-2xl font-bold tabular-nums">
                    <MoneyText :value="billing.plan.price" />
                    <span class="text-sm font-normal opacity-80"
                        >/{{
                            periodLabel[billing.plan.billing_period] ??
                            billing.plan.billing_period
                        }}</span
                    >
                </p>
            </section>

            <section class="bg-card space-y-3 rounded-2xl border p-4">
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

            <!--
                Tabel dua kolom, bukan <table> HTML: pada 390px sebuah <table>
                sungguhan gampang meluber, sedangkan grid tiga kolom dengan
                lebar label yang fleksibel selalu muat karena nilainya pendek
                ("3", "Tanpa batas", centang, strip).
            -->
            <section class="bg-card overflow-hidden rounded-2xl border">
                <div
                    class="text-muted-foreground grid grid-cols-[1fr_5rem_5rem] gap-2 border-b px-4 py-2.5 text-xs font-semibold tracking-wide uppercase"
                >
                    <span>Fitur</span>
                    <span class="text-center">Free</span>
                    <span class="text-money-in text-center">Pro</span>
                </div>

                <div class="divide-y">
                    <div
                        v-for="row in billing.comparison"
                        :key="row.feature"
                        class="grid grid-cols-[1fr_5rem_5rem] items-center gap-2 px-4 py-3 text-sm"
                    >
                        <span>{{ row.feature }}</span>

                        <span class="flex justify-center">
                            <Check
                                v-if="isBoolean(row.free) && row.free"
                                class="text-money-in size-4"
                                aria-hidden="true"
                            />
                            <span
                                v-else-if="isBoolean(row.free)"
                                class="text-muted-foreground"
                                aria-hidden="true"
                                >&mdash;</span
                            >
                            <span v-else class="text-center">{{
                                row.free
                            }}</span>
                        </span>

                        <span class="flex justify-center font-medium">
                            <Check
                                v-if="isBoolean(row.pro) && row.pro"
                                class="text-money-in size-4"
                                aria-hidden="true"
                            />
                            <span
                                v-else-if="isBoolean(row.pro)"
                                class="text-muted-foreground"
                                aria-hidden="true"
                                >&mdash;</span
                            >
                            <span v-else class="text-center">{{
                                row.pro
                            }}</span>
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-[1fr_5rem_5rem] items-center gap-2 px-4 py-3 text-sm font-semibold"
                    >
                        <span>Harga</span>
                        <span class="text-center">
                            <MoneyText :value="billing.plan.price" compact />
                        </span>
                        <span class="text-center">
                            <MoneyText :value="billing.upgrade.price" compact />
                        </span>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <Button
                    class="min-h-11 w-full"
                    @click="notYet('Pembayaran langganan')"
                >
                    Naik ke paket Pro
                </Button>
                <p class="text-muted-foreground text-center text-xs">
                    Hanya Owner yang bisa mengubah langganan. Pembayaran
                    diproses lewat Xendit (simulasi, belum tersambung).
                </p>
            </section>
        </template>
    </div>
</template>

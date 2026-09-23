<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Check, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as billingRoute } from '@/routes/billing';
import { toggle as toggleRoute } from '@/routes/billing';

type Usage = {
    members: number;
    max_members: number | null;
    accounts: number;
    max_accounts: number | null;
};

type ComparisonRow = {
    feature: string;
    free: string | boolean;
    pro: string | boolean;
};

const props = defineProps<{
    subscription: App.Data.SubscriptionData;
    upgrade: {
        name: string;
        price: string;
        billing_period: string;
    } | null;
    usage: Usage;
    comparison: ComparisonRow[];
    can: { manage: boolean };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Langganan', href: billingRoute.url() }] },
});

const statusLabel: Record<string, string> = {
    active: 'Aktif',
    past_due: 'Menunggak',
    cancelled: 'Dibatalkan',
    expired: 'Kedaluwarsa',
};

const limitText = (value: number | null): string =>
    value === null ? 'Tanpa batas' : String(value);

const overAccounts = computed(
    () =>
        props.usage.max_accounts !== null &&
        props.usage.accounts > props.usage.max_accounts,
);

// "Nilai" dari sudut pandang tabel: teks apa adanya, atau centang/strip untuk
// baris yang murni ada/tidaknya sebuah fitur.
function isBoolean(value: string | boolean): value is boolean {
    return typeof value === 'boolean';
}

const toggleForm = useForm({});

function toggle(): void {
    toggleForm.post(toggleRoute.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Langganan" />

    <div class="space-y-4 p-4">
        <header>
            <h1 class="text-xl font-bold tracking-tight">Langganan</h1>
            <p class="text-muted-foreground text-sm">
                Paket yang sedang dipakai tenant ini
            </p>
        </header>

        <section class="bg-brand text-brand-foreground rounded-2xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs opacity-80">Plan aktif</p>
                    <p class="text-lg font-bold">
                        {{ subscription.plan.name }}
                    </p>
                </div>
                <Badge class="bg-white/15 text-white">{{
                    statusLabel[subscription.status] ?? subscription.status
                }}</Badge>
            </div>
            <p class="font-numeric mt-2 text-2xl font-bold tabular-nums">
                <MoneyText :value="subscription.plan.price" />
                <span class="text-sm font-normal opacity-80"
                    >/{{ subscription.plan.billing_period }}</span
                >
            </p>
        </section>

        <section class="bg-card space-y-3 rounded-2xl border p-4">
            <h2 class="text-sm font-semibold">Pemakaian</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt>Anggota</dt>
                    <dd class="font-numeric tabular-nums">
                        {{ usage.members }} / {{ limitText(usage.max_members) }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt>Kantong</dt>
                    <dd
                        class="font-numeric flex items-center gap-1.5 tabular-nums"
                        :class="overAccounts && 'text-money-out font-medium'"
                    >
                        <TriangleAlert
                            v-if="overAccounts"
                            class="size-4"
                            aria-hidden="true"
                        />
                        {{ usage.accounts }} /
                        {{ limitText(usage.max_accounts) }}
                    </dd>
                </div>
            </dl>
            <p v-if="overAccounts" class="text-muted-foreground text-xs">
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
                <span class="text-center">{{ subscription.plan.name }}</span>
                <span class="text-money-in text-center">{{
                    upgrade?.name ?? 'Pro'
                }}</span>
            </div>

            <div class="divide-y">
                <div
                    v-for="row in comparison"
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
                        <span v-else class="text-center">{{ row.free }}</span>
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
                        <span v-else class="text-center">{{ row.pro }}</span>
                    </span>
                </div>

                <div
                    class="grid grid-cols-[1fr_5rem_5rem] items-center gap-2 px-4 py-3 text-sm font-semibold"
                >
                    <span>Harga</span>
                    <span class="text-center">
                        <MoneyText :value="subscription.plan.price" compact />
                    </span>
                    <span class="text-center">
                        <MoneyText
                            v-if="upgrade"
                            :value="upgrade.price"
                            compact
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </span>
                </div>
            </div>
        </section>

        <section v-if="can.manage && upgrade" class="space-y-3">
            <Button
                class="min-h-11 w-full"
                :disabled="toggleForm.processing"
                @click="toggle"
            >
                Naik ke paket {{ upgrade.name }}
            </Button>
            <p class="text-muted-foreground text-center text-xs">
                Simulasi: langsung aktif tanpa pembayaran (Xendit belum
                tersambung).
            </p>
        </section>

        <section v-else-if="can.manage" class="space-y-3">
            <Button
                variant="outline"
                class="min-h-11 w-full"
                :disabled="toggleForm.processing"
                @click="toggle"
            >
                Turun ke paket Free
            </Button>
            <p class="text-muted-foreground text-center text-xs">
                Simulasi: langsung berlaku (Xendit belum tersambung).
            </p>
        </section>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowLeftRight, ArrowRight, Send, TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { notYet } from '@/lib/notYet';
import { index as transfersRoute } from '@/routes/transfers';

type Account = {
    id: number;
    name: string;
    balance: string;
    is_archived: boolean;
};

type Transfer = {
    id: number;
    amount: string;
    description: string;
    date: string;
    from: string;
    to: string;
    creator: string;
};

const props = defineProps<{
    state: string;
    transfers: Transfer[];
    accounts: Account[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Transfer', href: transfersRoute() }] },
});

const today = new Date().toISOString().slice(0, 10);

// Kantong terarsip tidak boleh jadi sumber maupun tujuan transfer baru.
const options = computed(() => props.accounts.filter((a) => !a.is_archived));

const fromId = ref<number | null>(options.value[0]?.id ?? null);
const toId = ref<number | null>(options.value[1]?.id ?? null);
const amount = ref('');
const date = ref(today);
const note = ref('');

const find = (id: number | null) =>
    options.value.find((a) => a.id === id) ?? null;

const from = computed(() => find(fromId.value));
const to = computed(() => find(toId.value));

const value = computed(
    () => Number.parseFloat(amount.value.replace(/\./g, '')) || 0,
);

const sameAccount = computed(
    () => fromId.value !== null && fromId.value === toId.value,
);

const notEnough = computed(
    () =>
        from.value !== null &&
        value.value > Number.parseFloat(from.value.balance),
);

const canSubmit = computed(
    () => value.value > 0 && !sameAccount.value && from.value && to.value,
);

/**
 * Saldo kedua kantong setelah transfer, dihitung langsung saat mengetik.
 * Ini yang mencegah transfer membuat kantong sumber minus tanpa disadari.
 */
const preview = computed(() => {
    if (!from.value || !to.value || sameAccount.value) {
        return null;
    }

    return {
        from: Number.parseFloat(from.value.balance) - value.value,
        to: Number.parseFloat(to.value.balance) + value.value,
    };
});

function swap(): void {
    [fromId.value, toId.value] = [toId.value, fromId.value];
}

function onAmountInput(event: Event): void {
    const digits = (event.target as HTMLInputElement).value.replace(/\D/g, '');
    amount.value = digits ? Number(digits).toLocaleString('id-ID') : '';
}

function setAmount(nominal: number): void {
    amount.value = nominal.toLocaleString('id-ID');
}

function submit(): void {
    notYet('Transfer');
}

const quick = [50000, 100000, 500000, 1000000];

const dateLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const selectClass =
    'border-input bg-card focus-visible:ring-ring min-h-11 w-full rounded-xl border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none';
</script>

<template>
    <Head title="Transfer" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <header>
            <h1 class="text-xl font-bold tracking-tight">
                Transfer antar kantong
            </h1>
            <p class="text-muted-foreground text-sm">
                Memindahkan uang, bukan menambah atau mengurangi. Transfer tidak
                masuk hitungan pemasukan maupun pengeluaran.
            </p>
        </header>

        <ErrorState
            v-if="state === 'failed'"
            title="Halaman transfer gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <template v-else>
            <form
                class="bg-card space-y-4 rounded-2xl border p-4"
                @submit.prevent="submit"
            >
                <div class="flex items-end gap-2">
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <Label for="tf-from" class="text-xs font-semibold"
                            >Dari</Label
                        >
                        <select
                            id="tf-from"
                            v-model="fromId"
                            :class="selectClass"
                        >
                            <option
                                v-for="account in options"
                                :key="account.id"
                                :value="account.id"
                            >
                                {{ account.name }}
                            </option>
                        </select>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        class="size-11 shrink-0 p-0"
                        aria-label="Tukar kantong asal dan tujuan"
                        @click="swap"
                    >
                        <ArrowLeftRight class="size-4" aria-hidden="true" />
                    </Button>

                    <div class="min-w-0 flex-1 space-y-1.5">
                        <Label for="tf-to" class="text-xs font-semibold"
                            >Ke</Label
                        >
                        <select id="tf-to" v-model="toId" :class="selectClass">
                            <option
                                v-for="account in options"
                                :key="account.id"
                                :value="account.id"
                            >
                                {{ account.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <p
                    v-if="sameAccount"
                    class="text-money-out flex items-center gap-1.5 text-xs"
                >
                    <TriangleAlert
                        class="size-3.5 shrink-0"
                        aria-hidden="true"
                    />
                    Pilih dua kantong yang berbeda.
                </p>

                <!--
                    Saldo sesudah transfer ditampilkan sambil mengetik, bukan
                    setelah menekan simpan: kantong yang akan jadi minus harus
                    terlihat sebelum keputusannya diambil.
                -->
                <div
                    v-else-if="preview"
                    class="bg-muted space-y-1 rounded-xl p-3 text-xs"
                >
                    <p class="text-muted-foreground">Saldo setelah transfer</p>
                    <p class="flex flex-wrap items-center gap-x-1.5">
                        <span class="font-medium">{{ from?.name }}</span>
                        <MoneyText
                            :value="preview.from"
                            :direction="preview.from < 0 ? 'out' : 'neutral'"
                        />
                        <ArrowRight
                            class="text-muted-foreground size-3"
                            aria-hidden="true"
                        />
                        <span class="font-medium">{{ to?.name }}</span>
                        <MoneyText :value="preview.to" direction="in" />
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="tf-amount" class="text-xs font-semibold"
                        >Nominal</Label
                    >
                    <div class="relative">
                        <span
                            class="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-sm font-semibold"
                            aria-hidden="true"
                            >Rp</span
                        >
                        <Input
                            id="tf-amount"
                            :model-value="amount"
                            inputmode="numeric"
                            required
                            class="font-numeric min-h-11 rounded-xl pl-10 text-lg font-bold tabular-nums"
                            placeholder="0"
                            @input="onAmountInput"
                        />
                    </div>

                    <p
                        v-if="notEnough"
                        class="text-money-out flex items-center gap-1.5 text-xs"
                    >
                        <TriangleAlert
                            class="size-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        Melebihi saldo {{ from?.name }}. Kantong itu akan minus.
                    </p>

                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <button
                            v-for="nominal in quick"
                            :key="nominal"
                            type="button"
                            class="bg-muted focus-visible:ring-ring min-h-11 rounded-full px-3 text-xs font-medium focus-visible:ring-2 focus-visible:outline-none"
                            @click="setAmount(nominal)"
                        >
                            <MoneyText :value="nominal" />
                        </button>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="tf-date" class="text-xs font-semibold"
                        >Tanggal</Label
                    >
                    <Input
                        id="tf-date"
                        v-model="date"
                        type="date"
                        required
                        class="min-h-11 rounded-xl"
                    />
                </div>

                <div class="space-y-1.5">
                    <Label for="tf-note" class="text-xs font-semibold"
                        >Deskripsi (opsional)</Label
                    >
                    <Input
                        id="tf-note"
                        v-model="note"
                        class="min-h-11 rounded-xl"
                        placeholder="cth. Isi ulang kas harian"
                    />
                </div>

                <Button
                    type="submit"
                    class="min-h-11 w-full"
                    :disabled="!canSubmit"
                >
                    <Send class="size-4" aria-hidden="true" />
                    Transfer sekarang
                </Button>
            </form>

            <section class="bg-card rounded-2xl border p-4">
                <h2 class="font-semibold">Transfer terakhir</h2>

                <ul v-if="transfers.length" class="mt-2 divide-y">
                    <li
                        v-for="transfer in transfers"
                        :key="transfer.id"
                        class="flex items-center gap-3 py-3"
                    >
                        <span
                            class="bg-accent text-brand flex size-9 shrink-0 items-center justify-center rounded-full"
                            aria-hidden="true"
                        >
                            <ArrowLeftRight class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ transfer.from }} → {{ transfer.to }}
                            </p>
                            <p class="text-muted-foreground truncate text-xs">
                                {{ dateLabel.format(new Date(transfer.date)) }}
                                ·
                                {{ transfer.description }}
                            </p>
                        </div>
                        <MoneyText
                            :value="transfer.amount"
                            class="shrink-0 text-sm font-semibold"
                        />
                    </li>
                </ul>

                <EmptyState
                    v-else
                    :icon="ArrowLeftRight"
                    title="Belum ada transfer"
                    description="Perpindahan uang antar kantong akan tercatat di sini."
                    class="mt-2"
                />
            </section>
        </template>
    </div>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowDownUp } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import CurrencyInput from '@/components/fluxa/CurrencyInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type OptionAccount = {
    id: number;
    name: string;
    type: string;
    balance: string;
};

const props = withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'put';
        accounts?: OptionAccount[];
        transfer?: App.Data.TransferData | null;
    }>(),
    { method: 'post', accounts: () => [], transfer: null },
);

const form = useForm({
    from_account_id: props.transfer?.from_account_id ?? (null as number | null),
    to_account_id: props.transfer?.to_account_id ?? (null as number | null),
    amount: (props.transfer ? Number(props.transfer.amount) : null) as
        | number
        | null,
    description: props.transfer?.description ?? '',
    transfer_date:
        props.transfer?.transfer_date ?? new Date().toISOString().slice(0, 10),
});

// Kartu kredit dan paylater tidak ikut transfer (pakai Bayar tagihan);
// backend menolaknya juga, jadi daftar ini tidak pernah menawarkannya.
const assetAccounts = computed(() =>
    props.accounts.filter(
        (a) => a.type !== 'credit_card' && a.type !== 'paylater',
    ),
);

function swapAccounts(): void {
    const from = form.from_account_id;
    form.from_account_id = form.to_account_id;
    form.to_account_id = from;
}

// transform() merusak tipe form.errors, baca lewat cast (lihat CRUD_FLOW §6).
const errorFor = computed<Record<string, string | undefined>>(
    () => form.errors as Record<string, string | undefined>,
);

function submit(): void {
    form.transform((data) => ({
        ...data,
        amount:
            data.amount === null || data.amount === undefined
                ? ''
                : String(data.amount),
    }));

    if (props.method === 'put') {
        form.put(props.action);

        return;
    }

    form.post(props.action);
}

const selectClass =
    'border-input bg-card focus-visible:ring-ring min-h-11 w-full rounded-lg border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none';
</script>

<template>
    <Head :title="method === 'put' ? 'Ubah Transfer' : 'Transfer Baru'" />

    <div class="mx-auto max-w-2xl p-4">
        <form class="space-y-6" novalidate @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="transfer-from">Dari kantong</Label>
                <select
                    id="transfer-from"
                    v-model="form.from_account_id"
                    :class="selectClass"
                >
                    <option :value="null" disabled>Pilih kantong sumber</option>
                    <option
                        v-for="account in assetAccounts"
                        :key="account.id"
                        :value="account.id"
                    >
                        {{ account.name }}
                    </option>
                </select>
                <InputError :message="errorFor.from_account_id" />
            </div>

            <div class="flex justify-center">
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-11 rounded-full"
                    aria-label="Tukar sumber dan tujuan"
                    @click="swapAccounts"
                >
                    <ArrowDownUp class="size-4" aria-hidden="true" />
                </Button>
            </div>

            <div class="grid gap-2">
                <Label for="transfer-to">Ke kantong</Label>
                <select
                    id="transfer-to"
                    v-model="form.to_account_id"
                    :class="selectClass"
                >
                    <option :value="null" disabled>Pilih kantong tujuan</option>
                    <option
                        v-for="account in assetAccounts"
                        :key="account.id"
                        :value="account.id"
                    >
                        {{ account.name }}
                    </option>
                </select>
                <InputError :message="errorFor.to_account_id" />
            </div>

            <div class="grid gap-2">
                <Label for="transfer-amount">Nominal</Label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm font-medium"
                        aria-hidden="true"
                    >
                        Rp
                    </span>
                    <CurrencyInput
                        id="transfer-amount"
                        v-model="form.amount"
                        name="amount"
                        required
                        class="font-numeric min-h-11 pr-4 pl-10 text-lg font-bold tabular-nums"
                        placeholder="0"
                        autocomplete="off"
                    />
                </div>
                <InputError :message="errorFor.amount" />
            </div>

            <div class="grid gap-2">
                <Label for="transfer-date">Tanggal</Label>
                <Input
                    id="transfer-date"
                    v-model="form.transfer_date"
                    name="transfer_date"
                    type="date"
                    required
                    class="min-h-11"
                />
                <InputError :message="errorFor.transfer_date" />
            </div>

            <div class="grid gap-2">
                <Label for="transfer-description">Deskripsi</Label>
                <Input
                    id="transfer-description"
                    v-model="form.description"
                    name="description"
                    class="min-h-11"
                    placeholder="cth. Isi ulang kas harian"
                    autocomplete="off"
                />
                <InputError :message="errorFor.description" />
            </div>

            <Button
                type="submit"
                class="min-h-11 w-full sm:w-auto"
                :disabled="form.processing"
            >
                {{ method === 'put' ? 'Simpan perubahan' : 'Simpan transfer' }}
            </Button>
        </form>
    </div>
</template>

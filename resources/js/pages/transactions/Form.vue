<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import BillPaymentFields from '@/components/fluxa/BillPaymentFields.vue';
import CategoryPicker from '@/components/fluxa/CategoryPicker.vue';
import CurrencyInput from '@/components/fluxa/CurrencyInput.vue';
import ImageUpload from '@/components/fluxa/ImageUpload.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type OptionAccount = {
    id: number;
    name: string;
    type: string;
    balance: string;
};
type OptionCategory = { id: number; name: string; type: string };

const props = withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'put';
        accounts?: OptionAccount[];
        categories?: OptionCategory[];
        types?: string[];
        transaction?: App.Data.TransactionData | null;
    }>(),
    {
        method: 'post',
        accounts: () => [],
        categories: () => [],
        types: () => [],
        transaction: null,
    },
);

const form = useForm({
    type: props.transaction?.type ?? 'expense',
    account_id: props.transaction?.account_id ?? (null as number | null),
    category_id: props.transaction?.category_id ?? (null as number | null),
    linked_account_id:
        props.transaction?.linked_account_id ?? (null as number | null),
    amount: (props.transaction ? Number(props.transaction.amount) : null) as
        | number
        | null,
    description: props.transaction?.description ?? '',
    transaction_date:
        props.transaction?.transaction_date ??
        new Date().toISOString().slice(0, 10),
    receipt: null as File | null,
    remove_receipt: false,
});

const touchedCategory = ref(false);

const selectedAccount = computed(() =>
    props.accounts.find((a) => a.id === form.account_id),
);

const isLiabilityAccount = computed(
    () =>
        selectedAccount.value?.type === 'credit_card' ||
        selectedAccount.value?.type === 'paylater',
);

const isBillPayment = computed(() => form.type === 'bill_payment');

const visibleTypes = computed(() =>
    props.types.filter(
        (type) => type !== 'bill_payment' || isLiabilityAccount.value,
    ),
);

const sourceAccounts = computed(() =>
    props.accounts.filter(
        (a) =>
            a.id !== form.account_id &&
            a.type !== 'credit_card' &&
            a.type !== 'paylater',
    ),
);

const visibleCategories = computed(() =>
    props.categories.filter((c) => c.type === form.type),
);

watch(
    () => form.type,
    () => {
        if (isBillPayment.value || !touchedCategory.value) {
            form.category_id = null;
        }
    },
);

// Kantong aset tidak bisa melunasi: ganti jenis kembali saat kantong yang
// dipilih bukan kartu kredit atau paylater.
watch(isLiabilityAccount, (liability) => {
    if (!liability && isBillPayment.value) {
        form.type = 'expense';
    }
});

function pickCategory(id: number): void {
    touchedCategory.value = true;
    form.category_id = id;
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

const typeLabel = (type: string): string =>
    type === 'income'
        ? 'Pemasukan'
        : type === 'bill_payment'
          ? 'Bayar tagihan'
          : 'Pengeluaran';
</script>

<template>
    <Head :title="method === 'put' ? 'Ubah Transaksi' : 'Transaksi Baru'" />

    <div class="mx-auto max-w-2xl p-4">
        <form class="space-y-6" novalidate @submit.prevent="submit">
            <fieldset class="grid gap-2">
                <legend class="text-sm leading-none font-medium">Jenis</legend>
                <div
                    class="bg-muted grid gap-1 rounded-xl p-1"
                    :class="
                        visibleTypes.length > 2 ? 'grid-cols-3' : 'grid-cols-2'
                    "
                >
                    <button
                        v-for="type in visibleTypes"
                        :key="type"
                        type="button"
                        class="focus-visible:ring-ring min-h-11 rounded-lg px-1 text-sm font-semibold focus-visible:ring-2 focus-visible:outline-none"
                        :class="
                            form.type === type
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground'
                        "
                        :aria-pressed="form.type === type"
                        @click="form.type = type as App.Enums.TransactionType"
                    >
                        {{ typeLabel(type) }}
                    </button>
                </div>
                <InputError :message="errorFor.type" />
            </fieldset>

            <div class="grid gap-2">
                <Label for="transaction-amount">Nominal</Label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm font-medium"
                        aria-hidden="true"
                    >
                        Rp
                    </span>
                    <CurrencyInput
                        id="transaction-amount"
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
                <Label for="transaction-account">Kantong</Label>
                <select
                    id="transaction-account"
                    v-model="form.account_id"
                    class="border-input bg-card focus-visible:ring-ring min-h-11 w-full rounded-lg border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none"
                >
                    <option :value="null" disabled>Pilih kantong</option>
                    <option
                        v-for="account in accounts"
                        :key="account.id"
                        :value="account.id"
                    >
                        {{ account.name }}
                    </option>
                </select>
                <InputError :message="errorFor.account_id" />
            </div>

            <BillPaymentFields
                v-if="isBillPayment"
                v-model="form.linked_account_id"
                :accounts="sourceAccounts"
                :error="errorFor.linked_account_id"
            />

            <CategoryPicker
                v-else
                :categories="visibleCategories"
                :selected-id="form.category_id"
                :empty-hint="`Belum ada kategori ${typeLabel(form.type).toLowerCase()}.`"
                :error="errorFor.category_id"
                @select="pickCategory"
            />

            <div class="grid gap-2">
                <Label for="transaction-date">Tanggal</Label>
                <Input
                    id="transaction-date"
                    v-model="form.transaction_date"
                    name="transaction_date"
                    type="date"
                    required
                    class="min-h-11"
                />
                <InputError :message="errorFor.transaction_date" />
            </div>

            <div class="grid gap-2">
                <Label for="transaction-description">Deskripsi</Label>
                <Input
                    id="transaction-description"
                    v-model="form.description"
                    name="description"
                    class="min-h-11"
                    placeholder="cth. Belanja mingguan"
                    autocomplete="off"
                />
                <InputError :message="errorFor.description" />
            </div>

            <ImageUpload
                input-id="transaction-receipt"
                input-name="receipt"
                label="Foto struk (opsional)"
                preview-alt="Foto struk transaksi"
                :initial-preview="transaction?.receipt_url ?? ''"
                :error="errorFor.receipt"
                @select="
                    form.receipt = $event;
                    form.remove_receipt = false;
                "
                @clear="
                    form.receipt = null;
                    form.remove_receipt = true;
                "
            />

            <Button
                type="submit"
                class="min-h-11 w-full sm:w-auto"
                :disabled="form.processing"
            >
                {{ method === 'put' ? 'Simpan perubahan' : 'Simpan transaksi' }}
            </Button>
        </form>
    </div>
</template>

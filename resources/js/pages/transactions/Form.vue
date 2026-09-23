<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ReceiptText } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import CurrencyInput from '@/components/fluxa/CurrencyInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type OptionAccount = { id: number; name: string; balance: string };
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

const visibleCategories = computed(() =>
    props.categories.filter((c) => c.type === form.type),
);

watch(
    () => form.type,
    () => {
        if (!touchedCategory) {
            form.category_id = null;
        }
    },
);

function pickCategory(id: number): void {
    touchedCategory.value = true;
    form.category_id = id;
}

const receiptPreview = ref<string>(props.transaction?.receipt_url ?? '');

function onReceiptChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (file === null) {
        return;
    }

    if (receiptPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(receiptPreview.value);
    }

    form.receipt = file;
    form.remove_receipt = false;
    receiptPreview.value = URL.createObjectURL(file);
}

function clearReceipt(): void {
    if (receiptPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(receiptPreview.value);
    }

    form.receipt = null;
    form.remove_receipt = true;
    receiptPreview.value = '';
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
    type === 'income' ? 'Pemasukan' : 'Pengeluaran';
</script>

<template>
    <Head :title="method === 'put' ? 'Ubah Transaksi' : 'Transaksi Baru'" />

    <div class="mx-auto max-w-2xl p-4">
        <form class="space-y-6" novalidate @submit.prevent="submit">
            <fieldset class="grid gap-2">
                <legend class="text-sm leading-none font-medium">Jenis</legend>
                <div class="bg-muted grid grid-cols-2 gap-1 rounded-xl p-1">
                    <button
                        v-for="type in types"
                        :key="type"
                        type="button"
                        class="focus-visible:ring-ring min-h-11 rounded-lg text-sm font-semibold focus-visible:ring-2 focus-visible:outline-none"
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

            <fieldset class="grid gap-2">
                <legend class="text-sm leading-none font-medium">
                    Kategori
                </legend>
                <p
                    v-if="!visibleCategories.length"
                    class="text-muted-foreground text-sm"
                >
                    Belum ada kategori {{ typeLabel(form.type).toLowerCase() }}.
                </p>
                <div v-else class="flex flex-wrap gap-1.5">
                    <button
                        v-for="category in visibleCategories"
                        :key="category.id"
                        type="button"
                        class="focus-visible:ring-ring min-h-11 rounded-full border px-4 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                        :class="
                            form.category_id === category.id
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-input text-muted-foreground hover:bg-accent'
                        "
                        :aria-pressed="form.category_id === category.id"
                        @click="pickCategory(category.id)"
                    >
                        {{ category.name }}
                    </button>
                </div>
                <InputError :message="errorFor.category_id" />
            </fieldset>

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

            <div class="grid gap-2">
                <Label for="transaction-receipt">Foto struk (opsional)</Label>
                <div class="flex items-center gap-3">
                    <span
                        class="bg-muted text-muted-foreground flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border"
                    >
                        <img
                            v-if="receiptPreview"
                            :src="receiptPreview"
                            alt="Foto struk transaksi"
                            class="size-full object-cover"
                        />
                        <ReceiptText v-else class="size-6" aria-hidden="true" />
                    </span>
                    <Input
                        id="transaction-receipt"
                        name="receipt"
                        type="file"
                        accept="image/*"
                        class="file:text-foreground min-h-11 file:border-0 file:bg-transparent file:text-sm file:font-medium"
                        @change="onReceiptChange"
                    />
                    <Button
                        v-if="receiptPreview"
                        type="button"
                        variant="ghost"
                        class="min-h-11 shrink-0"
                        @click="clearReceipt"
                    >
                        Hapus
                    </Button>
                </div>
                <InputError :message="errorFor.receipt" />
            </div>

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

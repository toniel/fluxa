<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/currency';

const props = withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'put';
        types?: string[];
        // Absent saat membuat, yang membuat saldo awal bisa diketik.
        account?: App.Data.AccountData | null;
    }>(),
    { method: 'post', types: () => [], account: null },
);

const typeLabels: Record<string, string> = {
    cash: 'Tunai',
    bank: 'Rekening bank',
    ewallet: 'E-wallet',
    other: 'Lainnya',
};

const form = useForm({
    name: props.account?.name ?? '',
    type: props.account?.type ?? 'cash',
    initial_balance: props.account?.initial_balance ?? '',
});

// transform() merusak tipe form.errors, baca lewat cast (lihat CRUD_FLOW §6).
const errorFor = computed<Record<string, string | undefined>>(
    () => form.errors as Record<string, string | undefined>,
);

// Saldo sekarang hanya menampilkan prakiraan angka saat user mengetik; nilai
// yang dikirim tetap string mentah, bukan string "Rp ..." yang harus di-parse.
const balancePreview = computed(() =>
    form.initial_balance === '' ? '' : formatRupiah(form.initial_balance),
);

const isEdit = props.method === 'put';

function submit(): void {
    if (isEdit) {
        form.put(props.action);

        return;
    }

    form.post(props.action);
}
</script>

<template>
    <Head :title="isEdit ? 'Ubah Kantong' : 'Kantong Baru'" />

    <div class="mx-auto max-w-2xl p-4">
        <form class="space-y-6" novalidate @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="account-name">Nama kantong</Label>
                <Input
                    id="account-name"
                    v-model="form.name"
                    name="name"
                    required
                    class="min-h-11"
                    placeholder="Mis. Kas Harian, BCA, GoPay"
                    autocomplete="off"
                />
                <InputError :message="errorFor.name" />
            </div>

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
                        @click="form.type = type as App.Enums.AccountType"
                    >
                        {{ typeLabels[type] ?? type }}
                    </button>
                </div>
                <InputError :message="errorFor.type" />
            </fieldset>

            <div class="grid gap-2">
                <Label for="account-initial">Saldo awal</Label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm font-medium"
                        aria-hidden="true"
                    >
                        Rp
                    </span>
                    <Input
                        id="account-initial"
                        v-model="form.initial_balance"
                        name="initial_balance"
                        type="number"
                        inputmode="decimal"
                        step="0.01"
                        min="0"
                        required
                        class="min-h-11 pr-4 pl-10"
                        placeholder="0"
                        :readonly="isEdit"
                        :aria-readonly="isEdit"
                    />
                </div>
                <p v-if="balancePreview" class="text-muted-foreground text-sm">
                    {{ balancePreview }}
                </p>
                <p v-else-if="isEdit" class="text-muted-foreground text-sm">
                    Saldo awal tidak bisa diubah setelah kantong dibuat.
                </p>
                <InputError :message="errorFor.initial_balance" />
            </div>

            <Button
                type="submit"
                class="min-h-11 w-full sm:w-auto"
                :disabled="form.processing"
            >
                {{ isEdit ? 'Simpan perubahan' : 'Simpan kantong' }}
            </Button>
        </form>
    </div>
</template>

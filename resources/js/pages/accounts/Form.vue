<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, Landmark, PiggyBank, Smartphone, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
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

const typeOptions: Record<string, { label: string; icon: typeof Wallet }> = {
    cash: { label: 'Tunai', icon: Wallet },
    bank: { label: 'Rekening bank', icon: Landmark },
    ewallet: { label: 'E-wallet', icon: Smartphone },
    other: { label: 'Lainnya', icon: PiggyBank },
};

const form = useForm({
    name: props.account?.name ?? '',
    type: props.account?.type ?? 'cash',
    initial_balance: props.account?.initial_balance ?? '',
    logo: null as File | null,
    remove_logo: false,
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
const logoPreview = ref<string>(props.account?.logo_url ?? '');

// Indonesia memakai koma untuk desimal dan titik untuk ribuan. Diterjemahkan
// ke angka polos yang bisa divalidasi Numeric di backend: "25.000" -> "25000",
// "25000,50" -> "25000.50". Tidak menebak-nebak maksud koma/titik.
function toPlainNumber(value: string): string {
    const cleaned = value.replace(/[^\d.,]/g, '');

    if (cleaned.includes(',')) {
        return cleaned.replace(/\./g, '').replace(',', '.');
    }

    return cleaned.replace(/\./g, '');
}

function onLogoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (file === null) {
        return;
    }

    if (logoPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(logoPreview.value);
    }

    form.logo = file;
    form.remove_logo = false;
    logoPreview.value = URL.createObjectURL(file);
}

function clearLogo(): void {
    if (logoPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(logoPreview.value);
    }

    form.logo = null;
    form.remove_logo = true;
    logoPreview.value = '';
}

function submit(): void {
    form.initial_balance = toPlainNumber(form.initial_balance);

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
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="type in types"
                        :key="type"
                        type="button"
                        class="focus-visible:ring-ring flex min-h-14 items-center gap-2.5 rounded-xl border px-3 text-sm font-semibold focus-visible:ring-2 focus-visible:outline-none"
                        :class="
                            form.type === type
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-input text-muted-foreground hover:bg-accent'
                        "
                        :aria-pressed="form.type === type"
                        @click="form.type = type as App.Enums.AccountType"
                    >
                        <component
                            :is="typeOptions[type]?.icon ?? Wallet"
                            class="size-5 shrink-0"
                            aria-hidden="true"
                        />
                        <span class="truncate">
                            {{ typeOptions[type]?.label ?? type }}
                        </span>
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
                        type="text"
                        inputmode="decimal"
                        required
                        class="min-h-11 pr-4 pl-10"
                        placeholder="0"
                        autocomplete="off"
                        :readonly="isEdit"
                        :aria-readonly="isEdit"
                    />
                </div>
                <p v-if="isEdit" class="text-muted-foreground text-sm">
                    Saldo awal tidak bisa diubah setelah kantong dibuat.
                </p>
                <p v-else class="text-muted-foreground text-sm">
                    Pakai koma untuk desimal, mis. 25000,50.
                    <span v-if="balancePreview" class="font-medium">
                        → {{ balancePreview }}
                    </span>
                </p>
                <InputError :message="errorFor.initial_balance" />
            </div>

            <div class="grid gap-2">
                <Label for="account-logo">Logo kantong</Label>
                <div class="flex items-center gap-3">
                    <span
                        class="bg-muted text-muted-foreground flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-full"
                    >
                        <img
                            v-if="logoPreview"
                            :src="logoPreview"
                            :alt="`Logo ${form.name || 'kantong'}`"
                            class="size-full object-cover"
                        />
                        <Camera class="size-6" aria-hidden="true" />
                    </span>
                    <Input
                        id="account-logo"
                        name="logo"
                        type="file"
                        accept="image/*"
                        class="file:text-foreground min-h-11 file:border-0 file:bg-transparent file:text-sm file:font-medium"
                        @change="onLogoChange"
                    />
                    <Button
                        v-if="logoPreview"
                        type="button"
                        variant="ghost"
                        class="min-h-11 shrink-0"
                        @click="clearLogo"
                    >
                        Hapus
                    </Button>
                </div>
                <InputError :message="errorFor.logo" />
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

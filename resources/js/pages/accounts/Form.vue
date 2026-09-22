<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, Landmark, PiggyBank, Smartphone, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import CurrencyInput from '@/components/fluxa/CurrencyInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
    initial_balance: (
        props.account?.initial_balance ? Number(props.account.initial_balance) : null
    ) as number | null,
    logo: null as File | null,
    remove_logo: false,
});

// transform() merusak tipe form.errors, baca lewat cast (lihat CRUD_FLOW §6).
const errorFor = computed<Record<string, string | undefined>>(
    () => form.errors as Record<string, string | undefined>,
);

const isEdit = props.method === 'put';
const logoPreview = ref<string>(props.account?.logo_url ?? '');

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
    // Library mengirim angka, backend menerima string numerik. Ubah di
    // transform (tidak mengubah nilai lokal) supaya kontrak payload tetap.
    form.transform((data) => ({
        ...data,
        initial_balance:
            data.initial_balance === null || data.initial_balance === undefined
                ? ''
                : String(data.initial_balance),
    }));

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
                    <CurrencyInput
                        id="account-initial"
                        v-model="form.initial_balance"
                        name="initial_balance"
                        required
                        class="font-numeric min-h-11 pr-4 pl-10 text-lg font-bold tabular-nums"
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
                    Diketik biasa, titik ribuan muncul otomatis.
                </p>
                <InputError :message="errorFor.initial_balance" />
            </div>

            <div class="grid gap-2">
                <Label for="account-logo">Logo kantong</Label>
                <div class="flex items-center gap-3">
                    <span
                        class="bg-muted text-muted-foreground flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border"
                    >
                        <img
                            v-if="logoPreview"
                            :src="logoPreview"
                            :alt="`Logo ${form.name || 'kantong'}`"
                            class="size-full bg-white object-contain"
                        />
                        <Camera v-else class="size-6" aria-hidden="true" />
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

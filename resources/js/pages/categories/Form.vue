<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { categoryIcon } from '@/lib/categoryIcons';

const props = withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'put';
        types?: string[];
        icons?: string[];
        // Absent saat membuat, yang membuat semua field kosong.
        category?: App.Data.CategoryData | null;
    }>(),
    { method: 'post', types: () => [], icons: () => [], category: null },
);

// Nama Indonesia per ikon, dipakai sebagai label tombol supaya bisa dibaca
// pembaca layar dan tidak membingungkan kapan ikonnya tidak jelas artinya.
const iconLabels: Record<string, string> = {
    baby: 'Anak',
    banknote: 'Uang',
    briefcase: 'Gaji',
    broom: 'Perlengkapan rumah',
    bus: 'Bus',
    car: 'Kendaraan',
    'circle-dollar-sign': 'Penghasilan',
    'circle-plus': 'Umum',
    coins: 'Iuran',
    'credit-card': 'Kartu',
    dumbbell: 'Olahraga',
    ellipsis: 'Lainnya',
    'gamepad-2': 'Hiburan',
    gift: 'Hadiah',
    'graduation-cap': 'Pendidikan',
    'hand-coins': 'Donasi',
    'heart-pulse': 'Kesehatan',
    house: 'Rumah',
    key: 'Kontrakan',
    landmark: 'Bank',
    laptop: 'Gadget',
    'paw-print': 'Hewan',
    'piggy-bank': 'Tabungan',
    plane: 'Perjalanan',
    receipt: 'Tagihan',
    shirt: 'Pakaian',
    'shopping-bag': 'Belanja',
    'shopping-basket': 'Sembako',
    'shopping-cart': 'Belanja besar',
    smartphone: 'Pulsa',
    stethoscope: 'Obat',
    utensils: 'Makan',
    wallet: 'Dompet',
    wrench: 'Perbaikan',
};

const form = useForm({
    name: props.category?.name ?? '',
    type: props.category?.type ?? 'expense',
    icon: props.category?.icon ?? '',
    is_default: props.category?.is_default ?? false,
});

// transform() merusak tipe form.errors, baca lewat cast (lihat CRUD_FLOW §6).
const errorFor = computed<Record<string, string | undefined>>(
    () => form.errors as Record<string, string | undefined>,
);

function submit(): void {
    if (props.method === 'put') {
        form.put(props.action);

        return;
    }

    form.post(props.action);
}
</script>

<template>
    <Head :title="method === 'put' ? 'Ubah Kategori' : 'Kategori Baru'" />

    <div class="mx-auto max-w-2xl p-4">
        <form class="space-y-6" novalidate @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="category-name">Nama kategori</Label>
                <Input
                    id="category-name"
                    v-model="form.name"
                    name="name"
                    required
                    class="min-h-11"
                    placeholder="Mis. Makan, Transport, Gaji"
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
                        @click="form.type = type as App.Enums.CategoryType"
                    >
                        {{ type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                    </button>
                </div>
                <InputError :message="errorFor.type" />
            </fieldset>

            <fieldset class="grid gap-2">
                <legend class="text-sm leading-none font-medium">Ikon</legend>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="icon in icons"
                        :key="icon"
                        type="button"
                        class="focus-visible:ring-ring flex size-12 items-center justify-center rounded-full border p-0 focus-visible:ring-2 focus-visible:outline-none"
                        :class="
                            form.icon === icon
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-input hover:bg-accent text-muted-foreground'
                        "
                        :aria-label="iconLabels[icon] ?? icon"
                        :aria-pressed="form.icon === icon"
                        :title="iconLabels[icon] ?? icon"
                        @click="form.icon = icon"
                    >
                        <component :is="categoryIcon(icon)" class="size-5" />
                    </button>
                </div>
                <InputError :message="errorFor.icon" />
            </fieldset>

            <Label
                for="category-default"
                class="flex min-h-11 items-start gap-3 rounded-xl border p-3"
            >
                <Checkbox
                    id="category-default"
                    v-model:checked="form.is_default"
                    name="is_default"
                    class="mt-0.5"
                />
                <span class="grid gap-0.5">
                    <span class="text-sm font-medium">Kategori bawaan</span>
                    <span class="text-muted-foreground text-xs">
                        Ditandai di daftar. Tetap bisa diedit atau dihapus.
                    </span>
                </span>
            </Label>

            <Button
                type="submit"
                class="min-h-11 w-full sm:w-auto"
                :disabled="form.processing"
            >
                {{ method === 'put' ? 'Simpan perubahan' : 'Simpan kategori' }}
            </Button>
        </form>
    </div>
</template>

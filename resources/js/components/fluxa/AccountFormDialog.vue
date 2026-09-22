<script setup lang="ts">
import {
    Banknote,
    Briefcase,
    Car,
    Coffee,
    GraduationCap,
    HandCoins,
    House,
    Landmark,
    PiggyBank,
    Plane,
    Receipt,
    ShoppingBag,
    Smartphone,
    Sprout,
    Users,
    Utensils,
    Wallet,
    Zap,
} from '@lucide/vue';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CurrencyInput from '@/components/fluxa/CurrencyInput.vue';
import { notYet } from '@/lib/notYet';

const open = defineModel<boolean>('open', { default: false });

const types = [
    { value: 'cash', label: 'Tunai' },
    { value: 'bank', label: 'Rekening bank' },
    { value: 'ewallet', label: 'E-wallet' },
    { value: 'other', label: 'Lainnya' },
];

const colors = [
    { value: 'hijau', className: 'bg-swatch-1' },
    { value: 'biru', className: 'bg-swatch-2' },
    { value: 'teal', className: 'bg-swatch-3' },
    { value: 'amber', className: 'bg-swatch-4' },
    { value: 'oranye', className: 'bg-swatch-5' },
    { value: 'rose', className: 'bg-swatch-6' },
    { value: 'ungu', className: 'bg-swatch-7' },
    { value: 'cokelat', className: 'bg-swatch-8' },
];

const icons = [
    { value: 'wallet', icon: Wallet, label: 'Dompet' },
    { value: 'landmark', icon: Landmark, label: 'Bank' },
    { value: 'smartphone', icon: Smartphone, label: 'Ponsel' },
    { value: 'piggy-bank', icon: PiggyBank, label: 'Celengan' },
    { value: 'house', icon: House, label: 'Rumah' },
    { value: 'banknote', icon: Banknote, label: 'Uang' },
    { value: 'hand-coins', icon: HandCoins, label: 'Iuran' },
    { value: 'receipt', icon: Receipt, label: 'Tagihan' },
    { value: 'shopping-bag', icon: ShoppingBag, label: 'Belanja' },
    { value: 'utensils', icon: Utensils, label: 'Makan' },
    { value: 'car', icon: Car, label: 'Transport' },
    { value: 'zap', icon: Zap, label: 'Listrik' },
    { value: 'graduation-cap', icon: GraduationCap, label: 'Pendidikan' },
    { value: 'briefcase', icon: Briefcase, label: 'Kerja' },
    { value: 'plane', icon: Plane, label: 'Liburan' },
    { value: 'coffee', icon: Coffee, label: 'Jajan' },
    { value: 'users', icon: Users, label: 'Bersama' },
    { value: 'sprout', icon: Sprout, label: 'Tabungan' },
];

const form = ref({
    name: '',
    type: 'cash',
    initial: null as number | null,
    color: 'hijau',
    icon: 'wallet',
});

function submit(): void {
    notYet('Simpan kantong');
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <!--
            Lembar bawah di HP, dialog terpusat dari md ke atas. Di layar sempit
            sisi bawah lebih dekat ke ibu jari daripada tengah layar.
        -->
        <DialogContent
            class="top-auto bottom-0 left-0 max-h-[92svh] w-full max-w-none translate-x-0 translate-y-0 gap-0 overflow-y-auto rounded-t-3xl p-0 sm:max-w-none md:top-1/2 md:bottom-auto md:left-1/2 md:max-w-md md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-3xl"
        >
            <DialogHeader class="border-b px-5 py-4 text-left">
                <DialogTitle class="text-lg font-bold"
                    >Kantong baru</DialogTitle
                >
                <DialogDescription class="sr-only">
                    Isi nama, tipe, saldo awal, warna, dan ikon kantong.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4 px-5 py-4" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <Label for="account-name" class="text-xs font-semibold"
                        >Nama kantong</Label
                    >
                    <Input
                        id="account-name"
                        v-model="form.name"
                        class="min-h-11 rounded-xl"
                        placeholder="cth. Kas Rumah"
                        required
                    />
                </div>

                <div class="space-y-1.5">
                    <Label for="account-type" class="text-xs font-semibold"
                        >Tipe</Label
                    >
                    <!--
                        <select> bawaan, bukan dropdown buatan: di HP ia membuka
                        pemilih milik sistem yang sudah dikenal penggunanya.
                    -->
                    <select
                        id="account-type"
                        v-model="form.type"
                        class="border-input bg-card focus-visible:ring-ring min-h-11 w-full rounded-xl border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none"
                    >
                        <option
                            v-for="type in types"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <Label for="account-initial" class="text-xs font-semibold"
                        >Saldo awal</Label
                    >
                    <div class="relative">
                        <span
                            class="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-sm font-semibold"
                            aria-hidden="true"
                            >Rp</span
                        >
                        <CurrencyInput
                            id="account-initial"
                            v-model="form.initial"
                            class="font-numeric min-h-11 rounded-xl pl-10 text-lg font-bold tabular-nums"
                            placeholder="0"
                        />
                    </div>
                </div>

                <fieldset class="space-y-1.5">
                    <legend class="mb-1.5 text-xs font-semibold">Warna</legend>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="color in colors"
                            :key="color.value"
                            type="button"
                            class="focus-visible:ring-ring ring-offset-background size-11 rounded-full p-1.5 focus-visible:ring-2 focus-visible:outline-none"
                            :aria-label="`Warna ${color.value}`"
                            :aria-pressed="form.color === color.value"
                            @click="form.color = color.value"
                        >
                            <!-- Cincin tipis menjaga tepi swatch tetap terlihat
                                 walau isinya terang di atas latar terang. -->
                            <span
                                class="ring-border block size-full rounded-full ring-1"
                                :class="[
                                    color.className,
                                    form.color === color.value &&
                                        'ring-foreground ring-2 ring-offset-2',
                                ]"
                            />
                        </button>
                    </div>
                </fieldset>

                <fieldset class="space-y-1.5">
                    <legend class="mb-1.5 text-xs font-semibold">Ikon</legend>
                    <!-- Enam kolom di HP, bukan delapan: delapan membuat tiap
                         sel jatuh di bawah 44px. -->
                    <div class="grid grid-cols-6 gap-1.5 sm:grid-cols-8">
                        <button
                            v-for="item in icons"
                            :key="item.value"
                            type="button"
                            class="focus-visible:ring-ring flex aspect-square items-center justify-center rounded-xl border transition focus-visible:ring-2 focus-visible:outline-none"
                            :class="
                                form.icon === item.value
                                    ? 'bg-primary text-primary-foreground border-primary'
                                    : 'bg-card text-muted-foreground'
                            "
                            :aria-label="item.label"
                            :aria-pressed="form.icon === item.value"
                            @click="form.icon = item.value"
                        >
                            <component :is="item.icon" class="size-4" />
                        </button>
                    </div>
                </fieldset>
            </form>

            <DialogFooter
                class="flex-row justify-end gap-2 border-t px-5 py-4 pb-[calc(1rem+env(safe-area-inset-bottom))]"
            >
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    @click="open = false"
                >
                    Batal
                </Button>
                <Button type="button" class="min-h-11" @click="submit">
                    Buat kantong
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
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
import { formatRupiah } from '@/lib/currency';
import { notYet } from '@/lib/notYet';

type Account = {
    id: number;
    name: string;
    balance: string;
    is_archived: boolean;
};
type Category = { id: number; name: string; type: 'income' | 'expense' };

const props = defineProps<{
    accounts: Account[];
    categories: Category[];
}>();

const open = defineModel<boolean>('open', { default: false });

/**
 * bg-chart-out sengaja hampir sama gelapnya di kedua tema (ia juga dipakai
 * sebagai warna mark chart), sehingga tidak ada token teks bawaan yang
 * lolos 4.5:1 di keduanya sekaligus. Lihat categories/Index.vue untuk
 * angka pengukurannya.
 */
const activeExpenseTextClass = 'text-[#16211c]';

const today = new Date().toISOString().slice(0, 10);

const type = ref<'expense' | 'income'>('expense');
const amount = ref('');
const accountId = ref<number | null>(null);
const categoryId = ref<number | null>(null);
const date = ref(today);
const note = ref('');

// Kantong terarsip tidak boleh jadi tujuan transaksi baru.
const selectableAccounts = computed(() =>
    props.accounts.filter((a) => !a.is_archived),
);

const selectableCategories = computed(() =>
    props.categories.filter((c) => c.type === type.value),
);

/**
 * Kategori pemasukan dan pengeluaran adalah dua daftar terpisah, jadi mengganti
 * jenis harus melepas pilihan lama: membiarkannya akan menyimpan transaksi
 * dengan kategori yang tidak mungkin.
 */
watch(type, () => {
    categoryId.value = selectableCategories.value[0]?.id ?? null;
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    type.value = 'expense';
    amount.value = '';
    accountId.value = selectableAccounts.value[0]?.id ?? null;
    categoryId.value = selectableCategories.value[0]?.id ?? null;
    date.value = today;
    note.value = '';
});

/** Hanya digit yang disimpan; pemisah ribuan ditambahkan saat ditampilkan. */
function onAmountInput(event: Event): void {
    const digits = (event.target as HTMLInputElement).value.replace(/\D/g, '');
    amount.value = digits ? Number(digits).toLocaleString('id-ID') : '';
}

function submit(): void {
    notYet('Simpan transaksi');
    open.value = false;
}

const selectClass =
    'border-input bg-card focus-visible:ring-ring min-h-11 w-full rounded-xl border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none';
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="top-auto bottom-0 left-0 max-h-[92svh] w-full max-w-none translate-x-0 translate-y-0 gap-0 overflow-y-auto rounded-t-3xl p-0 sm:max-w-none md:top-1/2 md:bottom-auto md:left-1/2 md:max-w-md md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-3xl"
        >
            <DialogHeader class="border-b px-5 py-4 text-left">
                <DialogTitle class="text-lg font-bold">
                    Catat transaksi
                </DialogTitle>
                <DialogDescription class="sr-only">
                    Pilih jenis, nominal, kantong, kategori, tanggal, dan
                    deskripsi transaksi.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4 px-5 py-4" @submit.prevent="submit">
                <!--
                    Jenis diletakkan paling atas karena ia menentukan isi daftar
                    kategori di bawahnya. Warnanya mengikuti arah uang yang
                    dipakai di seluruh aplikasi, bukan warna tombol biasa.
                -->
                <div
                    class="bg-muted grid grid-cols-2 gap-1 rounded-xl p-1"
                    role="group"
                    aria-label="Jenis transaksi"
                >
                    <button
                        type="button"
                        class="focus-visible:ring-ring min-h-11 rounded-lg text-sm font-semibold focus-visible:ring-2 focus-visible:outline-none"
                        :class="
                            type === 'expense'
                                ? `bg-chart-out ${activeExpenseTextClass} shadow-sm`
                                : 'text-muted-foreground'
                        "
                        :aria-pressed="type === 'expense'"
                        @click="type = 'expense'"
                    >
                        Pengeluaran
                    </button>
                    <button
                        type="button"
                        class="focus-visible:ring-ring min-h-11 rounded-lg text-sm font-semibold focus-visible:ring-2 focus-visible:outline-none"
                        :class="
                            type === 'income'
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground'
                        "
                        :aria-pressed="type === 'income'"
                        @click="type = 'income'"
                    >
                        Pemasukan
                    </button>
                </div>

                <div class="space-y-1.5">
                    <Label for="trx-amount" class="text-xs font-semibold"
                        >Nominal</Label
                    >
                    <div class="relative">
                        <span
                            class="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-sm font-semibold"
                            aria-hidden="true"
                            >Rp</span
                        >
                        <Input
                            id="trx-amount"
                            :model-value="amount"
                            inputmode="numeric"
                            required
                            class="font-numeric min-h-11 rounded-xl pl-10 text-lg font-bold tabular-nums"
                            placeholder="0"
                            @input="onAmountInput"
                        />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="trx-account" class="text-xs font-semibold"
                        >Kantong</Label
                    >
                    <select
                        id="trx-account"
                        v-model="accountId"
                        :class="selectClass"
                    >
                        <option
                            v-for="account in selectableAccounts"
                            :key="account.id"
                            :value="account.id"
                        >
                            {{ account.name }} ·
                            {{ formatRupiah(account.balance) }}
                        </option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <Label for="trx-category" class="text-xs font-semibold"
                        >Kategori</Label
                    >
                    <select
                        id="trx-category"
                        v-model="categoryId"
                        :class="selectClass"
                    >
                        <option
                            v-for="item in selectableCategories"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.name }}
                        </option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <Label for="trx-date" class="text-xs font-semibold"
                        >Tanggal</Label
                    >
                    <Input
                        id="trx-date"
                        v-model="date"
                        type="date"
                        required
                        class="min-h-11 rounded-xl"
                    />
                </div>

                <div class="space-y-1.5">
                    <Label for="trx-note" class="text-xs font-semibold"
                        >Deskripsi</Label
                    >
                    <Input
                        id="trx-note"
                        v-model="note"
                        class="min-h-11 rounded-xl"
                        placeholder="cth. Belanja mingguan"
                    />
                </div>
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
                    Simpan transaksi
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

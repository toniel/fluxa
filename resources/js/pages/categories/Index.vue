<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Pencil, Plus, Tags, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Button } from '@/components/ui/button';
import { categoryIcon } from '@/lib/categoryIcons';
import { notYet } from '@/lib/notYet';
import { index as categoriesRoute } from '@/routes/categories';

type Category = {
    id: number;
    name: string;
    type: 'income' | 'expense';
    icon: string;
    is_default: boolean;
    usage: number;
};

const props = defineProps<{ state: string; categories: Category[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Kategori', href: categoriesRoute() }] },
});

const expenseCount = computed(
    () => props.categories.filter((c) => c.type === 'expense').length,
);
const incomeCount = computed(
    () => props.categories.filter((c) => c.type === 'income').length,
);

// Pengeluaran duluan: itu yang paling sering dicek saat menata anggaran.
const tab = ref<'expense' | 'income'>('expense');

const visible = computed(() =>
    props.categories.filter((c) => c.type === tab.value),
);

/**
 * bg-chart-out sengaja hampir sama gelapnya di kedua tema (ia juga dipakai
 * sebagai warna mark chart), sehingga tidak ada token teks bawaan yang lolos
 * 4.5:1 di keduanya sekaligus: text-foreground lolos light (5.57:1) tapi
 * gagal dark (2.70:1); text-background sebaliknya (2.70:1 light, 5.79:1
 * dark). Nilai literal ini diukur terhadap kedua warna chart-out dan lolos
 * pada keduanya (5.57:1 dan 5.35:1).
 */
const activeExpenseTextClass = 'text-[#16211c]';
</script>

<template>
    <Head title="Kategori" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight">Kategori</h1>
                <p class="text-muted-foreground text-sm">
                    Kelompokkan transaksi supaya laporan lebih jelas
                </p>
            </div>
            <Button
                class="min-h-11 shrink-0"
                @click="notYet('Tambah kategori')"
            >
                <Plus class="size-4" aria-hidden="true" />
                Tambah
            </Button>
        </header>

        <ErrorState
            v-if="state === 'failed'"
            title="Daftar kategori gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <EmptyState
            v-else-if="!categories.length"
            :icon="Tags"
            title="Belum ada kategori"
            description="Kategori default biasanya dibuat otomatis saat tenant baru dibuat."
        >
            <Button class="min-h-11" @click="notYet('Tambah kategori')">
                <Plus class="size-4" aria-hidden="true" />
                Tambah kategori
            </Button>
        </EmptyState>

        <template v-else>
            <div
                class="bg-muted grid grid-cols-2 gap-1 rounded-xl p-1"
                role="tablist"
                aria-label="Jenis kategori"
            >
                <button
                    type="button"
                    role="tab"
                    class="focus-visible:ring-ring min-h-11 rounded-lg text-sm font-semibold focus-visible:ring-2 focus-visible:outline-none"
                    :class="
                        tab === 'expense'
                            ? `bg-chart-out ${activeExpenseTextClass} shadow-sm`
                            : 'text-muted-foreground'
                    "
                    :aria-selected="tab === 'expense'"
                    @click="tab = 'expense'"
                >
                    Pengeluaran ({{ expenseCount }})
                </button>
                <button
                    type="button"
                    role="tab"
                    class="focus-visible:ring-ring min-h-11 rounded-lg text-sm font-semibold focus-visible:ring-2 focus-visible:outline-none"
                    :class="
                        tab === 'income'
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground'
                    "
                    :aria-selected="tab === 'income'"
                    @click="tab = 'income'"
                >
                    Pemasukan ({{ incomeCount }})
                </button>
            </div>

            <EmptyState
                v-if="!visible.length"
                title="Belum ada kategori di sini"
                :description="`Tambahkan kategori ${tab === 'expense' ? 'pengeluaran' : 'pemasukan'} pertama.`"
            />

            <ul v-else class="bg-card divide-y rounded-2xl border px-3">
                <li
                    v-for="category in visible"
                    :key="category.id"
                    class="flex items-center gap-3 py-3"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-full"
                        :class="
                            tab === 'expense'
                                ? 'bg-chart-out/12 text-money-out'
                                : 'bg-money-in/12 text-money-in'
                        "
                        aria-hidden="true"
                    >
                        <component
                            :is="categoryIcon(category.icon)"
                            class="size-5"
                        />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ category.name }}
                        </p>
                        <p class="text-muted-foreground text-xs">
                            {{ category.usage }} transaksi
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-11"
                            :aria-label="`Ubah kategori ${category.name}`"
                            @click="notYet(`Ubah kategori ${category.name}`)"
                        >
                            <Pencil class="size-4" aria-hidden="true" />
                        </Button>
                        <!--
                            Kategori bawaan tetap bisa dihapus di rujukan: tenant
                            berhak menghilangkan kategori yang tidak dipakainya.
                            Yang dijaga bukan penghapusan, tapi transaksi lama
                            yang masih memakainya - itu urusan backend nanti.
                        -->
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-money-out hover:text-money-out size-11"
                            :aria-label="`Hapus kategori ${category.name}`"
                            @click="notYet(`Hapus kategori ${category.name}`)"
                        >
                            <Trash2 class="size-4" aria-hidden="true" />
                        </Button>
                    </div>
                </li>
            </ul>
        </template>
    </div>
</template>

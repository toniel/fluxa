<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus, Tags } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import PageHeader from '@/components/fluxa/PageHeader.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as categoriesRoute } from '@/routes/categories';

type Category = {
    id: number;
    name: string;
    type: 'income' | 'expense';
    is_default: boolean;
    usage: number;
};

const props = defineProps<{ state: string; categories: Category[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Kategori', href: categoriesRoute() }] },
});

const groups = computed(() => [
    {
        key: 'income' as const,
        label: 'Pemasukan',
        items: props.categories.filter((c) => c.type === 'income'),
    },
    {
        key: 'expense' as const,
        label: 'Pengeluaran',
        items: props.categories.filter((c) => c.type === 'expense'),
    },
]);
</script>

<template>
    <Head title="Kategori" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <PageHeader
            title="Kategori"
            description="Pengelompokan transaksi. Kategori bawaan bisa diubah atau dihapus."
        >
            <template #action>
                <Button class="min-h-11" @click="notYet('Tambah kategori')">
                    <Plus class="size-4" aria-hidden="true" />
                    Tambah
                </Button>
            </template>
        </PageHeader>

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
            <section v-for="group in groups" :key="group.key" class="space-y-2">
                <h2 class="text-muted-foreground px-1 text-xs font-medium">
                    {{ group.label }}
                </h2>
                <ul class="bg-card divide-y rounded-lg border">
                    <li
                        v-for="category in group.items"
                        :key="category.id"
                        class="flex items-center justify-between gap-3 p-3"
                    >
                        <span class="flex min-w-0 items-center gap-2">
                            <span class="truncate font-medium">{{
                                category.name
                            }}</span>
                            <Badge
                                v-if="category.is_default"
                                variant="secondary"
                                >Bawaan</Badge
                            >
                        </span>
                        <span class="flex shrink-0 items-center gap-2">
                            <span class="text-muted-foreground text-xs"
                                >{{ category.usage }}x</span
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="min-h-11 px-2 text-xs md:min-h-9"
                                @click="notYet('Ubah kategori')"
                            >
                                Ubah
                            </Button>
                        </span>
                    </li>
                </ul>
            </section>
        </template>
    </div>
</template>

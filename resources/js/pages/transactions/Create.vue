<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TransactionForm from './Form.vue';
import { index, store } from '@/routes/transactions';

defineProps<{
    accounts: { id: number; name: string; balance: string }[];
    categories: { id: number; name: string; type: string }[];
    types: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transaksi', href: index.url() },
            { title: 'Transaksi Baru' },
        ],
    },
});
</script>

<template>
    <Head title="Transaksi Baru" />

    <TransactionForm
        :action="store.url()"
        :accounts="accounts"
        :categories="categories"
        :types="types"
    />
</template>

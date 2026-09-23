<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TransactionForm from './Form.vue';
import { index, update } from '@/routes/transactions';

const props = defineProps<{
    transaction: App.Data.TransactionData;
    accounts: { id: number; name: string; balance: string }[];
    categories: { id: number; name: string; type: string }[];
    types: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transaksi', href: index.url() },
            { title: 'Ubah' },
        ],
    },
});
</script>

<template>
    <Head :title="`Ubah transaksi`" />

    <TransactionForm
        :action="update.url(props.transaction.id)"
        method="put"
        :accounts="accounts"
        :categories="categories"
        :types="types"
        :transaction="transaction"
    />
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TransferForm from './Form.vue';
import { index, store } from '@/routes/transfers';

defineProps<{
    accounts: { id: number; name: string; type: string; balance: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transfer', href: index.url() },
            { title: 'Transfer Baru' },
        ],
    },
});
</script>

<template>
    <Head title="Transfer Baru" />

    <TransferForm :action="store.url()" :accounts="accounts" />
</template>

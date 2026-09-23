<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TransferForm from './Form.vue';
import { index, update } from '@/routes/transfers';

const props = defineProps<{
    transfer: App.Data.TransferData;
    accounts: { id: number; name: string; type: string; balance: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transfer', href: index.url() },
            { title: 'Ubah' },
        ],
    },
});
</script>

<template>
    <Head title="Ubah transfer" />

    <TransferForm
        :action="update.url(props.transfer.id)"
        method="put"
        :accounts="accounts"
        :transfer="transfer"
    />
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AccountForm from './Form.vue';
import { index, update } from '@/routes/accounts';

defineProps<{
    account: App.Data.AccountData;
    types: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kantong', href: index.url() },
            { title: 'Ubah' },
        ],
    },
});
</script>

<template>
    <Head :title="`Ubah ${account.name}`" />

    <AccountForm
        :action="update.url(account.id)"
        method="put"
        :types="types"
        :account="account"
    />
</template>

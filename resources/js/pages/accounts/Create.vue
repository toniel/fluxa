<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AccountForm from './Form.vue';
import { index, store } from '@/routes/accounts';

defineProps<{
    types: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kantong', href: index.url() },
            { title: 'Kantong Baru' },
        ],
    },
});
</script>

<template>
    <Head title="Kantong Baru" />

    <AccountForm :action="store.url()" :types="types" />
</template>

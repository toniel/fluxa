<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CategoryForm from './Form.vue';
import { index, store } from '@/routes/categories';

defineProps<{
    types: string[];
    icons: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kategori', href: index.url() },
            { title: 'Kategori Baru' },
        ],
    },
});
</script>

<template>
    <Head title="Kategori Baru" />

    <CategoryForm :action="store.url()" :types="types" :icons="icons" />
</template>

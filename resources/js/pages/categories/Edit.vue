<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CategoryForm from './Form.vue';
import { index, update } from '@/routes/categories';

const props = defineProps<{
    category: App.Data.CategoryData;
    types: string[];
    icons: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kategori', href: index.url() },
            { title: 'Ubah' },
        ],
    },
});
</script>

<template>
    <Head :title="`Ubah ${props.category.name}`" />

    <CategoryForm
        :action="update.url(props.category.id)"
        method="put"
        :types="types"
        :icons="icons"
        :category="category"
    />
</template>

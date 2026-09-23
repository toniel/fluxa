<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

const selected = defineModel<number | null>({ required: true });

defineProps<{
    accounts: { id: number; name: string }[];
    error?: string;
}>();
</script>

<template>
    <div class="grid gap-2">
        <Label for="transaction-source">Bayar dari</Label>
        <select
            id="transaction-source"
            v-model="selected"
            class="border-input bg-card focus-visible:ring-ring min-h-11 w-full rounded-lg border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none"
        >
            <option :value="null" disabled>Pilih kantong sumber</option>
            <option
                v-for="account in accounts"
                :key="account.id"
                :value="account.id"
            >
                {{ account.name }}
            </option>
        </select>
        <InputError :message="error" />
    </div>
</template>

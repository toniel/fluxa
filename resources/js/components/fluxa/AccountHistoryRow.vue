<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeftRight, ReceiptText } from '@lucide/vue';
import { computed } from 'vue';
import IconBadge from '@/components/fluxa/IconBadge.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { show as showTransactionRoute } from '@/routes/transactions';
import { edit as editTransferRoute } from '@/routes/transfers';

const props = defineProps<{
    entry: App.Data.AccountHistoryData;
}>();

// Baris transaksi selalu bisa dibuka (view terbuka semua role); baris
// transfer menaut ke halaman ubah hanya bila boleh mengubahnya.
const href = computed<string | null>(() => {
    if (props.entry.kind === 'transaction') {
        return showTransactionRoute.url(props.entry.ref_id);
    }

    return props.entry.can_edit
        ? editTransferRoute.url(props.entry.ref_id)
        : null;
});

// Backend hanya mengirim 'in'/'out'; cast menjaga kontrak komponen.
const direction = computed(() => props.entry.direction as 'in' | 'out');
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href ?? undefined"
        class="flex items-center gap-3 py-3"
    >
        <IconBadge
            :icon="entry.kind === 'transaction' ? ReceiptText : ArrowLeftRight"
            :tone="direction"
        />

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium">{{ entry.title }}</p>
            <p class="text-muted-foreground truncate text-xs">
                {{ entry.subtitle }} · {{ entry.creator_name }}
            </p>
        </div>

        <MoneyText
            :value="entry.amount"
            :direction="direction"
            signed
            class="shrink-0 text-sm font-semibold"
        />
    </component>
</template>

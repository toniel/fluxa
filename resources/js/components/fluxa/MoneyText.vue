<script setup lang="ts">
import { computed } from 'vue';
import { formatRupiah, formatRupiahCompact } from '@/lib/currency';

const props = withDefaults(
    defineProps<{
        value: number | string;
        direction?: 'in' | 'out' | 'neutral';
        /** Tampilkan tanda + / - di depan nominal. */
        signed?: boolean;
        compact?: boolean;
    }>(),
    { direction: 'neutral', signed: false, compact: false },
);

const formatted = computed(() =>
    props.compact
        ? formatRupiahCompact(props.value)
        : formatRupiah(props.value),
);

const sign = computed(() => {
    if (!props.signed || props.direction === 'neutral') {
        return '';
    }

    return props.direction === 'in' ? '+' : '−';
});

const tone = computed(() => {
    switch (props.direction) {
        case 'in':
            return 'text-money-in';
        case 'out':
            return 'text-money-out';
        default:
            return '';
    }
});
</script>

<template>
    <span class="font-numeric tabular-nums" :class="tone">
        <span v-if="sign" aria-hidden="true">{{ sign }}</span
        >{{ formatted }}
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Eye, EyeOff } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useMaskedMoney } from '@/composables/useMaskedMoney';

const { isMasked, toggleMaskMoney } = useMaskedMoney();

const label = computed(() =>
    isMasked.value ? 'Tampilkan nominal' : 'Sembunyikan nominal',
);
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        class="shrink-0"
        :aria-label="label"
        :aria-pressed="isMasked"
        :title="label"
        @click="toggleMaskMoney"
    >
        <Eye v-if="isMasked" class="size-5" aria-hidden="true" />
        <EyeOff v-else class="size-5" aria-hidden="true" />
    </Button>
</template>

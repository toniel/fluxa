<script setup lang="ts">
import { watch } from 'vue';
import { CurrencyDisplay, useCurrencyInput } from 'vue-currency-input';
import type { CurrencyInputOptions } from 'vue-currency-input';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue: number | null;
        options?: CurrencyInputOptions;
        id?: string;
        name?: string;
        placeholder?: string;
        required?: boolean;
        readonly?: boolean;
        disabled?: boolean;
        autocomplete?: string;
        ariaReadonly?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    {
        options: undefined,
        id: undefined,
        name: undefined,
        placeholder: '0',
        required: false,
        readonly: false,
        disabled: false,
        autocomplete: 'off',
        ariaReadonly: undefined,
        class: undefined,
    },
);

defineEmits<{
    (e: 'update:modelValue', payload: number | null): void;
}>();

const defaults: CurrencyInputOptions = {
    currency: 'IDR',
    locale: 'id-ID',
    precision: { min: 0, max: 2 },
    currencyDisplay: CurrencyDisplay.hidden,
    hideCurrencySymbolOnFocus: true,
    hideGroupingSeparatorOnFocus: false,
    hideNegligibleDecimalDigitsOnFocus: false,
    useGrouping: true,
    valueRange: { min: 0, max: 999999999999.99 },
};

const { inputRef, setOptions, setValue } = useCurrencyInput({
    ...defaults,
    ...props.options,
});

watch(
    () => props.modelValue,
    (value) => {
        setValue(value);
    },
);

watch(
    () => props.options,
    (options) => {
        setOptions({ ...defaults, ...options });
    },
);
</script>

<template>
    <input
        ref="inputRef"
        data-slot="input"
        type="text"
        inputmode="decimal"
        :id="id"
        :name="name"
        :placeholder="placeholder"
        :required="required"
        :readonly="readonly"
        :disabled="disabled"
        :autocomplete="autocomplete"
        :aria-readonly="ariaReadonly"
        :class="
            cn(
                'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                props.class,
            )
        "
    />
</template>

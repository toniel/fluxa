<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import CurrencyInput from '@/components/fluxa/CurrencyInput.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export interface CreditDetailModel {
    credit_limit: number | null;
    billing_cycle_start_day?: number;
    billing_cycle_end_day?: number;
    payment_due_offset_days?: number;
    default_interest_rate_monthly: string;
    default_admin_fee_percentage: string;
}

const model = defineModel<CreditDetailModel>({ required: true });

defineProps<{
    errors: Record<string, string | undefined>;
}>();
</script>

<template>
    <fieldset class="space-y-4">
        <legend class="text-sm leading-none font-medium">
            Pengaturan tagihan
        </legend>

        <div class="grid gap-2">
            <Label for="account-limit">Limit kredit (opsional)</Label>
            <div class="relative">
                <span
                    class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm font-medium"
                    aria-hidden="true"
                >
                    Rp
                </span>
                <CurrencyInput
                    id="account-limit"
                    v-model="model.credit_limit"
                    name="credit_limit"
                    class="font-numeric min-h-11 pr-4 pl-10 text-lg font-bold tabular-nums"
                    placeholder="0"
                    autocomplete="off"
                />
            </div>
            <InputError :message="errors.credit_limit" />
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="grid gap-2">
                <Label for="account-cycle-end">Tutup siklus (tgl)</Label>
                <Input
                    id="account-cycle-end"
                    v-model="model.billing_cycle_end_day"
                    name="billing_cycle_end_day"
                    type="number"
                    min="1"
                    max="31"
                    required
                    class="min-h-11"
                    placeholder="15"
                />
                <InputError :message="errors.billing_cycle_end_day" />
            </div>
            <div class="grid gap-2">
                <Label for="account-due-offset">Jatuh tempo (+hari)</Label>
                <Input
                    id="account-due-offset"
                    v-model="model.payment_due_offset_days"
                    name="payment_due_offset_days"
                    type="number"
                    min="0"
                    max="93"
                    required
                    class="min-h-11"
                    placeholder="5"
                />
                <InputError :message="errors.payment_due_offset_days" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="grid gap-2">
                <Label for="account-cycle-start">Buka siklus (tgl)</Label>
                <Input
                    id="account-cycle-start"
                    v-model="model.billing_cycle_start_day"
                    name="billing_cycle_start_day"
                    type="number"
                    min="1"
                    max="31"
                    required
                    class="min-h-11"
                    placeholder="16"
                />
                <InputError :message="errors.billing_cycle_start_day" />
            </div>
            <div class="grid gap-2">
                <Label for="account-interest">Bunga/bln (%)</Label>
                <Input
                    id="account-interest"
                    v-model="model.default_interest_rate_monthly"
                    name="default_interest_rate_monthly"
                    inputmode="decimal"
                    class="min-h-11"
                    placeholder="0"
                    autocomplete="off"
                />
                <InputError :message="errors.default_interest_rate_monthly" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="account-admin-fee">Biaya admin (%)</Label>
            <Input
                id="account-admin-fee"
                v-model="model.default_admin_fee_percentage"
                name="default_admin_fee_percentage"
                inputmode="decimal"
                class="min-h-11"
                placeholder="0"
                autocomplete="off"
            />
            <InputError :message="errors.default_admin_fee_percentage" />
        </div>
    </fieldset>
</template>

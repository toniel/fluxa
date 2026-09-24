<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as tenantsRoute, store as storeRoute } from '@/routes/tenants';

const form = useForm({ name: '' });

// transform() merusak tipe form.errors, baca lewat cast (lihat CRUD_FLOW §6).
const errorFor = computed<Record<string, string | undefined>>(
    () => form.errors as Record<string, string | undefined>,
);

function submit(): void {
    form.post(storeRoute.url());
}
</script>

<template>
    <Head title="Tenant baru" />

    <div class="bg-background min-h-svh px-4 py-10">
        <div class="mx-auto w-full max-w-md space-y-6">
            <header class="space-y-2">
                <AppLogoIcon class="size-8" />
                <h1 class="text-xl font-semibold tracking-tight">
                    Buat tenant baru
                </h1>
                <p class="text-muted-foreground text-sm">
                    Satu ruang keuangan untuk satu keluarga atau komunitas. Kamu
                    otomatis jadi pemiliknya.
                </p>
            </header>

            <form
                class="bg-card space-y-4 rounded-2xl border p-4"
                novalidate
                @submit.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="tenant-name">Nama tenant</Label>
                    <Input
                        id="tenant-name"
                        v-model="form.name"
                        name="name"
                        required
                        class="min-h-11"
                        placeholder="Mis. Keluarga Budi, RT 05"
                        autocomplete="organization"
                    />
                    <p class="text-muted-foreground text-xs">
                        Alamat akses acak dibuatkan otomatis, bisa diganti
                        sendiri di paket Pro.
                    </p>
                    <InputError :message="errorFor.name" />
                </div>

                <Button
                    type="submit"
                    class="min-h-11 w-full"
                    :disabled="form.processing"
                >
                    Buat dan buka
                </Button>

                <Button as-child variant="ghost" class="min-h-11 w-full">
                    <Link :href="tenantsRoute.url()">Kembali</Link>
                </Button>
            </form>
        </div>
    </div>
</template>

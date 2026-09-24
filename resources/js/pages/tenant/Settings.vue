<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { settings as settingsRoute } from '@/routes/tenant';
import {
    destroy as destroyRoute,
    update as updateRoute,
} from '@/routes/tenant/settings';

const props = defineProps<{
    tenant: App.Data.TenantData;
    plan: { name: string; slug: string };
    can: { update: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pengaturan', href: settingsRoute.url() }],
    },
});

const form = useForm({
    name: props.tenant.name,
    subdomain: props.tenant.subdomain ?? '',
});

// Subdomain pilihan sendiri adalah fitur Pro; Free terkunci di alamat acak.
const canCustomizeSubdomain = computed(() => props.plan.slug !== 'free');

// transform() merusak tipe form.errors, baca lewat cast (lihat CRUD_FLOW §6).
const errorFor = computed<Record<string, string | undefined>>(
    () => form.errors as Record<string, string | undefined>,
);

function submit(): void {
    form.patch(updateRoute.url(), { preserveScroll: true });
}

const dateLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

// Kolom bertipe date dibaca sebagai Y-m-d tanpa offset, jadi pin jam tengah
// malam lokal supaya format tidak mundur sehari di zona WIB.
const createdLabel = dateLabel.format(
    new Date(`${props.tenant.created_at}T00:00:00`),
);

const deleteForm = useForm({});
const deleteOpen = ref(false);

function confirmDelete(): void {
    deleteForm.delete(destroyRoute.url());
}
</script>

<template>
    <Head title="Pengaturan tenant" />

    <div class="space-y-4 p-4">
        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            title="Hapus tenant"
            :description="`Tenant \u201C${tenant.name}\u201D beserta semua kantong, transaksi, dan anggota akan dihapus permanen.`"
            confirm-label="Hapus tenant"
            @confirm="confirmDelete"
        />

        <header>
            <h1 class="text-xl font-bold tracking-tight">Pengaturan tenant</h1>
            <p class="text-muted-foreground text-sm">
                Identitas dan alamat akses tenant
            </p>
        </header>

        <form
            v-if="can.update"
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
                    autocomplete="organization"
                />
                <p class="text-muted-foreground text-xs">
                    Nama ini terlihat oleh seluruh anggota.
                </p>
                <InputError :message="errorFor.name" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between gap-2">
                    <Label for="tenant-subdomain">Subdomain</Label>
                    <span
                        v-if="!canCustomizeSubdomain"
                        class="text-money-out text-xs font-medium"
                        >Khusus Pro</span
                    >
                </div>
                <div class="flex items-center gap-2">
                    <Input
                        id="tenant-subdomain"
                        v-model="form.subdomain"
                        name="subdomain"
                        class="min-h-11"
                        :readonly="!canCustomizeSubdomain"
                        :aria-readonly="!canCustomizeSubdomain"
                        autocomplete="off"
                        placeholder="nama-pilihanmu"
                    />
                    <span class="text-muted-foreground shrink-0 text-sm"
                        >.fluxa.test</span
                    >
                </div>
                <p class="text-muted-foreground text-xs">
                    {{
                        canCustomizeSubdomain
                            ? 'Huruf, angka, dan strip. Alamat lama tetap bisa dibuka (dialihkan).'
                            : 'Alamat pilihan sendiri tersedia di paket Pro.'
                    }}
                </p>
                <InputError :message="errorFor.subdomain" />
            </div>

            <Button
                type="submit"
                class="min-h-11 w-full sm:w-auto"
                :disabled="form.processing"
            >
                Simpan perubahan
            </Button>
        </form>

        <section class="bg-card space-y-3 rounded-2xl border p-4">
            <div class="space-y-1.5">
                <p class="text-xs font-semibold">Alamat akses</p>
                <div
                    class="bg-accent flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm"
                >
                    <span
                        class="bg-money-in size-2 shrink-0 rounded-full"
                        aria-hidden="true"
                    />
                    <span class="font-numeric min-w-0 truncate"
                        >https://{{ tenant.subdomain }}.fluxa.test</span
                    >
                </div>
                <p class="text-muted-foreground text-xs">
                    Alamat pilihan sendiri tersedia di paket Pro. Alamat lama
                    tidak dihapus saat diganti, jadi tautan yang sudah dibagikan
                    tetap bisa dibuka.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 border-t pt-4">
                <div>
                    <p class="text-muted-foreground text-xs">Mata uang</p>
                    <p class="text-sm font-medium">Rupiah (IDR)</p>
                </div>
                <div>
                    <p class="text-muted-foreground text-xs">Zona waktu</p>
                    <p class="text-sm font-medium">WIB (UTC+7)</p>
                </div>
            </div>
        </section>

        <section class="bg-card space-y-3 rounded-2xl border p-4">
            <h2 class="text-sm font-semibold">Info tenant</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-muted-foreground">Dibuat</dt>
                    <dd>{{ createdLabel }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-muted-foreground">Anggota</dt>
                    <dd>{{ tenant.member_count }} orang</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-muted-foreground">Plan</dt>
                    <dd>
                        <Badge
                            :variant="
                                plan.slug === 'free' ? 'secondary' : 'default'
                            "
                            >{{ plan.name }}</Badge
                        >
                    </dd>
                </div>
            </dl>
        </section>

        <section
            v-if="can.update"
            class="border-destructive/30 bg-destructive/5 space-y-3 rounded-2xl border p-4"
        >
            <h2 class="text-destructive text-sm font-semibold">
                Zona berbahaya
            </h2>
            <p class="text-muted-foreground text-sm">
                Menghapus tenant akan menghapus semua kantong, transaksi, dan
                {{ tenant.member_count }} anggota. Tindakan ini tidak bisa
                dibatalkan.
            </p>
            <Button
                variant="destructive"
                class="min-h-11 w-full"
                @click="deleteOpen = true"
            >
                <Trash2 class="size-4" aria-hidden="true" />
                Hapus tenant
            </Button>
        </section>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/fluxa/PageHeader.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { notYet } from '@/lib/notYet';
import { settings as settingsRoute } from '@/routes/tenant';

type Tenant = {
    name: string;
    subdomain: string;
    member_count: number;
};

defineProps<{ state: string; tenant: Tenant }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Pengaturan', href: settingsRoute() }] },
});
</script>

<template>
    <Head title="Pengaturan tenant" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <PageHeader
            title="Pengaturan tenant"
            description="Identitas tenant dan alamat yang dipakai anggotanya."
        />

        <form
            class="bg-card space-y-4 rounded-lg border p-4"
            @submit.prevent="notYet('Simpan pengaturan')"
        >
            <div class="space-y-2">
                <Label for="tenant-name">Nama tenant</Label>
                <Input
                    id="tenant-name"
                    :default-value="tenant.name"
                    class="min-h-11"
                    autocomplete="organization"
                />
                <p class="text-muted-foreground text-xs">
                    Nama ini terlihat oleh seluruh anggota.
                </p>
            </div>

            <div class="space-y-2">
                <Label for="tenant-subdomain">Alamat</Label>
                <div class="flex items-center gap-2">
                    <Input
                        id="tenant-subdomain"
                        :default-value="tenant.subdomain"
                        class="min-h-11"
                        disabled
                    />
                    <span class="text-muted-foreground shrink-0 text-sm"
                        >.fluxa.test</span
                    >
                </div>
                <p class="text-muted-foreground text-xs">
                    Alamat pilihan sendiri tersedia di paket Pro. Alamat lama
                    tidak dihapus saat diganti, jadi tautan yang sudah dibagikan
                    tetap bisa dibuka.
                </p>
            </div>

            <Button type="submit" class="min-h-11">Simpan perubahan</Button>
        </form>

        <section class="border-destructive/30 space-y-3 rounded-lg border p-4">
            <h2 class="text-sm font-semibold">Hapus tenant</h2>
            <p class="text-muted-foreground text-sm">
                Seluruh kantong, transaksi, dan riwayat milik
                {{ tenant.member_count }} anggota ikut terhapus. Tindakan ini
                tidak bisa dibatalkan.
            </p>
            <Button
                variant="destructive"
                class="min-h-11"
                @click="notYet('Hapus tenant')"
            >
                Hapus tenant ini
            </Button>
        </section>
    </div>
</template>

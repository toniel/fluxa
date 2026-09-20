<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CheckCircle2, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { notYet } from '@/lib/notYet';
import { settings as settingsRoute } from '@/routes/tenant';

type Tenant = {
    name: string;
    subdomain: string;
    created_at: string;
    member_count: number;
};

type Plan = { name: string; slug: string };

const props = defineProps<{ state: string; tenant: Tenant; plan: Plan }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Pengaturan', href: settingsRoute() }] },
});

const name = ref(props.tenant.name);

// Subdomain pilihan sendiri adalah fitur Pro (PRD: subdomain acak untuk
// Free, custom untuk subscriber) - bukan dikunci sembarangan.
const canCustomizeSubdomain = props.plan.slug !== 'free';

const dateLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});
</script>

<template>
    <Head title="Pengaturan tenant" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <header>
            <h1 class="text-xl font-bold tracking-tight">Pengaturan tenant</h1>
            <p class="text-muted-foreground text-sm">
                Identitas dan alamat akses tenant
            </p>
        </header>

        <ErrorState
            v-if="state === 'failed'"
            title="Pengaturan gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <template v-else>
            <form
                class="bg-card space-y-4 rounded-2xl border p-4"
                @submit.prevent="notYet('Simpan pengaturan tenant')"
            >
                <div class="space-y-1.5">
                    <Label for="tenant-name" class="text-xs font-semibold"
                        >Nama tenant</Label
                    >
                    <Input
                        id="tenant-name"
                        v-model="name"
                        class="min-h-11 rounded-xl"
                        autocomplete="organization"
                        required
                    />
                    <p class="text-muted-foreground text-xs">
                        Nama ini terlihat oleh seluruh anggota.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between gap-2">
                        <Label
                            for="tenant-subdomain"
                            class="text-xs font-semibold"
                            >Subdomain</Label
                        >
                        <span
                            v-if="!canCustomizeSubdomain"
                            class="text-money-out text-xs font-medium"
                            >Custom · Pro</span
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <Input
                            id="tenant-subdomain"
                            :model-value="tenant.subdomain"
                            class="min-h-11 rounded-xl"
                            :disabled="!canCustomizeSubdomain"
                        />
                        <span class="text-muted-foreground shrink-0 text-sm"
                            >.fluxa.test</span
                        >
                    </div>

                    <!--
                        Alamat sekarang ditampilkan apa adanya, bukan pratinjau
                        "sedang dicek ketersediaan": pada paket Free field-nya
                        terkunci, jadi tidak ada apa pun untuk dicek.
                    -->
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

                    <p
                        v-if="!canCustomizeSubdomain"
                        class="text-muted-foreground text-xs"
                    >
                        Alamat pilihan sendiri tersedia di paket Pro. Alamat
                        lama tidak dihapus saat diganti, jadi tautan yang sudah
                        dibagikan tetap bisa dibuka.
                    </p>
                </div>

                <!--
                    Mata uang dan zona waktu ditampilkan sebagai info, bukan
                    dropdown: PRD menunda multi-currency, dan zona waktu
                    terkunci Asia/Jakarta. Dropdown yang tidak mengubah apa pun
                    adalah kontrol mati - lebih jujur menampilkannya sebagai
                    fakta.
                -->
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

                <Button type="submit" class="min-h-11 w-full">
                    Simpan perubahan
                </Button>
            </form>

            <section class="bg-card space-y-3 rounded-2xl border p-4">
                <h2 class="text-sm font-semibold">Info tenant</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted-foreground">Dibuat</dt>
                        <dd>
                            {{ dateLabel.format(new Date(tenant.created_at)) }}
                        </dd>
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
                                    plan.slug === 'free'
                                        ? 'secondary'
                                        : 'default'
                                "
                                >{{ plan.name }}</Badge
                            >
                        </dd>
                    </div>
                </dl>
            </section>

            <section
                class="border-destructive/30 bg-destructive/5 space-y-3 rounded-2xl border p-4"
            >
                <h2 class="text-destructive text-sm font-semibold">
                    Zona berbahaya
                </h2>
                <p class="text-muted-foreground text-sm">
                    Menghapus tenant akan menghapus semua kantong, transaksi,
                    dan {{ tenant.member_count }} anggota. Tindakan ini tidak
                    bisa dibatalkan.
                </p>
                <Button
                    variant="destructive"
                    class="min-h-11 w-full"
                    @click="notYet('Hapus tenant')"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                    Hapus tenant
                </Button>
            </section>
        </template>
    </div>
</template>

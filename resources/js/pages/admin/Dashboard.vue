<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminUsers } from '@/routes/admin/users';

type Totals = {
    users: number;
    users_new_7d: number;
    tenants: number;
    subscriptions_active: number;
    mrr: string;
};

type Signup = { label: string; count: number };
type PlanRow = {
    name: string;
    slug: string;
    price: string;
    active_count: number;
};
type TenantRow = {
    id: number;
    name: string;
    subdomain: string | null;
    members_count: number;
    created_at: string;
};

const props = defineProps<{
    totals: Totals;
    signups: Signup[];
    plans: PlanRow[];
    recent_tenants: TenantRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin', href: adminDashboard.url() }],
    },
});

const maxSignups = computed(() =>
    Math.max(...props.signups.map((s) => s.count), 1),
);

const dayLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

// Kolom bertipe date dibaca sebagai Y-m-d tanpa offset, jadi pin jam tengah
// malam lokal supaya format tidak mundur sehari di zona WIB.
const asLocalDate = (ymd: string): Date => new Date(`${ymd}T00:00:00`);

const stats = computed(() => [
    {
        label: 'Pengguna',
        value: String(props.totals.users),
        hint: `+${props.totals.users_new_7d} seminggu terakhir`,
    },
    {
        label: 'Tenant',
        value: String(props.totals.tenants),
        hint: 'ruang keuangan aktif',
    },
    {
        label: 'Langganan aktif',
        value: String(props.totals.subscriptions_active),
        hint: 'status active',
    },
]);
</script>

<template>
    <Head title="Admin" />

    <div class="bg-background min-h-svh">
        <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="size-7" aria-hidden="true" />
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">
                            Admin Fluxa
                        </h1>
                        <p class="text-muted-foreground text-sm">
                            Statistik lintas tenant
                        </p>
                    </div>
                </div>
                <Link
                    :href="adminUsers.url()"
                    class="text-money-in focus-visible:ring-ring inline-flex min-h-11 items-center rounded px-2 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                >
                    Daftar pengguna
                </Link>
            </header>

            <section
                class="grid grid-cols-2 gap-2 md:grid-cols-4"
                aria-label="Ringkasan"
            >
                <div
                    v-for="stat in stats"
                    :key="stat.label"
                    class="bg-card rounded-2xl border p-4"
                >
                    <p class="text-muted-foreground text-xs">
                        {{ stat.label }}
                    </p>
                    <p
                        class="font-numeric mt-1 text-2xl font-bold tabular-nums"
                    >
                        {{ stat.value }}
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-xs">
                        {{ stat.hint }}
                    </p>
                </div>
                <div class="bg-brand text-brand-foreground rounded-2xl p-4">
                    <p class="text-xs opacity-80">MRR</p>
                    <p
                        class="font-numeric mt-1 text-2xl font-bold tabular-nums"
                    >
                        <MoneyText :value="totals.mrr" />
                    </p>
                    <p class="mt-0.5 text-xs opacity-80">
                        langganan aktif × harga paket
                    </p>
                </div>
            </section>

            <section class="bg-card space-y-3 rounded-2xl border p-4">
                <h2 class="text-sm font-semibold">
                    Pendaftar 8 pekan terakhir
                </h2>
                <div
                    class="flex h-28 items-end gap-1.5"
                    role="img"
                    aria-label="Grafik pendaftar per pekan"
                >
                    <div
                        v-for="week in signups"
                        :key="week.label"
                        class="flex min-w-0 flex-1 flex-col items-center gap-1"
                    >
                        <span class="font-numeric text-[11px] tabular-nums">{{
                            week.count
                        }}</span>
                        <span
                            class="bg-brand w-full rounded-t"
                            :style="{
                                height: `${Math.max((week.count / maxSignups) * 72, week.count > 0 ? 4 : 0)}px`,
                            }"
                        />
                        <span class="text-muted-foreground text-[10px]">{{
                            week.label
                        }}</span>
                    </div>
                </div>
            </section>

            <section class="bg-card rounded-2xl border p-4">
                <h2 class="text-sm font-semibold">Paket</h2>
                <ul class="mt-2 divide-y">
                    <li
                        v-for="plan in plans"
                        :key="plan.slug"
                        class="flex items-baseline justify-between gap-3 py-2.5 text-sm"
                    >
                        <span class="font-medium">{{ plan.name }}</span>
                        <span class="text-muted-foreground">
                            <MoneyText :value="plan.price" compact />
                            · {{ plan.active_count }} aktif
                        </span>
                    </li>
                </ul>
            </section>

            <section class="bg-card rounded-2xl border p-4">
                <h2 class="text-sm font-semibold">Tenant terbaru</h2>
                <ul class="mt-2 divide-y">
                    <li
                        v-for="tenant in recent_tenants"
                        :key="tenant.id"
                        class="flex items-baseline justify-between gap-3 py-2.5 text-sm"
                    >
                        <span class="min-w-0">
                            <span class="block truncate font-medium">{{
                                tenant.name
                            }}</span>
                            <span
                                class="text-muted-foreground block truncate text-xs"
                            >
                                {{ tenant.subdomain }} ·
                                {{ tenant.members_count }} anggota
                            </span>
                        </span>
                        <span class="text-muted-foreground shrink-0 text-xs">
                            {{
                                dayLabel.format(asLocalDate(tenant.created_at))
                            }}
                        </span>
                    </li>
                </ul>
                <p
                    v-if="!recent_tenants.length"
                    class="text-muted-foreground py-2 text-sm"
                >
                    Belum ada tenant.
                </p>
            </section>
        </div>
    </div>
</template>

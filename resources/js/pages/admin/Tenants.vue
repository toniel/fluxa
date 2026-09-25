<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Building, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminUsers } from '@/routes/admin/users';
import {
    index as adminTenants,
    update as updateTenant,
} from '@/routes/admin/tenants';

type TenantRow = {
    id: number;
    name: string;
    subdomain: string | null;
    member_count: number;
    plan_slug: string | undefined;
    created_at: string;
};

type Paginated = {
    data: TenantRow[];
    current_page: number;
    last_page: number;
    total: number;
};

const props = defineProps<{
    tenants: Paginated;
}>();

const search = ref(
    new URLSearchParams(window.location.search).get('search') ?? '',
);

const pending = ref<TenantRow | null>(null);

const downgradeOpen = computed({
    get: () => pending.value !== null,
    set: (open: boolean) => {
        if (!open) {
            pending.value = null;
        }
    },
});

const plans: Record<string, { name: string; action: string }> = {
    free: { name: 'Free', action: 'Naik ke Pro' },
    'pro-monthly': { name: 'Pro', action: 'Turun ke Free' },
};

const planOf = computed(
    () => (tenant: TenantRow) =>
        plans[tenant.plan_slug ?? 'free'] ?? {
            name: tenant.plan_slug ?? '',
            action: 'Naik ke Pro',
        },
);

function applySearch(): void {
    router.get(
        adminTenants.url(),
        search.value ? { search: search.value } : {},
        {
            preserveState: true,
            replace: true,
        },
    );
}

function goTo(page: number): void {
    router.get(
        adminTenants.url(),
        { ...(search.value ? { search: search.value } : {}), page },
        { preserveState: true },
    );
}

function requestDowngrade(tenant: TenantRow): void {
    pending.value = tenant;
}

function confirmDowngrade(): void {
    const tenant = pending.value;

    if (!tenant) {
        return;
    }

    router.put(
        updateTenant.url(tenant.id),
        { plan_slug: 'free' },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
    pending.value = null;
}

function upgrade(tenant: TenantRow): void {
    router.put(
        updateTenant.url(tenant.id),
        { plan_slug: 'pro-monthly' },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Tenant" />

    <div class="bg-background min-h-svh">
        <div class="mx-auto w-full max-w-4xl space-y-4 p-4 md:p-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="size-7" aria-hidden="true" />
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">Tenant</h1>
                        <p class="text-muted-foreground text-sm">
                            {{ tenants.total }} tenant terdaftar
                        </p>
                    </div>
                </div>
                <Link
                    :href="adminDashboard.url()"
                    class="text-money-in focus-visible:ring-ring inline-flex min-h-11 items-center rounded px-2 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                >
                    Statistik
                </Link>
                <Link
                    :href="adminUsers.url()"
                    class="text-money-in focus-visible:ring-ring inline-flex min-h-11 items-center rounded px-2 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                >
                    Pengguna
                </Link>
            </header>

            <form class="relative" role="search" @submit.prevent="applySearch">
                <Search
                    class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                    aria-hidden="true"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="min-h-11 rounded-xl pl-9"
                    placeholder="Cari nama tenant"
                    aria-label="Cari tenant"
                />
            </form>

            <EmptyState
                v-if="!tenants.data.length"
                :icon="Search"
                title="Tidak ada yang cocok"
                description="Coba kata kunci lain."
            />

            <ul v-else class="bg-card divide-y rounded-2xl border px-3">
                <li
                    v-for="tenant in tenants.data"
                    :key="tenant.id"
                    class="flex flex-wrap items-center gap-3 py-3"
                >
                    <span
                        class="bg-accent flex size-10 shrink-0 items-center justify-center rounded-lg"
                        aria-hidden="true"
                    >
                        <Building class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ tenant.name }}
                        </p>
                        <p class="text-muted-foreground truncate text-xs">
                            {{ tenant.subdomain ?? 'tanpa domain' }} ·
                            {{ tenant.member_count }} anggota
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <Badge variant="secondary">
                            Paket {{ planOf(tenant).name }}
                        </Badge>
                        <Button
                            v-if="tenant.plan_slug === 'pro-monthly'"
                            variant="outline"
                            class="min-h-10 px-4"
                            @click="requestDowngrade(tenant)"
                        >
                            {{ planOf(tenant).action }}
                        </Button>
                        <Button
                            v-else
                            class="min-h-10 px-4"
                            @click="upgrade(tenant)"
                        >
                            {{ planOf(tenant).action }}
                        </Button>
                    </div>
                </li>
            </ul>

            <ConfirmDeleteDialog
                v-model:open="downgradeOpen"
                :title="pending ? `Turunkan ${pending.name} ke Free?` : ''"
                description="Subdomain kustom tidak berlaku lagi dan tenant kembali ke domain acak."
                confirm-label="Turunkan"
                @confirm="confirmDowngrade"
            />

            <div
                v-if="tenants.last_page > 1"
                class="flex items-center justify-between gap-3"
            >
                <Button
                    variant="outline"
                    class="min-h-11"
                    :disabled="tenants.current_page <= 1"
                    @click="goTo(tenants.current_page - 1)"
                >
                    Sebelumnya
                </Button>
                <p class="text-muted-foreground text-sm">
                    Halaman {{ tenants.current_page }} dari
                    {{ tenants.last_page }}
                </p>
                <Button
                    variant="outline"
                    class="min-h-11"
                    :disabled="tenants.current_page >= tenants.last_page"
                    @click="goTo(tenants.current_page + 1)"
                >
                    Berikutnya
                </Button>
            </div>
        </div>
    </div>
</template>

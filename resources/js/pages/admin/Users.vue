<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, ShieldCheck } from '@lucide/vue';
import { ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminUsers } from '@/routes/admin/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    is_super_admin: boolean;
    tenants_count: number;
    created_at: string;
};

type Paginated = {
    data: UserRow[];
    current_page: number;
    last_page: number;
    total: number;
};

const props = defineProps<{ users: Paginated }>();

const search = ref(
    new URLSearchParams(window.location.search).get('search') ?? '',
);

function applySearch(): void {
    router.get(adminUsers.url(), search.value ? { search: search.value } : {}, {
        preserveState: true,
        replace: true,
    });
}

function goTo(page: number): void {
    router.get(
        adminUsers.url(),
        {
            ...(search.value ? { search: search.value } : {}),
            page,
        },
        { preserveState: true },
    );
}

function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
</script>

<template>
    <Head title="Pengguna" />

    <div class="bg-background min-h-svh">
        <div class="mx-auto w-full max-w-4xl space-y-4 p-4 md:p-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="size-7" aria-hidden="true" />
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">
                            Pengguna
                        </h1>
                        <p class="text-muted-foreground text-sm">
                            {{ users.total }} akun terdaftar
                        </p>
                    </div>
                </div>
                <Link
                    :href="adminDashboard.url()"
                    class="text-money-in focus-visible:ring-ring inline-flex min-h-11 items-center rounded px-2 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                >
                    Statistik
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
                    placeholder="Cari nama atau email"
                    aria-label="Cari pengguna"
                />
            </form>

            <EmptyState
                v-if="!users.data.length"
                :icon="Search"
                title="Tidak ada yang cocok"
                description="Coba kata kunci lain."
            />

            <ul v-else class="bg-card divide-y rounded-2xl border px-3">
                <li
                    v-for="user in users.data"
                    :key="user.id"
                    class="flex items-center gap-3 py-3"
                >
                    <span
                        class="bg-accent flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                        aria-hidden="true"
                        >{{ initials(user.name) }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-x-1.5">
                            <span class="truncate text-sm font-medium">{{
                                user.name
                            }}</span>
                            <ShieldCheck
                                v-if="user.is_super_admin"
                                class="text-money-in size-4 shrink-0"
                                aria-label="Super-admin"
                            />
                        </p>
                        <p class="text-muted-foreground truncate text-xs">
                            {{ user.email }} · {{ user.tenants_count }} tenant
                        </p>
                    </div>
                </li>
            </ul>

            <div
                v-if="users.last_page > 1"
                class="flex items-center justify-between gap-3"
            >
                <Button
                    variant="outline"
                    class="min-h-11"
                    :disabled="users.current_page <= 1"
                    @click="goTo(users.current_page - 1)"
                >
                    Sebelumnya
                </Button>
                <p class="text-muted-foreground text-sm">
                    Halaman {{ users.current_page }} dari {{ users.last_page }}
                </p>
                <Button
                    variant="outline"
                    class="min-h-11"
                    :disabled="users.current_page >= users.last_page"
                    @click="goTo(users.current_page + 1)"
                >
                    Berikutnya
                </Button>
            </div>
        </div>
    </div>
</template>

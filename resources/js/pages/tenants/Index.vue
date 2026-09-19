<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ArrowRight, Building2 } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import { Badge } from '@/components/ui/badge';

type Membership = {
    id: number;
    name: string;
    subdomain: string | null;
    url: string;
    role: string | null;
};

defineProps<{ memberships: Membership[] }>();

const page = usePage();

const userName = computed(() => page.props.auth?.user?.name ?? '');

const roleLabel: Record<string, string> = {
    owner: 'Pemilik',
    admin: 'Admin',
    member: 'Anggota',
};
</script>

<template>
    <Head title="Pilih tenant" />

    <div class="bg-background min-h-svh px-4 py-10">
        <div class="mx-auto w-full max-w-md space-y-6">
            <header class="space-y-2">
                <AppLogoIcon class="size-8" />
                <h1 class="text-xl font-semibold tracking-tight">
                    Halo{{ userName ? `, ${userName}` : '' }}
                </h1>
                <p class="text-muted-foreground text-sm">
                    Pilih tenant yang mau dibuka. Tiap tenant punya kantong,
                    transaksi, dan anggotanya sendiri.
                </p>
            </header>

            <!--
                Tautan lintas origin, jadi <a> biasa: Inertia tidak bisa
                mengunjungi origin lain, dan muat ulang penuh justru yang
                diinginkan supaya tidak ada sisa state tenant sebelumnya.
            -->
            <ul v-if="memberships.length" class="space-y-2">
                <li v-for="membership in memberships" :key="membership.id">
                    <a
                        :href="membership.url"
                        class="bg-card hover:bg-accent focus-visible:ring-ring flex min-h-16 items-center gap-3 rounded-lg border p-3 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                    >
                        <span
                            class="bg-brand text-brand-foreground flex size-10 shrink-0 items-center justify-center rounded-md"
                            aria-hidden="true"
                        >
                            <Building2 class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium">{{
                                membership.name
                            }}</span>
                            <span
                                class="text-muted-foreground block truncate text-xs"
                                >{{ membership.subdomain }}.fluxa.test</span
                            >
                        </span>
                        <Badge v-if="membership.role" variant="secondary">{{
                            roleLabel[membership.role] ?? membership.role
                        }}</Badge>
                        <ArrowRight
                            class="text-muted-foreground size-4 shrink-0"
                            aria-hidden="true"
                        />
                    </a>
                </li>
            </ul>

            <EmptyState
                v-else
                :icon="Building2"
                title="Belum tergabung di tenant mana pun"
                description="Minta pemilik tenant mengundang alamat email kamu, atau buat tenant sendiri setelah fitur itu tersedia."
            />
        </div>
    </div>
</template>

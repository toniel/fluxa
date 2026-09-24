<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Membership = {
    id: number;
    name: string;
    role: string;
    subdomain: string;
    is_current: boolean;
};

const page = usePage();

const tenant = computed(
    () =>
        page.props.tenant as
            | { name: string; memberships: Membership[] }
            | undefined,
);

const current = computed(() =>
    tenant.value?.memberships?.find((m) => m.is_current),
);

const roleLabel: Record<string, string> = {
    owner: 'Pemilik',
    admin: 'Admin',
    member: 'Anggota',
};

function tenantUrl(subdomain: string): string {
    const { protocol, host } = window.location;
    const central = host.split('.').slice(1).join('.') || host;

    return `${protocol}//${subdomain}.${central}/dashboard`;
}
</script>

<template>
    <header
        v-if="tenant"
        class="bg-background/95 sticky top-0 z-30 flex items-center justify-between gap-3 px-4 py-3 backdrop-blur md:hidden"
    >
        <DropdownMenu>
            <DropdownMenuTrigger
                class="focus-visible:ring-ring bg-card flex min-h-11 max-w-[60%] items-center gap-2 rounded-full border px-3 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
            >
                <span class="truncate">{{ tenant.name }}</span>
                <ChevronsUpDown class="size-4 shrink-0 opacity-60" />
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-60" align="start">
                <DropdownMenuLabel class="text-muted-foreground text-xs">
                    Pindah tenant
                </DropdownMenuLabel>
                <DropdownMenuItem
                    v-for="membership in tenant.memberships ?? []"
                    :key="membership.id"
                    as-child
                >
                    <a
                        :href="tenantUrl(membership.subdomain)"
                        class="flex min-h-11 items-center gap-2"
                    >
                        <span class="flex-1 truncate">{{
                            membership.name
                        }}</span>
                        <Check
                            v-if="membership.is_current"
                            class="size-4 shrink-0"
                            aria-hidden="true"
                        />
                    </a>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <span
            v-if="current"
            class="bg-money-out/10 text-money-out shrink-0 rounded-full px-3 py-1 text-xs font-semibold"
        >
            {{ roleLabel[current.role] ?? current.role }}
        </span>
    </header>
</template>

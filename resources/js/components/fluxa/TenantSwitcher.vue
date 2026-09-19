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
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

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

const roleLabel: Record<string, string> = {
    owner: 'Pemilik',
    admin: 'Admin',
    member: 'Anggota',
};

/**
 * Pindah tenant adalah perpindahan origin, jadi ia memakai <a> biasa, bukan
 * <Link> Inertia: Inertia tidak bisa mengunjungi origin lain. Muat ulang penuh
 * juga yang membuang seluruh state halaman tenant sebelumnya.
 */
function tenantUrl(subdomain: string): string {
    const { protocol, host, port } = window.location;
    const central = host.split('.').slice(1).join('.') || host;

    return `${protocol}//${subdomain}.${central}${port ? '' : ''}/dashboard`;
}
</script>

<template>
    <SidebarMenu v-if="tenant">
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent min-h-11"
                    >
                        <div class="grid flex-1 text-left leading-tight">
                            <span class="text-[11px] opacity-70"
                                >Tenant aktif</span
                            >
                            <span class="truncate font-semibold">{{
                                tenant.name
                            }}</span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-60" align="start" side="bottom">
                    <DropdownMenuLabel class="text-muted-foreground text-xs">
                        Pindah tenant
                    </DropdownMenuLabel>
                    <DropdownMenuItem
                        v-for="membership in tenant.memberships"
                        :key="membership.id"
                        as-child
                    >
                        <a
                            :href="tenantUrl(membership.subdomain)"
                            class="flex min-h-11 items-center gap-2"
                        >
                            <span class="grid flex-1 leading-tight">
                                <span class="truncate">{{
                                    membership.name
                                }}</span>
                                <span class="text-muted-foreground text-xs">{{
                                    roleLabel[membership.role] ??
                                    membership.role
                                }}</span>
                            </span>
                            <Check
                                v-if="membership.is_current"
                                class="size-4 shrink-0"
                                aria-hidden="true"
                            />
                        </a>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>

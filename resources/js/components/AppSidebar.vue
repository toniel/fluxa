<script setup lang="ts">
import {
    ArrowLeftRight,
    CreditCard,
    House,
    ReceiptText,
    Settings,
    Tags,
    Users,
    Wallet,
} from '@lucide/vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import TenantSwitcher from '@/components/fluxa/TenantSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { index as accounts } from '@/routes/accounts';
import { index as billing } from '@/routes/billing';
import { index as categories } from '@/routes/categories';
import { dashboard } from '@/routes';
import { index as members } from '@/routes/members';
import { settings as tenantSettings } from '@/routes/tenant';
import { index as transactions } from '@/routes/transactions';
import { index as transfers } from '@/routes/transfers';
import type { NavItem } from '@/types';

/**
 * Ikon dipilih dari benda yang diwakilinya, bukan dari kesan "aplikasi
 * keuangan": dompet untuk kantong, struk untuk transaksi, dua panah berlawanan
 * untuk transfer.
 */
const uangNavItems: NavItem[] = [
    { title: 'Beranda', href: dashboard(), icon: House },
    { title: 'Transaksi', href: transactions(), icon: ReceiptText },
    { title: 'Transfer', href: transfers(), icon: ArrowLeftRight },
    { title: 'Kantong', href: accounts(), icon: Wallet },
    { title: 'Kategori', href: categories(), icon: Tags },
];

const kelolaNavItems: NavItem[] = [
    { title: 'Anggota', href: members(), icon: Users },
    { title: 'Langganan', href: billing(), icon: CreditCard },
    { title: 'Pengaturan', href: tenantSettings(), icon: Settings },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <TenantSwitcher />
        </SidebarHeader>

        <SidebarContent>
            <NavMain label="Uang" :items="uangNavItems" />
            <NavMain label="Kelola" :items="kelolaNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

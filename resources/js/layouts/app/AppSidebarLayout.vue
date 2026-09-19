<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import MobileTabBar from '@/components/fluxa/MobileTabBar.vue';
import TenantTopBar from '@/components/fluxa/TenantTopBar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <!-- Header breadcrumb dipakai dari md ke atas; di HP tempatnya
                 diambil alih bar tenant yang membawa pemilih tenant dan role. -->
            <div class="hidden md:block">
                <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            </div>
            <TenantTopBar />
            <!--
                Padding bawah menyediakan ruang untuk tab bar yang fixed di HP,
                ditambah safe area, supaya baris terakhir daftar tetap terjangkau.
            -->
            <div
                class="pb-[calc(3.5rem+env(safe-area-inset-bottom)+0.5rem)] md:pb-0"
            >
                <slot />
            </div>
        </AppContent>
        <MobileTabBar />
        <Toaster />
    </AppShell>
</template>

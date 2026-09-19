<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeftRight, House, Menu, ReceiptText, Wallet } from '@lucide/vue';
import { useSidebar } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { index as accounts } from '@/routes/accounts';
import { dashboard } from '@/routes';
import { index as transactions } from '@/routes/transactions';
import { index as transfers } from '@/routes/transfers';

const { isCurrentUrl } = useCurrentUrl();
const { setOpenMobile } = useSidebar();

const tabs = [
    { title: 'Beranda', href: dashboard(), icon: House },
    { title: 'Transaksi', href: transactions(), icon: ReceiptText },
    { title: 'Kantong', href: accounts(), icon: Wallet },
    { title: 'Transfer', href: transfers(), icon: ArrowLeftRight },
];
</script>

<template>
    <!--
        Navigasi utama di HP. Tujuan yang paling sering dipakai tetap terlihat,
        jadi menu tidak menyembunyikan satu-satunya jalan berpindah halaman.
        Tingginya dikompensasi oleh padding bawah di AppSidebarLayout supaya
        baris terakhir daftar tidak pernah tertutup.
    -->
    <nav
        aria-label="Navigasi utama"
        class="bg-card fixed inset-x-0 bottom-0 z-40 border-t pb-[env(safe-area-inset-bottom)] md:hidden"
    >
        <ul class="grid grid-cols-5">
            <li v-for="tab in tabs" :key="tab.title">
                <Link
                    :href="tab.href"
                    class="focus-visible:ring-ring flex min-h-14 flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] focus-visible:ring-2 focus-visible:-outline-offset-2 focus-visible:outline-none"
                    :class="
                        isCurrentUrl(tab.href)
                            ? 'text-money-in font-medium'
                            : 'text-muted-foreground'
                    "
                    :aria-current="isCurrentUrl(tab.href) ? 'page' : undefined"
                >
                    <component
                        :is="tab.icon"
                        class="size-5"
                        aria-hidden="true"
                    />
                    {{ tab.title }}
                </Link>
            </li>
            <li>
                <button
                    type="button"
                    class="text-muted-foreground focus-visible:ring-ring flex min-h-14 w-full flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] focus-visible:ring-2 focus-visible:-outline-offset-2 focus-visible:outline-none"
                    @click="setOpenMobile(true)"
                >
                    <Menu class="size-5" aria-hidden="true" />
                    Lainnya
                </button>
            </li>
        </ul>
    </nav>
</template>

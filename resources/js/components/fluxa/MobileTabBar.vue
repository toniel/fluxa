<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { House, Menu, ReceiptText, Wallet } from '@lucide/vue';
import { useSidebar } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { index as accounts } from '@/routes/accounts';
import { dashboard } from '@/routes';
import { index as transactions } from '@/routes/transactions';

const { isCurrentUrl } = useCurrentUrl();
const { setOpenMobile } = useSidebar();

// Tiga tujuan paling sering plus satu pintu ke sisanya. Transfer dan kategori
// pindah ke menu karena keduanya dipakai jauh lebih jarang daripada mencatat
// transaksi atau melihat saldo.
const tabs = [
    { title: 'Beranda', href: dashboard(), icon: House },
    { title: 'Kantong', href: accounts(), icon: Wallet },
    { title: 'Transaksi', href: transactions(), icon: ReceiptText },
];
</script>

<template>
    <nav
        aria-label="Navigasi utama"
        class="bg-card fixed inset-x-0 bottom-0 z-40 border-t pb-[env(safe-area-inset-bottom)] md:hidden"
    >
        <ul class="grid grid-cols-4">
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

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { House, Menu, Plus, ReceiptText, Wallet } from '@lucide/vue';
import { useSidebar } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { notYet } from '@/lib/notYet';
import { index as accounts } from '@/routes/accounts';
import { dashboard } from '@/routes';
import { index as transactions } from '@/routes/transactions';

const { isCurrentUrl } = useCurrentUrl();
const { setOpenMobile } = useSidebar();

// Tujuan paling sering dipakai saja yang tampil. Transfer dan kategori masuk
// menu karena keduanya jauh lebih jarang dibuka daripada saldo dan transaksi.
const left = [
    { title: 'Beranda', href: dashboard(), icon: House },
    { title: 'Kantong', href: accounts(), icon: Wallet },
];

const right = [{ title: 'Transaksi', href: transactions(), icon: ReceiptText }];
</script>

<template>
    <nav
        aria-label="Navigasi utama"
        class="bg-card fixed inset-x-0 bottom-0 z-40 border-t pb-[env(safe-area-inset-bottom)] md:hidden"
    >
        <ul class="grid grid-cols-5 items-center">
            <li v-for="tab in left" :key="tab.title">
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

            <!--
                Mencatat transaksi adalah aksi yang paling sering dilakukan, jadi
                ia mendapat tombol sendiri di tengah alih-alih bersaing sebagai
                satu baris di dalam menu.
            -->
            <li class="flex justify-center">
                <button
                    type="button"
                    class="bg-primary text-primary-foreground focus-visible:ring-ring flex size-14 items-center justify-center rounded-full shadow-sm focus-visible:ring-2 focus-visible:outline-none"
                    aria-label="Catat transaksi"
                    @click="notYet('Catat transaksi')"
                >
                    <Plus class="size-6" aria-hidden="true" />
                </button>
            </li>

            <li v-for="tab in right" :key="tab.title">
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

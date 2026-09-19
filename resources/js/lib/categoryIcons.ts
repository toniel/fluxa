import {
    Banknote,
    Car,
    CirclePlus,
    Ellipsis,
    HandCoins,
    Receipt,
    ShoppingBag,
    Utensils,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

/**
 * Nama ikon dari backend dipetakan ke komponennya di sini, bukan di halaman,
 * supaya kategori baru cukup ditambahkan sekali.
 */
const icons: Record<string, LucideIcon> = {
    banknote: Banknote,
    car: Car,
    'circle-plus': CirclePlus,
    ellipsis: Ellipsis,
    'hand-coins': HandCoins,
    receipt: Receipt,
    'shopping-bag': ShoppingBag,
    utensils: Utensils,
};

export function categoryIcon(name: string | null | undefined): LucideIcon {
    return (name && icons[name]) || Ellipsis;
}

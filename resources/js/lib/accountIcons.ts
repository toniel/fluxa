import {
    Banknote,
    Car,
    CircleDollarSign,
    Coins,
    CreditCard,
    Ellipsis,
    Gift,
    HandCoins,
    House,
    Landmark,
    PiggyBank,
    Plane,
    Receipt,
    ShoppingBag,
    Smartphone,
    Wallet,
    Wrench,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

/**
 * Nama ikon kantong dari backend dipetakan ke komponennya di sini.
 */
const icons: Record<string, LucideIcon> = {
    banknote: Banknote,
    car: Car,
    'circle-dollar-sign': CircleDollarSign,
    coins: Coins,
    'credit-card': CreditCard,
    ellipsis: Ellipsis,
    gift: Gift,
    'hand-coins': HandCoins,
    house: House,
    landmark: Landmark,
    'piggy-bank': PiggyBank,
    plane: Plane,
    receipt: Receipt,
    'shopping-bag': ShoppingBag,
    smartphone: Smartphone,
    wallet: Wallet,
    wrench: Wrench,
};

export function accountIcon(
    name: string | null | undefined,
): LucideIcon | null {
    return name ? (icons[name] ?? null) : null;
}

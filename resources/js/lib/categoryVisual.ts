/**
 * Slot warna kategori (nama tanpa "--"), selaras dengan config/fluxa.php
 * `category_colors` dan var CSS --cat-1..--cat-5 di resources/css/app.css.
 * Disimpan sebagai nama slot agar warnanya ikut tema terang/gelap.
 */
export const COLOR_SLOTS = [
    'cat-1',
    'cat-2',
    'cat-3',
    'cat-4',
    'cat-5',
] as const;

export type ColorSlot = (typeof COLOR_SLOTS)[number];

export function isColorSlot(
    value: string | null | undefined,
): value is ColorSlot {
    return (
        value !== null &&
        value !== undefined &&
        COLOR_SLOTS.includes(value as ColorSlot)
    );
}

/**
 * Kelas Tailwind untuk lencana berwarna slot. Ditulis lengkap, bukan dibangun
 * dari nama, supaya utilitasnya benar-benar ada saat build.
 */
export const colorTint: Record<ColorSlot, string> = {
    'cat-1': 'bg-cat-1/12 text-cat-1',
    'cat-2': 'bg-cat-2/12 text-cat-2',
    'cat-3': 'bg-cat-3/12 text-cat-3',
    'cat-4': 'bg-cat-4/12 text-cat-4',
    'cat-5': 'bg-cat-5/12 text-cat-5',
};

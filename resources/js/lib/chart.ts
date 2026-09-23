import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from 'chart.js';
import { formatRupiah } from '@/lib/currency';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
);

/**
 * Canvas tidak mengerti var(): warna tema dibaca dari CSS yang sudah
 * dihitung supaya chart ikut terang/gelap.
 */
export function themeColor(name: string): string {
    if (typeof document === 'undefined') {
        return '#000000';
    }

    return (
        getComputedStyle(document.documentElement)
            .getPropertyValue(name)
            .trim() || '#000000'
    );
}

/**
 * Urutan warna sama dengan donat SVG sebelumnya: kategori ke-3 selalu
 * memakai warna ke-3 walau kategori lain hilang dari bulan berjalan.
 */
export function chartPalette(): string[] {
    return [1, 2, 3, 4, 5].map((i) => themeColor(`--cat-${i}`));
}

export function chartTextColor(): string {
    return themeColor('--muted-foreground');
}

export function chartGridColor(): string {
    return themeColor('--border');
}

/**
 * Callback label tooltip: nominal Rupiah. Parameter disengaja longgar
 * ({raw}) supaya satu fungsi terpasang di chart batang maupun donat.
 */
export function rupiahTooltip(tooltipItem: { raw: unknown }): string {
    const raw: unknown = tooltipItem.raw;
    const value =
        typeof raw === 'number'
            ? raw
            : typeof raw === 'string'
              ? Number.parseFloat(raw)
              : 0;

    return formatRupiah(Number.isFinite(value) ? value : 0);
}

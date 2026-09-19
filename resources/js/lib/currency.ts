const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

const compact = new Intl.NumberFormat('id-ID', {
    notation: 'compact',
    maximumFractionDigits: 1,
});

function toNumber(value: number | string): number {
    return typeof value === 'number' ? value : Number.parseFloat(value || '0');
}

export function formatRupiah(value: number | string): string {
    return rupiah.format(toNumber(value));
}

/** Untuk kartu ringkas di mobile, tempat nominal penuh tidak muat. */
export function formatRupiahCompact(value: number | string): string {
    return `Rp ${compact.format(toNumber(value))}`;
}

export function parseRupiah(input: string): number {
    return (
        Number.parseFloat(input.replace(/[^\d,-]/g, '').replace(',', '.')) || 0
    );
}

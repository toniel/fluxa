import { toast } from 'vue-sonner';

/**
 * Umpan balik untuk aksi yang tampilannya sudah ada tapi belum punya backend.
 *
 * Dipakai supaya tombol tidak jadi kontrol mati: yang menekannya tetap
 * mendapat jawaban, dan jawabannya jujur soal apa yang belum ada.
 */
export function notYet(action: string): void {
    toast.info(`${action} belum tersambung`, {
        description: 'Halaman ini masih pratinjau tampilan dengan data contoh.',
    });
}

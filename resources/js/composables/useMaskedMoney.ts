import type { Ref } from 'vue';
import { ref } from 'vue';

const STORAGE_KEY = 'fluxa_masked_money';

/** Baca pref seragam yang disimpan di localStorage dari kunjungan lalu. */
function readStored(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return localStorage.getItem(STORAGE_KEY) === '1';
}

// Singleton modul: semua komponen berbagi satu sumber kebenaran sehingga
// tombol di bar atas dan seluruh tampilan nominal sinkron tanpa event bus.
const isMasked: Ref<boolean> = ref(readStored());

export function useMaskedMoney(): {
    isMasked: Ref<boolean>;
    toggleMaskMoney: () => boolean;
} {
    function toggleMaskMoney(): boolean {
        isMasked.value = !isMasked.value;
        localStorage.setItem(STORAGE_KEY, isMasked.value ? '1' : '0');

        return isMasked.value;
    }

    return { isMasked, toggleMaskMoney };
}

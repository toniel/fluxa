<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Pemilih state tampilan untuk pratinjau.
 *
 * Setiap halaman daftar punya tiga state yang harus benar sebelum dianggap
 * selesai: ada isi, kosong, dan gagal memuat. Dengan data contoh, dua state
 * terakhir tidak akan pernah muncul sendiri, jadi keduanya dibuat bisa dipanggil
 * lewat query string supaya benar-benar bisa dilihat dan dinilai.
 *
 * Ikut hilang bersama SampleData begitu lapisan data asli mendarat.
 */
enum PreviewState: string
{
    case Ready = 'ready';
    case Empty = 'empty';
    case Failed = 'failed';

    public static function fromRequest(Request $request): self
    {
        return match (true) {
            $request->has('galat') => self::Failed,
            $request->has('kosong') => self::Empty,
            default => self::Ready,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function rows(array $rows): array
    {
        return $this === self::Ready ? $rows : [];
    }
}

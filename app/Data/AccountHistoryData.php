<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Satu baris garis waktu mutasi kantong: transaksi atau transfer. Read-only,
 * jadi tidak ada FormData pasangannya.
 */
#[TypeScript]
class AccountHistoryData extends Data
{
    public function __construct(
        public string $key,
        public string $kind,
        public int $ref_id,
        public string $date,
        public string $title,
        public string $subtitle,
        public string $amount,
        public string $direction,
        public string $creator_name,
        public bool $can_edit = false,
    ) {}
}

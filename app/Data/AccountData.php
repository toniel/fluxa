<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AccountType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Bentuk kantong keluar, satu baris yang sudah ada di database.
 *
 * nominal dikirim sebagai string decimal mentah, diformat di frontend.
 */
#[TypeScript]
class AccountData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public AccountType $type,
        public string $balance,
        public string $initial_balance,
        public bool $is_archived,
        public string $logo_url,
    ) {}
}

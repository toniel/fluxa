<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AccountType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Bentuk kantong masuk, apa yang dikirim form create/update.
 *
 * `initial_balance` ikut dikirim saat update (form menguncinya read-only),
 * tapi action update mengabaikannya: saldo awal tidak bisa diubah sesudah
 * kantong dibuat. Tanpa id: target update datang dari route binding, bukan
 * dari payload.
 */
#[TypeScript]
class AccountFormData extends Data
{
    public function __construct(
        #[Required, Max(50)]
        public string $name,
        #[Enum(AccountType::class)]
        public AccountType $type,
        #[Required, Numeric, Min(0), Max(999999999999.99)]
        public string $initial_balance,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Different;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TransferFormData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1), Exists('accounts', 'id')]
        public int $from_account_id,
        #[Required, IntegerType, Min(1), Exists('accounts', 'id'), Different('from_account_id')]
        public int $to_account_id,
        #[Required, Numeric, Min(0.01), Max(999999999999.99)]
        public string $amount,
        #[Nullable, Max(500)]
        public ?string $description,
        #[Required, DateFormat('Y-m-d'), BeforeOrEqual('today')]
        public string $transfer_date,
    ) {}
}

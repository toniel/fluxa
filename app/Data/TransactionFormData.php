<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TransactionType;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Enum;
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
class TransactionFormData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1), Exists('accounts', 'id')]
        public int $account_id,
        #[Nullable, IntegerType, Min(1), Exists('categories', 'id')]
        public ?int $category_id,
        #[Nullable, IntegerType, Min(1), Exists('accounts', 'id')]
        public ?int $linked_account_id,
        #[Enum(TransactionType::class)]
        public TransactionType $type,
        #[Required, Numeric, Min(0.01), Max(999999999999.99)]
        public string $amount,
        #[Nullable, Max(500)]
        public ?string $description,
        #[Required, DateFormat('Y-m-d'), BeforeOrEqual('today')]
        public string $transaction_date,
    ) {}
}

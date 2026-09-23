<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TransactionType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TransactionData extends Data
{
    public function __construct(
        public int $id,
        public int $account_id,
        public string $account_name,
        public ?int $category_id,
        public ?string $category_name,
        public ?string $category_icon,
        public TransactionType $type,
        public string $amount,
        public ?string $description,
        public string $transaction_date,
        public string $creator_name,
        public string $receipt_url,
        public bool $can_edit = false,
        public bool $can_delete = false,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TransferData extends Data
{
    public function __construct(
        public int $id,
        public int $from_account_id,
        public string $from_account_name,
        public int $to_account_id,
        public string $to_account_name,
        public string $amount,
        public ?string $description,
        public string $transfer_date,
        public string $creator_name,
        public bool $can_edit = false,
        public bool $can_delete = false,
    ) {}
}

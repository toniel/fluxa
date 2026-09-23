<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreditCardDetailData extends Data
{
    public function __construct(
        public int $billing_cycle_start_day,
        public int $billing_cycle_end_day,
        public int $payment_due_offset_days,
        public string $default_interest_rate_monthly,
        public string $default_admin_fee_percentage,
        public ?string $credit_limit,
    ) {}
}

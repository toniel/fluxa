<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SubscriptionStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SubscriptionData extends Data
{
    public function __construct(
        public int $id,
        public PlanData $plan,
        public SubscriptionStatus $status,
        public ?string $current_period_end,
    ) {}
}

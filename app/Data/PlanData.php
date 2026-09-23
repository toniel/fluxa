<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PlanData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $price,
        public string $billing_period,
        public bool $is_active,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TenantSettingsFormData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public string $name,
        #[Nullable, Max(30)]
        public ?string $subdomain,
    ) {}
}

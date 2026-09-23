<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TenantRole;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class InvitationFormData extends Data
{
    public function __construct(
        #[Required, Email, Max(255)]
        public string $email,
        #[Enum(TenantRole::class)]
        public TenantRole $role,
    ) {}
}

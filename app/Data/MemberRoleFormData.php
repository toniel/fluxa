<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TenantRole;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MemberRoleFormData extends Data
{
    public function __construct(
        #[Enum(TenantRole::class)]
        public TenantRole $role,
    ) {}
}

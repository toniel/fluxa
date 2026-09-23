<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TenantMemberData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
        public ?string $joined_at,
        public bool $is_owner,
        public bool $is_current_user,
        public bool $can_change_role = false,
        public bool $can_remove = false,
    ) {}
}

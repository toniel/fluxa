<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\InvitationStatus;
use App\Enums\TenantRole;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TenantInvitationData extends Data
{
    public function __construct(
        public int $id,
        public string $email,
        public TenantRole $role,
        public InvitationStatus $status,
        public string $expires_at,
        public string $accept_url,
        public bool $can_resend = false,
        public bool $can_revoke = false,
    ) {}
}

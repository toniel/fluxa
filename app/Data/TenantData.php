<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TenantData extends Data
{
    /**
     * @param  list<array{id: int, name: string, subdomain: string|null, url: string, role: string|null, is_current: bool}>  $memberships
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $subdomain,
        public string $created_at,
        public int $member_count,
        public array $memberships = [],
    ) {}
}

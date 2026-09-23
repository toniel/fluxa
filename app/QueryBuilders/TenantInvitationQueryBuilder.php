<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Enums\InvitationStatus;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<TenantInvitation>
 */
class TenantInvitationQueryBuilder extends Builder
{
    public function byToken(string $token): ?TenantInvitation
    {
        return $this->where('token', $token)->first();
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->where('tenant_id', $tenant->getKey());
    }

    public function pending(): static
    {
        return $this->where('status', InvitationStatus::Pending->value)
            ->where('expires_at', '>', now());
    }

    public function expireStale(): int
    {
        return $this->where('status', InvitationStatus::Pending->value)
            ->where('expires_at', '<=', now())
            ->update(['status' => InvitationStatus::Expired->value]);
    }
}

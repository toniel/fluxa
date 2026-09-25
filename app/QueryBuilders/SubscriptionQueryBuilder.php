<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Subscription>
 */
class SubscriptionQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('status', SubscriptionStatus::Active->value);
    }

    /**
     * Batasi ke langganan para tenant itu.
     *
     * @param  list<int>  $tenantIds
     */
    public function forTenants(array $tenantIds): static
    {
        return $this->whereIn('tenant_id', $tenantIds);
    }
}

<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Domain>
 */
class DomainQueryBuilder extends Builder
{
    public function forTenant(Tenant $tenant): static
    {
        return $this->where('tenant_id', $tenant->getKey());
    }

    public function primary(): static
    {
        return $this->where('is_primary', true);
    }

    public function redirecting(): static
    {
        return $this->whereNotNull('redirects_to');
    }
}

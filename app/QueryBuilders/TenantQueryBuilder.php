<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Tenant>
 */
class TenantQueryBuilder extends Builder
{
    public function forMember(User $user): static
    {
        return $this->whereHas(
            'members',
            fn (Builder $query) => $query->whereKey($user->getKey()),
        );
    }

    public function withPrimaryDomain(): static
    {
        return $this->with('domains');
    }
}

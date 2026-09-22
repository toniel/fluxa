<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Account>
 */
class AccountQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('is_archived', false);
    }

    public function archived(): static
    {
        return $this->where('is_archived', true);
    }

    public function orderedForListing(): static
    {
        return $this->orderBy('is_archived')->orderBy('name');
    }
}

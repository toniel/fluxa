<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Plan>
 */
class PlanQueryBuilder extends Builder
{
    public function bySlug(string $slug): ?Plan
    {
        return $this->where('slug', $slug)->first();
    }
}

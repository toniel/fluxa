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

    /**
     * Slug paket yang aktif, untuk menemani validasi permintaan.
     *
     * @return list<string>
     */
    public function activeSlugs(): array
    {
        $slugs = [];

        foreach ($this->where('is_active', true)->get() as $plan) {
            $slugs[] = $plan->slug;
        }

        return $slugs;
    }
}

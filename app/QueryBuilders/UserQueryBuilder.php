<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<User>
 */
class UserQueryBuilder extends Builder
{
    public function createdSince(CarbonInterface $date): static
    {
        return $this->where('created_at', '>=', $date);
    }

    public function createdBetween(CarbonInterface $from, CarbonInterface $to): static
    {
        return $this->whereBetween('created_at', [$from, $to]);
    }
}

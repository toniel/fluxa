<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Category>
 */
class CategoryQueryBuilder extends Builder
{
    public function ofType(CategoryType $type): static
    {
        return $this->where('type', $type->value);
    }

    public function orderedForListing(): static
    {
        return $this->orderBy('name');
    }

    public function whereNameAndType(string $name, CategoryType $type): static
    {
        return $this->where('name', $name)->where('type', $type->value);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryType;
use App\Models\Concerns\BelongsToTenant;
use App\Policies\CategoryPolicy;
use App\QueryBuilders\CategoryQueryBuilder;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Lacodix\LaravelModelFilter\Traits\IsSortable;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property CategoryType $type
 * @property string|null $icon
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static CategoryQueryBuilder query()
 *
 * @mixin CategoryQueryBuilder
 */
#[Fillable(['name', 'type', 'icon', 'is_default'])]
#[UseEloquentBuilder(CategoryQueryBuilder::class)]
#[UsePolicy(CategoryPolicy::class)]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use BelongsToTenant, HasFactory, HasFilters, IsSearchable, IsSortable;

    /** @var list<string> */
    protected $searchable = ['name'];

    /** @var array<string, string|null> */
    protected $sortable = ['name' => null, 'is_default' => null];

    /**
     * @return list<EnumFilter<Category>>
     */
    public function filters(): array
    {
        return [EnumFilter::make('type')
            ->setEnum(CategoryType::class)
            ->setQueryName('type')];
    }

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'is_default' => 'boolean',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\QueryBuilders\DomainQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * @property int $id
 * @property string $domain Label subdomain saja, tanpa central domain.
 * @property int $tenant_id
 * @property bool $is_primary
 * @property string|null $redirects_to
 *
 * @method static DomainQueryBuilder query()
 */
#[Fillable(['domain', 'tenant_id', 'is_primary', 'redirects_to'])]
#[UseEloquentBuilder(DomainQueryBuilder::class)]
class Domain extends BaseDomain
{
    public $incrementing = true;

    protected $keyType = 'int';

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}

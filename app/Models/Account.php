<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountType;
use App\Models\Concerns\BelongsToTenant;
use App\Policies\AccountPolicy;
use App\QueryBuilders\AccountQueryBuilder;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Lacodix\LaravelModelFilter\Traits\IsSortable;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property AccountType $type
 * @property string $balance
 * @property string $initial_balance
 * @property string|null $icon
 * @property string|null $color
 * @property bool $is_archived
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static AccountQueryBuilder query()
 *
 * @mixin AccountQueryBuilder
 */
#[Fillable(['name', 'type', 'initial_balance', 'icon', 'color', 'is_archived', 'created_by'])]
#[UseEloquentBuilder(AccountQueryBuilder::class)]
#[UsePolicy(AccountPolicy::class)]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use BelongsToTenant, HasFactory, HasFilters, IsSearchable, IsSortable;

    /**
     * `balance` sengaja TIDAK fillable: satu-satunya penulisnya adalah
     * UpsertAccountAction (saat create menyamakannya dengan initial_balance),
     * dan kelak operasi transaksi lewat Action pembaca saldo.
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'balance' => 'decimal:2',
            'initial_balance' => 'decimal:2',
            'is_archived' => 'boolean',
        ];
    }

    /** @var list<string> */
    protected $searchable = ['name'];

    /** @var array<string, string|null> */
    protected $sortable = ['name' => null, 'is_archived' => null];

    /**
     * @return list<EnumFilter<Account>>
     */
    public function filters(): array
    {
        return [EnumFilter::make('type')
            ->setEnum(AccountType::class)
            ->setQueryName('type')];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

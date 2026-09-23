<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Policies\TransferPolicy;
use App\QueryBuilders\TransferQueryBuilder;
use Database\Factories\TransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Lacodix\LaravelModelFilter\Traits\IsSortable;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $from_account_id
 * @property int $to_account_id
 * @property numeric-string $amount
 * @property string|null $description
 * @property Carbon $transfer_date
 * @property int $created_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static TransferQueryBuilder query()
 *
 * @mixin TransferQueryBuilder
 */
#[Fillable(['from_account_id', 'to_account_id', 'amount', 'description', 'transfer_date', 'created_by'])]
#[UseEloquentBuilder(TransferQueryBuilder::class)]
#[UsePolicy(TransferPolicy::class)]
class Transfer extends Model
{
    /** @use HasFactory<TransferFactory> */
    use BelongsToTenant, HasFactory, HasFilters, IsSearchable, IsSortable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transfer_date' => 'date',
        ];
    }

    /** @var list<string> */
    protected $searchable = ['description'];

    /** @var array<string, string|null> */
    protected $sortable = ['transfer_date' => null, 'amount' => null];

    /**
     * @return array{SelectFilter<Transfer>, SelectFilter<Transfer>}
     */
    public function filters(): array
    {
        return [
            SelectFilter::make('from_account_id')->setQueryName('from_account_id'),
            SelectFilter::make('to_account_id')->setQueryName('to_account_id'),
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

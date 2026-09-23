<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToTenant;
use App\Policies\TransactionPolicy;
use App\QueryBuilders\TransactionQueryBuilder;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Lacodix\LaravelModelFilter\Traits\IsSortable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $account_id
 * @property int|null $category_id
 * @property TransactionType $type
 * @property numeric-string $amount
 * @property string|null $description
 * @property Carbon $transaction_date
 * @property int $created_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $receipt_url
 *
 * @method static TransactionQueryBuilder query()
 *
 * @mixin TransactionQueryBuilder
 */
#[Fillable(['account_id', 'category_id', 'type', 'amount', 'description', 'transaction_date', 'created_by'])]
#[UseEloquentBuilder(TransactionQueryBuilder::class)]
#[UsePolicy(TransactionPolicy::class)]
class Transaction extends Model implements HasMedia
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToTenant, HasFactory, HasFilters, InteractsWithMedia, IsSearchable, IsSortable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    /** @var list<string> */
    protected $searchable = ['description'];

    /** @var array<string, string|null> */
    protected $sortable = ['transaction_date' => null, 'amount' => null];

    /**
     * @return array{EnumFilter<Transaction>, SelectFilter<Transaction>, SelectFilter<Transaction>}
     */
    public function filters(): array
    {
        return [
            EnumFilter::make('type')
                ->setEnum(TransactionType::class)
                ->setQueryName('type'),
            SelectFilter::make('account_id')->setQueryName('account_id'),
            SelectFilter::make('category_id')->nullable()->setQueryName('category_id'),
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')->singleFile();
    }

    public function getReceiptUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('receipt');
    }

    public function signedAmount(): string
    {
        return bcmul($this->amount, (string) $this->type->signum(), 2);
    }
}

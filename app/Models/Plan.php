<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingPeriod;
use App\QueryBuilders\PlanQueryBuilder;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Paket langganan global (tanpa tenant). Harga dan fitur bisa diubah tanpa
 * deploy; null pada fitur angka berarti tanpa batas.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property numeric-string $price
 * @property BillingPeriod $billing_period
 * @property array<string, mixed> $features
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static PlanQueryBuilder query()
 */
#[Fillable(['name', 'slug', 'price', 'billing_period', 'features', 'is_active'])]
#[UseEloquentBuilder(PlanQueryBuilder::class)]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'billing_period' => BillingPeriod::class,
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function feature(string $key, mixed $default = null): mixed
    {
        return $this->features[$key] ?? $default;
    }
}

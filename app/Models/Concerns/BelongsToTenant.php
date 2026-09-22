<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\TenantContextMissingException;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isolasi data per tenant untuk model yang memakai kolom tenant_id.
 *
 * Menambah global scope (semua query ter-kunci ke tenant aktif) dan mengisi
 * tenant_id otomatis di hook creating, sehingga tidak ada action yang perlu
 * mengingat menuliskannya. Model tanpa tenant_id (User, Tenant, Domain,
 * TenantInvitation) TIDAK memakai trait ini.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $context = app(TenantContext::class);

            if (! $context->check()) {
                throw new TenantContextMissingException($model::class);
            }

            $model->setAttribute('tenant_id', $context->id());
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Escape hatch eksplisit, bukan untuk dipakai controller.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}

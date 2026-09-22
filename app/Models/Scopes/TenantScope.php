<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Exceptions\TenantContextMissingException;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Mengunci semua query model bertenant ke tenant aktif.
 *
 * Tanpa tenant aktif, HTTP melempar keras supaya tidak pernah ada query yang
 * diam-diam mengembalikan baris semua tenant. Konsol mematikan scope: seeder
 * dan factory dijalankan di dalam TenantContext::runFor(), yang membuat hook
 * creating di trait BelongsToTenant tetap mengisi tenant_id.
 *
 * @implements Scope<Model>
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->isBypassed()) {
            return;
        }

        if ($context->check()) {
            $builder->where($model->qualifyColumn('tenant_id'), $context->id());

            return;
        }

        if (app()->runningInConsole()) {
            return;
        }

        throw new TenantContextMissingException($model::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Events\TenancyEnded;
use Stancl\Tenancy\Events\TenancyInitialized;

/**
 * Menjahit dua state global menjadi satu: tenant aktif milik stancl dan team id
 * milik spatie/laravel-permission.
 *
 * Kalau keduanya melenceng, user melihat data tenant B dengan permission tenant
 * A — kegagalan paling berbahaya di aplikasi ini, dan ia tidak menimbulkan error
 * apa pun. Karena itu listener ini adalah SATU-SATUNYA tempat yang boleh
 * memanggil setPermissionsTeamId(): dengan menempelkannya ke event stancl,
 * request HTTP, console, queue, seeder, dan test semuanya ikut sinkron tanpa
 * perlu mengingat apa pun.
 */
class SyncPermissionTeam
{
    public function handle(TenancyInitialized|TenancyEnded $event): void
    {
        // tenancy->tenant bertipe Model|Tenant; getTenantKey() hanya ada di
        // kontraknya, jadi penyempitan ini bukan formalitas.
        $tenant = $event instanceof TenancyInitialized ? $event->tenancy->tenant : null;

        $tenantId = $tenant instanceof Tenant ? $tenant->getTenantKey() : null;

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        // Relasi roles/permissions yang sudah ter-load milik team sebelumnya.
        // Tanpa dibuang, pengecekan berikutnya menjawab dari data tenant lama.
        $user = auth()->user();

        if ($user !== null) {
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}

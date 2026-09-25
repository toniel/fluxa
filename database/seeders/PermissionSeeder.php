<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\TenantRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission dan role dibuat SEKALI sebagai baris global (tenant_id = null),
 * bukan tiga baris role per tenant.
 *
 * Role per tenant akan membuat penambahan satu permission menjadi pekerjaan
 * backfill ke seluruh tenant, dan setiap pembuatan tenant berisiko gagal
 * separuh jalan dengan role yang hilang. Yang per-tenant adalah ASSIGNMENT-nya
 * (baris di model_has_roles), bukan definisi role-nya.
 *
 * Seeder ini idempotent dan aman dijalankan di produksi. Ia WAJIB dijalankan
 * ulang di setiap environment setiap kali PermissionEnum atau
 * TenantRole::permissions() berubah — perlakukan sebagai langkah deploy.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        // Tanpa ini, role yang dibuat akan mewarisi team yang sedang aktif dan
        // berhenti menjadi global.
        $registrar->setPermissionsTeamId(null);

        // Flush di AWAL, bukan hanya di akhir. findOrCreate() menjawab dari
        // cache spatie; kalau cache-nya basi (mis. setelah seeder ini pernah
        // gagal separuh jalan), lookup meleset dan insert-nya menabrak unique
        // constraint. Seeder ini harus bisa dijalankan berulang kali.
        $registrar->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // Tanpa flush ini, cache spatie bisa basi: dalam DatabaseSeeder, model
        // events (sumber flush normal via RefreshesPermissionCache) dinonaktifkan
        // oleh WithoutModelEvents, jadi findOrCreate() tidak mematikan cache.
        $registrar->forgetCachedPermissions();

        foreach (TenantRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web')
                ->syncPermissions($role->permissionValues());
        }

        $registrar->forgetCachedPermissions();
    }
}

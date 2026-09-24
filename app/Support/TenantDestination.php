<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;

/**
 * Menentukan ke mana seorang user diarahkan setelah login.
 *
 * Dashboard hidup di subdomain tenant, sedangkan login terjadi di central
 * domain, jadi tujuan sesudah login tidak pernah bisa berupa path biasa: ia
 * selalu perpindahan lintas origin, atau halaman pemilih kalau tenant-nya
 * lebih dari satu.
 */
final class TenantDestination
{
    /**
     * TenantCollection milik stancl tidak beranotasi generik, sehingga tipe
     * elemennya luruh menjadi Model. Pemeriksaan instanceof di bawah bukan
     * formalitas: ia sekaligus menjaga kalau tenant_model di config diarahkan
     * ke kelas lain.
     *
     * @return list<Tenant>
     */
    public static function tenantsOf(User $user): array
    {
        $tenants = [];

        foreach (Tenant::query()->forMember($user)->withPrimaryDomain()->orderBy('name')->get() as $tenant) {
            if ($tenant instanceof Tenant) {
                $tenants[] = $tenant;
            }
        }

        return $tenants;
    }

    /**
     * Keanggotaan user beserta role dan subdomainnya.
     *
     * @return list<array{id: int, name: string, subdomain: string|null, url: string, role: string|null, is_current: bool}>
     */
    public static function membershipsOf(User $user, ?int $currentId = null): array
    {
        return array_map(
            static fn (Tenant $tenant): array => [
                'id' => $tenant->getKey(),
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain(),
                'url' => $tenant->url('/dashboard'),
                'role' => self::roleIn($tenant, $user)?->value,
                'is_current' => $currentId !== null && $tenant->getKey() === $currentId,
            ],
            self::tenantsOf($user),
        );
    }

    /**
     * Role user di satu tenant.
     *
     * Role spatie ter-scope per team, jadi ia hanya bisa dibaca dari dalam
     * konteks tenant yang bersangkutan. Konteks sebelumnya dipulihkan supaya
     * pemanggil tidak ikut terbawa pindah tenant.
     */
    public static function roleIn(Tenant $tenant, User $user): ?TenantRole
    {
        $previous = tenant();

        try {
            tenancy()->initialize($tenant);

            // Relasi yang sudah ter-load milik team sebelumnya; tanpa dibuang,
            // jawabannya adalah role di tenant yang salah.
            $user->unsetRelation('roles');

            // getRoleNames() mengembalikan nama role sebagai string, sehingga
            // tidak bergantung pada kelas Role yang ditentukan lewat config.
            $name = $user->getRoleNames()->first();

            return is_string($name) ? TenantRole::tryFrom($name) : null;
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }

    /**
     * Tujuan setelah login: langsung ke tenant kalau hanya ada satu, selain itu
     * ke pemilih tenant di central domain.
     */
    public static function afterLogin(User $user): string
    {
        $tenants = self::tenantsOf($user);

        if (count($tenants) === 1 && $tenants[0]->subdomain() !== null) {
            return $tenants[0]->url('/dashboard');
        }

        return route('tenants.index');
    }
}

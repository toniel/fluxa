<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * User contoh beserta keanggotaan dan role-nya, untuk pengembangan lokal.
 *
 * Dibuat dua tenant, bukan satu, dengan sengaja: tenant kedua memberi tenant
 * switcher sesuatu untuk di-switch, memberi test isolasi data pembanding yang
 * nyata, dan menyediakan subdomain kedua untuk menguji bahwa anggota tenant A
 * ditolak di subdomain tenant B.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = $this->user('Budi Pemilik', 'owner@fluxa.test');
        $admin = $this->user('Sinta Admin', 'admin@fluxa.test');
        $member = $this->user('Rudi Anggota', 'member@fluxa.test');

        $keluarga = $this->tenant('Keluarga Demo', 'keluarga-demo', $owner);
        $this->join($keluarga, $owner, TenantRole::Owner);
        $this->join($keluarga, $admin, TenantRole::Admin);
        $this->join($keluarga, $member, TenantRole::Member);

        // Owner yang sama memegang tenant kedua, dan di sini Sinta hanya
        // anggota biasa — pasangan yang membuktikan role tidak bocor antar
        // tenant: Sinta admin di Keluarga Demo, member di RT 05.
        $rt = $this->tenant('Komunitas RT 05', 'rt-05', $owner);
        $this->join($rt, $owner, TenantRole::Owner);
        $this->join($rt, $admin, TenantRole::Member);
    }

    private function user(string $name, string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Subdomain di-set eksplisit, bukan hasil generator acak, supaya developer
     * bisa langsung mengetik http://keluarga-demo.fluxa.test tanpa membuka
     * database lebih dulu.
     */
    private function tenant(string $name, string $subdomain, User $owner): Tenant
    {
        $tenant = Tenant::firstOrCreate(
            ['name' => $name],
            ['owner_id' => $owner->getKey()],
        );

        $tenant->domains()->firstOrCreate(
            ['domain' => $subdomain],
            ['is_primary' => true],
        );

        return $tenant;
    }

    /**
     * Keanggotaan dan role ditulis bersamaan.
     *
     * syncRoles() dijalankan di dalam konteks tenancy karena role spatie
     * ter-scope per team: di luar konteks, role akan tertulis dengan tenant_id
     * null dan berlaku di semua tenant sekaligus.
     */
    private function join(Tenant $tenant, User $user, TenantRole $role): void
    {
        $tenant->members()->syncWithoutDetaching([
            $user->getKey() => ['joined_at' => now()],
        ]);

        $previous = tenant();

        try {
            tenancy()->initialize($tenant);
            $user->syncRoles([$role->value]);
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}

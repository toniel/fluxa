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
 * Seeder ini otoritatif: keanggotaan disamakan persis dengan yang tertulis di
 * sini setiap kali dijalankan. Menjalankannya ulang setelah mengubah susunan
 * di bawah akan merapikan keadaan lama, bukan menumpuk di atasnya.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = $this->user('Budi Pemilik', 'owner@fluxa.test');
        $admin = $this->user('Sinta Admin', 'admin@fluxa.test');
        $member = $this->user('Rudi Anggota', 'member@fluxa.test');

        // Super admin platform tidak tergabung tenant mana pun: ia mengelola
        // lintas tenant dari central (path /admin). is_super_admin tidak
        // fillable, jadi di-set lewat forceFill — sama seperti command promote.
        $superadmin = $this->user('Ayu Platform', 'superadmin@fluxa.test');
        $superadmin->forceFill(['is_super_admin' => true])->save();

        // Budi sengaja hanya memegang satu tenant supaya login langsung masuk
        // dashboard tanpa mampir ke pemilih tenant.
        $this->tenant('Keluarga Demo', 'keluarga-demo', $owner, [
            $owner->id => TenantRole::Owner,
            $admin->id => TenantRole::Admin,
            $member->id => TenantRole::Member,
        ]);

        // Tenant kedua dipertahankan karena ia yang membuktikan role tidak bocor
        // antar tenant: Sinta adalah Admin di Keluarga Demo dan Owner di sini.
        // Ia juga memberi tenant switcher sesuatu untuk di-switch, dan
        // menyediakan subdomain kedua untuk menguji penolakan akses nanti.
        $this->tenant('Komunitas RT 05', 'rt-05', $admin, [
            $admin->id => TenantRole::Owner,
            $member->id => TenantRole::Member,
        ]);
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
     *
     * @param  array<int, TenantRole>  $roles  user_id => role
     */
    private function tenant(string $name, string $subdomain, User $owner, array $roles): Tenant
    {
        $tenant = Tenant::firstOrCreate(['name' => $name], ['owner_id' => $owner->getKey()]);
        $tenant->update(['owner_id' => $owner->getKey()]);

        $tenant->domains()->firstOrCreate(['domain' => $subdomain], ['is_primary' => true]);

        $this->syncMembers($tenant, $roles);

        return $tenant;
    }

    /**
     * Menyamakan keanggotaan tenant dengan daftar yang diberikan.
     *
     * Keanggotaan dan role hidup di dua tabel: pivot tenant_user dan
     * model_has_roles milik spatie. Keduanya harus ikut dilepas saat seseorang
     * dikeluarkan, kalau tidak ia kehilangan akses tapi role-nya tertinggal
     * sebagai baris yatim.
     *
     * @param  array<int, TenantRole>  $roles  user_id => role
     */
    private function syncMembers(Tenant $tenant, array $roles): void
    {
        $existing = $tenant->members()->pluck('users.id')->all();
        $removed = array_diff($existing, array_keys($roles));

        $tenant->members()->sync(
            array_map(static fn (): array => ['joined_at' => now()], $roles),
        );

        $previous = tenant();

        try {
            // syncRoles() ter-scope per team, jadi ia harus dijalankan dari dalam
            // konteks tenant yang bersangkutan.
            tenancy()->initialize($tenant);

            foreach ($roles as $userId => $role) {
                User::find((int) $userId)?->syncRoles([$role->value]);
            }

            foreach ($removed as $userId) {
                User::find((int) $userId)?->syncRoles([]);
            }
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}

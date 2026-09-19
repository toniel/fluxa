<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

/*
 * Regresi: tujuan bawaan Fortify adalah '/dashboard', padahal dashboard hanya
 * dilayani di subdomain tenant. Di central domain path itu dijawab 404 oleh
 * PreventAccessFromCentralDomains, sehingga setiap login berakhir di halaman
 * error.
 */

function tenantFor(User $user, string $name, string $subdomain): Tenant
{
    $tenant = Tenant::create(['name' => $name, 'owner_id' => $user->id]);
    $tenant->domains()->create(['domain' => $subdomain, 'is_primary' => true]);
    $tenant->members()->attach($user->id, ['joined_at' => now()]);

    tenancy()->initialize($tenant);
    $user->syncRoles([TenantRole::Owner->value]);
    tenancy()->end();

    return $tenant;
}

beforeEach(fn () => $this->seed(PermissionSeeder::class));

test('login tidak pernah mendarat di dashboard central domain', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-uji')]);
    tenantFor($user, 'Keluarga Uji', 'keluarga-uji');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'rahasia-uji',
    ]);

    $response->assertRedirect();

    // Yang dilarang adalah dashboard di CENTRAL domain. Dashboard di subdomain
    // tenant justru tujuan yang benar, dan alamatnya memuat central domain
    // sebagai akhiran, jadi perbandingannya harus persis.
    expect($response->headers->get('Location'))->not->toBe('http://fluxa.test/dashboard');
});

test('user dengan satu tenant diarahkan ke subdomain tenantnya', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-uji')]);
    tenantFor($user, 'Keluarga Uji', 'keluarga-uji');

    $this->post('/login', ['email' => $user->email, 'password' => 'rahasia-uji'])
        ->assertRedirect('http://keluarga-uji.fluxa.test/dashboard');
});

test('user dengan lebih dari satu tenant diarahkan ke pemilih tenant', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-uji')]);
    tenantFor($user, 'Keluarga Uji', 'keluarga-uji');
    tenantFor($user, 'Komunitas Uji', 'komunitas-uji');

    $this->post('/login', ['email' => $user->email, 'password' => 'rahasia-uji'])
        ->assertRedirect(route('tenants.index'));
});

test('user tanpa tenant tetap mendarat di halaman yang ada', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-uji')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'rahasia-uji'])
        ->assertRedirect(route('tenants.index'));

    $this->actingAs($user)->get(route('tenants.index'))->assertOk();
});

test('membuka halaman login saat sudah masuk tidak berakhir di dashboard central', function () {
    $user = User::factory()->create();
    tenantFor($user, 'Keluarga Uji', 'keluarga-uji');

    // RedirectIfAuthenticated memakai config('fortify.home'), bukan
    // LoginResponse, jadi jalur ini bisa rusak sendiri walau login POST benar.
    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect(route('tenants.index'));
});

test('pemilih tenant menampilkan seluruh tenant milik user', function () {
    $user = User::factory()->create();
    tenantFor($user, 'Keluarga Uji', 'keluarga-uji');
    tenantFor($user, 'Komunitas Uji', 'komunitas-uji');

    $this->actingAs($user)
        ->get(route('tenants.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tenants/Index')
            ->has('memberships', 2)
            ->where('memberships.0.url', 'http://keluarga-uji.fluxa.test/dashboard')
            ->where('memberships.0.role', 'owner'));
});

test('login inertia ke tenant lintas origin memakai X-Inertia-Location', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-uji')]);
    tenantFor($user, 'Keluarga Uji', 'keluarga-uji');

    // Redirect biasa tidak bisa menyeberang origin: Inertia mengikutinya lewat
    // XHR, dan XHR lintas origin diblokir CORS sehingga user tertahan di
    // halaman login tanpa pesan apa pun. Jawaban yang benar adalah 409 dengan
    // header X-Inertia-Location, yang memicu kunjungan halaman penuh.
    $this->withHeader('X-Inertia', 'true')
        ->withHeader('X-Inertia-Version', '1')
        ->post('/login', ['email' => $user->email, 'password' => 'rahasia-uji'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'http://keluarga-uji.fluxa.test/dashboard');
});

test('login inertia ke pemilih tenant tetap redirect biasa', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-uji')]);
    tenantFor($user, 'Keluarga Uji', 'keluarga-uji');
    tenantFor($user, 'Komunitas Uji', 'komunitas-uji');

    // Pemilih tenant satu origin dengan halaman login, jadi ia tidak butuh
    // kunjungan halaman penuh.
    $this->withHeader('X-Inertia', 'true')
        ->withHeader('X-Inertia-Version', '1')
        ->post('/login', ['email' => $user->email, 'password' => 'rahasia-uji'])
        ->assertRedirect(route('tenants.index'));
});

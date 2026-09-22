<?php

use App\Models\Tenant;
use App\Models\User;

/*
 * Dashboard kini hidup di subdomain tenant, bukan di central domain.
 *
 * Middleware auth sudah dipasang di routes/tenant.php (kategori memakai data
 * asli yang tidak boleh lihat orang luar), jadi setiap akses subdomain dibuat
 * dalam keadaan login sebagai anggota tenant bersangkutan.
 */

function dashboardOwner(): array
{
    $owner = User::factory()->create();

    $tenant = Tenant::create(['name' => 'Keluarga Uji', 'owner_id' => $owner->getKey()]);
    $tenant->domains()->create(['domain' => 'keluarga-uji', 'is_primary' => true]);

    return [$owner, $tenant];
}

test('dashboard tidak bisa diakses lewat central domain', function () {
    [$owner] = dashboardOwner();

    $this->actingAs($owner)
        ->get('http://fluxa.test/dashboard')
        ->assertNotFound();
});

test('dashboard bisa diakses lewat subdomain tenant sambil login', function () {
    [$owner] = dashboardOwner();

    $this->actingAs($owner)
        ->get('http://keluarga-uji.fluxa.test/dashboard')
        ->assertOk();
});

test('dashboard menolak pengunjung yang belum login', function () {
    dashboardOwner();

    $this->get('http://keluarga-uji.fluxa.test/dashboard')
        ->assertRedirect();
});

test('subdomain yang tidak terdaftar menjawab 404', function () {
    [$owner] = dashboardOwner();

    $this->actingAs($owner)
        ->get('http://bukan-tenant.fluxa.test/dashboard')
        ->assertNotFound();
});

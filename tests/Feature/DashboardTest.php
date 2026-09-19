<?php

use App\Models\Tenant;
use App\Models\User;

/*
 * Dashboard kini hidup di subdomain tenant, bukan di central domain.
 *
 * Assertion soal auth (tamu diarahkan ke login) sengaja belum ada di sini:
 * routes/tenant.php memang belum memasang middleware auth karena halamannya
 * masih pratinjau tampilan dengan data contoh. Assertion itu kembali bersama
 * middleware-nya, sebelum ada data asli yang bisa dilihat orang luar.
 */

function dashboardTenant(): Tenant
{
    $owner = User::factory()->create();

    $tenant = Tenant::create(['name' => 'Keluarga Uji', 'owner_id' => $owner->id]);
    $tenant->domains()->create(['domain' => 'keluarga-uji', 'is_primary' => true]);

    return $tenant;
}

test('dashboard tidak bisa diakses lewat central domain', function () {
    dashboardTenant();

    $this->get('http://fluxa.test/dashboard')->assertNotFound();
});

test('dashboard bisa diakses lewat subdomain tenant', function () {
    dashboardTenant();

    $this->get('http://keluarga-uji.fluxa.test/dashboard')->assertOk();
});

test('subdomain yang tidak terdaftar menjawab 404', function () {
    $this->get('http://bukan-tenant.fluxa.test/dashboard')->assertNotFound();
});

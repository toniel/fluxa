<?php

use App\Models\Tenant;
use App\Models\User;

/*
 * Isolasi tenant: subdomain hanya boleh dibuka anggotanya.
 *
 * Tenant di sini dibuat dengan pivot keanggotaan yang eksplisit, meniru
 * CreateTenantAction, supaya yang diuji adalah pagar keanggotaan dan bukan
 * jalan pintas owner_id.
 */

function isolationTenant(string $domain): array
{
    $owner = User::factory()->create();

    $tenant = Tenant::create(['name' => 'Tim '.$domain, 'owner_id' => $owner->getKey()]);
    $tenant->domains()->create(['domain' => $domain, 'is_primary' => true]);
    $tenant->members()->attach($owner->getKey(), ['joined_at' => now()]);

    return [$owner, $tenant];
}

test('anggota bisa membuka dashboard tenantnya', function () {
    [$owner] = isolationTenant('abc');

    $this->actingAs($owner)
        ->get('http://abc.fluxa.test/dashboard')
        ->assertOk();
});

test('user non-anggota tidak bisa membuka subdomain tenant lain', function () {
    isolationTenant('abc');
    [$outsider] = isolationTenant('def');

    // $outsider adalah anggota 'def', tapi mencoba membuka 'abc'.
    $this->actingAs($outsider)
        ->get('http://abc.fluxa.test/dashboard')
        ->assertNotFound();
});

test('user tanpa tenant apa pun tidak bisa membuka subdomain tenant', function () {
    isolationTenant('abc');
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get('http://abc.fluxa.test/dashboard')
        ->assertNotFound();
});

test('super-admin bisa membuka subdomain tenant mana pun', function () {
    isolationTenant('abc');
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->get('http://abc.fluxa.test/dashboard')
        ->assertOk();
});

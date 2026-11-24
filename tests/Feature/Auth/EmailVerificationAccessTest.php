<?php

use App\Models\Tenant;
use App\Models\User;

test('akun belum terverifikasi diarahkan ke halaman verifikasi dari area pusat', function () {
    $user = User::factory()->unverified()->create();
    $user->forceFill(['is_super_admin' => true])->save();

    $this->actingAs($user)->get('/tenants')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get('/admin')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->post('/invitations/token/accept')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get('/settings/security')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get('/settings/profile')->assertOk();
});

test('akun belum terverifikasi tidak dapat membuka area tenant', function () {
    $user = User::factory()->unverified()->create();
    $tenant = Tenant::create(['name' => 'Tim Uji', 'owner_id' => $user->getKey()]);
    $tenant->domains()->create(['domain' => 'tim-uji', 'is_primary' => true]);

    $this->actingAs($user)
        ->get('http://tim-uji.fluxa.test/dashboard')
        ->assertRedirect('http://tim-uji.fluxa.test/email/verify');
});

test('akun terverifikasi dapat membuka pemilih tenant', function () {
    $this->actingAs(User::factory()->create())
        ->get('/tenants')
        ->assertOk();
});

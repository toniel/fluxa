<?php

use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

/**
 * Halaman yang masih memakai data contoh ikut ter-render di belakang auth.
 * Mengunjunginya juga menutup controller pratinjau dan SampleData, supaya
 * kedua-duanya tidak jadi file 0% bagi gate coverage.
 */
test('halaman pratinjau tetap ter-render untuk anggota tenant', function (string $path, string $component) {
    ['user' => $owner] = categoryTenant('preview-uji');

    $this->actingAs($owner)
        ->get(categoryBaseUrl('preview-uji').$path)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'kantong' => ['/accounts', 'accounts/Index'],
    'transaksi' => ['/transactions', 'transactions/Index'],
    'transfer' => ['/transfers', 'transfers/Index'],
    'anggota' => ['/members', 'members/Index'],
    'langganan' => ['/billing', 'billing/Index'],
    'pengaturan tenant' => ['/settings/tenant', 'tenant/Settings'],
]);

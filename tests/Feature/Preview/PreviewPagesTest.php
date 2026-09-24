<?php

use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

/**
 * Asap halaman utama: setiap halaman inti ikut ter-render di belakang auth
 * untuk anggota tenant.
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
]);

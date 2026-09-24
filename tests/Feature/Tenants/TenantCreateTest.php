<?php

use App\Models\Category;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, PlanSeeder::class]);
});

test('user tanpa tenant bisa membuat tenant baru dan langsung masuk', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/tenants/create')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('tenants/Create'),
        );

    $this->actingAs($user)
        ->post('/tenants', ['name' => 'Keluarga Baru'])
        ->assertRedirect();

    $tenant = Tenant::query()->where('owner_id', $user->getKey())->firstOrFail();

    expect($tenant->name)->toBe('Keluarga Baru')
        ->and($tenant->subdomain())->not->toBeNull()
        ->and($tenant->members()->whereKey($user->getKey())->exists())->toBeTrue()
        ->and(Category::query()->withoutTenantScope()->where('tenant_id', $tenant->getKey())->count())->toBe(7)
        ->and(Subscription::query()->withoutTenantScope()->where('tenant_id', $tenant->getKey())->count())->toBe(1);
});

test('nama tenant wajib diisi', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tenants', ['name' => ''])
        ->assertSessionHasErrors(['name']);

    expect(Tenant::query()->count())->toBe(0);
});

test('tamu diarahkan login', function () {
    $this->get('/tenants/create')->assertRedirect('/login');
    $this->post('/tenants', ['name' => 'X'])->assertRedirect('/login');
});

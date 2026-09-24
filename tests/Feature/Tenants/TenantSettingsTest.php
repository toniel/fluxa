<?php

use App\Enums\TenantRole;
use App\Models\Account;
use App\Models\Tenant;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, PlanSeeder::class]);
});

test('halaman pengaturan memuat data tenant asli', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->get(categoryBaseUrl('keluarga-uji').'/settings/tenant')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('tenant/Settings')
                ->where('tenant.id', $tenant->getKey())
                ->where('tenant.name', 'Tim Uji')
                ->where('tenant.subdomain', 'keluarga-uji')
                ->where('tenant.member_count', 1)
                ->where('plan.slug', 'free')
                ->where('can.update', true),
        );
});

test('owner bisa mengubah nama tenant', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->patch(categoryBaseUrl('keluarga-uji').'/settings/tenant', ['name' => 'Keluarga Besar'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($tenant->refresh()->name)->toBe('Keluarga Besar');
});

test('nama kosong ditolak', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->patch(categoryBaseUrl('keluarga-uji').'/settings/tenant', ['name' => ''])
        ->assertSessionHasErrors(['name']);

    expect($tenant->refresh()->name)->toBe('Tim Uji');
});

test('admin dan member ditolak mengubah dan menghapus tenant', function (TenantRole $role) {
    ['tenant' => $tenant] = memberTenant('keluarga-uji');
    $user = categoryUser($tenant, $role);
    attachMember($tenant, $user, $role);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($user)
        ->patch($url.'/settings/tenant', ['name' => 'Dicuri'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete($url.'/settings/tenant')
        ->assertForbidden();

    expect($tenant->refresh()->name)->toBe('Tim Uji')
        ->and(Tenant::query()->count())->toBeGreaterThanOrEqual(1);
})->with([
    'admin' => [TenantRole::Admin],
    'member' => [TenantRole::Member],
]);

test('owner menghapus tenant beserta datanya', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $tenantId = $tenant->getKey();

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', accountPayload())
        ->assertRedirect();

    $this->actingAs($owner)
        ->delete(categoryBaseUrl('keluarga-uji').'/settings/tenant')
        ->assertRedirect();

    expect(Tenant::query()->find($tenantId))->toBeNull()
        ->and(Account::query()->withoutTenantScope()->where('tenant_id', $tenantId)->count())->toBe(0);
});

test('paket pro bisa ganti subdomain, alamat lama dialihkan', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant, 'pro-monthly');
    $url = categoryBaseUrl('keluarga-uji');
    $old = $tenant->subdomain();

    $this->actingAs($owner)
        ->patch($url.'/settings/tenant', ['name' => 'Tim Uji', 'subdomain' => 'keluarga-baru'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $domains = $tenant->domains()->orderBy('domain')->get();

    expect($domains)->toHaveCount(2)
        ->and($tenant->refresh()->subdomain())->toBe('keluarga-baru');

    $retired = $domains->firstWhere('domain', $old);

    expect($retired->is_primary)->toBeFalse()
        ->and($retired->redirects_to)->toBe('keluarga-baru');
});

test('paket free ditolak ganti subdomain', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant, 'free');
    $old = $tenant->subdomain();

    $this->actingAs($owner)
        ->patch(categoryBaseUrl('keluarga-uji').'/settings/tenant', ['name' => 'Tim Uji', 'subdomain' => 'mau-gratis'])
        ->assertStatus(422);

    expect($tenant->refresh()->subdomain())->toBe($old);
});

test('subdomain reserved, duplikat, dan format salah ditolak', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant, 'pro-monthly');
    $url = categoryBaseUrl('keluarga-uji');

    $payload = static fn (?string $subdomain): array => ['name' => 'Tim Uji', 'subdomain' => $subdomain];

    $this->actingAs($owner)
        ->patch($url.'/settings/tenant', $payload('www'))
        ->assertSessionHasErrors(['subdomain']);

    $this->actingAs($owner)
        ->patch($url.'/settings/tenant', $payload('AB'))
        ->assertSessionHasErrors(['subdomain']);

    $this->actingAs($owner)
        ->patch($url.'/settings/tenant', $payload('sudah ada!'))
        ->assertSessionHasErrors(['subdomain']);

    ['user' => $secondOwner, 'tenant' => $secondTenant] = memberTenant('komunitas-uji');
    billingSubscription($secondTenant, 'pro-monthly');

    $this->actingAs($secondOwner)
        ->patch(categoryBaseUrl('komunitas-uji').'/settings/tenant', $payload($tenant->subdomain()))
        ->assertSessionHasErrors(['subdomain']);

    expect($tenant->refresh()->subdomain())->toBe('keluarga-uji');
});

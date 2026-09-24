<?php

use App\Enums\TenantRole;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, PlanSeeder::class]);
});

function billingSubscription(Tenant $tenant, string $slug = 'free'): Subscription
{
    $plan = Plan::query()->bySlug($slug) ?? $this->fail('Paket belum di-seed');

    return Subscription::factory()->for($tenant)->create(['plan_id' => $plan->getKey()]);
}

test('seeder paket idempotent', function () {
    $this->seed(PlanSeeder::class);

    expect(Plan::query()->count())->toBe(2)
        ->and(Plan::query()->bySlug('pro-monthly')?->price)->toBe('35000.00');
});

test('owner melihat paket, pemakaian, dan perbandingan', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant);

    $this->actingAs($owner)
        ->get(categoryBaseUrl('keluarga-uji').'/billing')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('billing/Index')
                ->where('subscription.plan.slug', 'free')
                ->where('subscription.status', 'active')
                ->where('usage.members', 1)
                ->where('usage.max_members', 3)
                ->where('can.manage', true)
                ->has('comparison', 4)
                ->has('upgrade'),
        );
});

test('admin dan member ditutup dari halaman billing', function (TenantRole $role) {
    ['tenant' => $tenant, 'user' => $owner] = memberTenant('keluarga-uji');
    $user = $role === TenantRole::Owner ? $owner : categoryUser($tenant, $role);
    billingSubscription($tenant);

    $this->actingAs($user)
        ->get(categoryBaseUrl('keluarga-uji').'/billing')
        ->assertForbidden();
})->with([
    'admin' => [TenantRole::Admin],
    'member' => [TenantRole::Member],
]);

test('toggle bolak-balik free dan pro', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant);
    $url = categoryBaseUrl('keluarga-uji').'/billing/toggle';

    $this->actingAs($owner)
        ->post($url)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $subscription = Subscription::query()->firstOrFail();

    expect($subscription->plan->slug)->toBe('pro-monthly')
        ->and($subscription->isActive())->toBeTrue();

    $this->actingAs($owner)
        ->post($url)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($subscription->refresh()->plan->slug)->toBe('free');
});

test('toggle membuat langganan bila belum ada', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/billing/toggle')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Subscription::query()->count())->toBe(1)
        ->and(Subscription::query()->firstOrFail()->plan->slug)->toBe('pro-monthly');
});

test('bukan owner ditolak toggle', function (TenantRole $role) {
    ['tenant' => $tenant, 'user' => $owner] = memberTenant('keluarga-uji');
    $user = $role === TenantRole::Owner ? $owner : categoryUser($tenant, $role);
    billingSubscription($tenant);

    $this->actingAs($user)
        ->post(categoryBaseUrl('keluarga-uji').'/billing/toggle')
        ->assertForbidden();

    expect(Subscription::query()->firstOrFail()->plan->slug)->toBe('free');
})->with([
    'admin' => [TenantRole::Admin],
    'member' => [TenantRole::Member],
]);

test('helper fitur paket dan status aktif', function () {
    $free = Plan::query()->bySlug('free');

    expect($free)->not->toBeNull();

    expect($free->feature('max_members'))->toBe(3)
        ->and($free->feature('tidak-ada', 'bawaan'))->toBe('bawaan')
        ->and($free->billing_period->label())->toBe('bulan');
});

test('turun ke free mengembalikan subdomain acak semula', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant, 'pro-monthly');
    $random = $tenant->subdomain();
    $url = categoryBaseUrl($random);

    $this->actingAs($owner)
        ->patch($url.'/settings/tenant', ['name' => 'Tim Uji', 'subdomain' => 'keluarga-pro'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($tenant->refresh()->subdomain())->toBe('keluarga-pro');

    $this->actingAs($owner)
        ->post($url.'/billing/toggle')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($tenant->refresh()->subdomain())->toBe($random);

    $custom = $tenant->domains()->where('domain', 'keluarga-pro')->firstOrFail();

    expect($custom->is_primary)->toBeFalse()
        ->and($custom->redirects_to)->toBe($random);
});

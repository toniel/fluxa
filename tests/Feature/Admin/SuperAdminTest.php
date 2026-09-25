<?php

use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, PlanSeeder::class]);
});

function superAdmin(): User
{
    $user = User::factory()->create();

    $user->forceFill(['is_super_admin' => true])->save();

    return $user->refresh();
}

test('command promote mengangkat dan mencabut super-admin', function () {
    $user = User::factory()->create(['email' => 'bos@fluxa.test']);

    $this->artisan('user:promote', ['email' => 'bos@fluxa.test'])
        ->assertSuccessful();

    expect($user->refresh()->isSuperAdmin())->toBeTrue();

    $this->artisan('user:promote', ['email' => 'bos@fluxa.test', '--revoke' => true])
        ->assertSuccessful();

    expect($user->refresh()->isSuperAdmin())->toBeFalse();

    $this->artisan('user:promote', ['email' => 'tak-ada@fluxa.test'])
        ->assertFailed();
});

test('bukan super-admin menjawab 404 di area admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertNotFound();
});

test('tamu diarahkan login', function () {
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/admin/users')->assertRedirect('/login');
});

test('dasbor menampilkan angka lintas tenant yang benar', function () {
    $admin = superAdmin();
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant, 'pro-monthly');

    User::factory()->create(['created_at' => now()->subDays(2)]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('admin/Dashboard')
                ->where('totals.users', 3)
                ->where('totals.users_new_7d', 3)
                ->where('totals.tenants', 1)
                ->where('totals.subscriptions_active', 1)
                ->where('totals.mrr', '35000.00')
                ->where('plans.1.slug', 'pro-monthly')
                ->where('plans.1.active_count', 1)
                ->has('recent_tenants', 1)
                ->where('recent_tenants.0.name', 'Tim Uji')
                ->has('signups', 8),
        );
});

test('daftar pengguna bisa dicari dan menandai super-admin', function () {
    $admin = superAdmin();
    User::factory()->create(['name' => 'Sinta Admin', 'email' => 'sinta@fluxa.test']);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('admin/Users')
                ->where('users.total', 2)
                ->has('users.data', 2),
        );

    $this->actingAs($admin)
        ->get('/admin/users?search=sinta')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('admin/Users')
                ->has('users.data', 1)
                ->where('users.data.0.email', 'sinta@fluxa.test'),
        );
});

test('bukan super-admin tidak bisa memakai fitur admin walau tahu url', function () {
    ['user' => $owner] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->get('/admin/users?search=sinta')
        ->assertNotFound();
});

test('super-admin melihat daftar tenant beserta paketnya', function () {
    $admin = superAdmin();
    ['tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant, 'pro-monthly');

    $this->actingAs($admin)
        ->get('/admin/tenants')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('admin/Tenants')
                ->where('tenants.total', 1)
                ->has('tenants.data', 1)
                ->where('tenants.data.0.name', 'Tim Uji')
                ->where('tenants.data.0.subdomain', 'keluarga-uji')
                ->where('tenants.data.0.member_count', 1)
                ->where('tenants.data.0.plan_slug', 'pro-monthly'),
        );
});

test('super-admin mengeset paket tenant ke pro atau free', function () {
    $admin = superAdmin();
    ['tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant, 'free');

    $this->actingAs($admin)
        ->put("/admin/tenants/{$tenant->getKey()}", ['plan_slug' => 'pro-monthly'])
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionHasNoErrors();

    expect(Subscription::query()->latest('id')->firstOrFail()->plan->slug)
        ->toBe('pro-monthly');

    $this->actingAs($admin)
        ->put("/admin/tenants/{$tenant->getKey()}", ['plan_slug' => 'free'])
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionHasNoErrors();

    expect(Subscription::query()->latest('id')->firstOrFail()->plan->slug)
        ->toBe('free');
});

test('super-admin tidak bisa mengeset paket yang tidak aktif', function () {
    $admin = superAdmin();
    ['tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant, 'free');

    $this->actingAs($admin)
        ->put("/admin/tenants/{$tenant->getKey()}", ['plan_slug' => 'tidak-ada'])
        ->assertSessionHasErrors('plan_slug');

    expect(Subscription::query()->latest('id')->firstOrFail()->plan->slug)
        ->toBe('free');
});

test('penurunan ke free dari admin mengembalikan subdomain acak semula', function () {
    $admin = superAdmin();
    ['tenant' => $tenant] = memberTenant('keluarga-uji');
    billingSubscription($tenant, 'pro-monthly');
    $random = $tenant->subdomain();

    $this->actingAs($admin)
        ->put("/admin/tenants/{$tenant->getKey()}", ['plan_slug' => 'free'])
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionHasNoErrors();

    expect($tenant->refresh()->subdomain())->toBe($random);
});

test('bukan super-admin ditutup dari daftar dan pengesetan paket tenant', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->get('/admin/tenants')
        ->assertNotFound();

    $this->actingAs($owner)
        ->put("/admin/tenants/{$tenant->getKey()}", ['plan_slug' => 'pro-monthly'])
        ->assertNotFound();
});

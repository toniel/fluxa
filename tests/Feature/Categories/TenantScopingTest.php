<?php

use App\Enums\CategoryType;
use App\Exceptions\TenantContextMissingException;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;

function scopingTenant(string $domain): Tenant
{
    $owner = User::factory()->create();

    $tenant = Tenant::create(['name' => 'Tim Uji', 'owner_id' => $owner->getKey()]);
    $tenant->domains()->create(['domain' => $domain, 'is_primary' => true]);

    return $tenant;
}

test('membuat kategori tanpa tenant aktif melempar, bukan menciptakan baris yatim', function () {
    expect(
        fn () => Category::create(['name' => 'Yatim', 'type' => CategoryType::Expense->value]),
    )->toThrow(TenantContextMissingException::class);
});

test('runFor mengisi tenant_id untuk pembuatan di luar request', function () {
    $tenant = scopingTenant('keluarga-uji');

    $category = app(TenantContext::class)->runFor(
        $tenant,
        fn () => Category::create(['name' => 'Makan', 'type' => CategoryType::Expense->value]),
    );

    expect($category->tenant_id)->toBe($tenant->getKey());
});

test('query dalam konteks tenant A tidak melihat baris tenant B', function () {
    $a = scopingTenant('keluarga-uji');
    $b = scopingTenant('komunitas-uji');

    app(TenantContext::class)->runFor($a, fn () => Category::create(['name' => 'Untuk A', 'type' => CategoryType::Expense->value]));
    app(TenantContext::class)->runFor($b, fn () => Category::create(['name' => 'Untuk B', 'type' => CategoryType::Expense->value]));

    $countA = app(TenantContext::class)->runFor($a, fn () => Category::query()->count());

    expect($countA)->toBe(1)
        ->and(Category::query()->withoutTenantScope()->count())->toBe(2);
});

test('withoutScope membuka kunci tenant untuk query lintas tenant', function () {
    $a = scopingTenant('keluarga-uji');
    $b = scopingTenant('komunitas-uji');

    app(TenantContext::class)->runFor($a, fn () => Category::create(['name' => 'Untuk A', 'type' => CategoryType::Expense->value]));
    app(TenantContext::class)->runFor($b, fn () => Category::create(['name' => 'Untuk B', 'type' => CategoryType::Expense->value]));

    $all = app(TenantContext::class)->withoutScope(fn () => Category::query()->count());

    expect($all)->toBe(2);
});

test('ofType menyaring kategori menurut jenisnya', function () {
    $tenant = scopingTenant('keluarga-uji');

    app(TenantContext::class)->runFor($tenant, function () {
        Category::create(['name' => 'Gaji', 'type' => CategoryType::Income->value]);
        Category::create(['name' => 'Makan', 'type' => CategoryType::Expense->value]);
    });

    $expense = app(TenantContext::class)->runFor(
        $tenant,
        fn () => Category::query()->ofType(CategoryType::Expense)->pluck('name')->all(),
    );

    expect($expense)->toBe(['Makan']);
});

test('filter enum lewat filter() memakai filter lacodix', function () {
    $tenant = scopingTenant('keluarga-uji');

    app(TenantContext::class)->runFor($tenant, function () {
        Category::create(['name' => 'Gaji', 'type' => CategoryType::Income->value]);
        Category::create(['name' => 'Makan', 'type' => CategoryType::Expense->value]);
    });

    $income = app(TenantContext::class)->runFor(
        $tenant,
        fn () => Category::query()->filter(['type' => 'income'])->pluck('name')->all(),
    );

    expect($income)->toBe(['Gaji']);
});

test('konteks bisa di-set dan dilupakan secara eksplisit', function () {
    $tenant = scopingTenant('keluarga-uji');

    app(TenantContext::class)->set($tenant);

    expect(app(TenantContext::class)->tenant())->toBe($tenant);

    app(TenantContext::class)->forget();

    expect(app(TenantContext::class)->tenant())->toBeNull();
});

test('factory kategori memakai for() untuk mengisi tenant_id', function () {
    $tenant = scopingTenant('keluarga-uji');

    $category = Category::factory()->ofType(CategoryType::Expense)->for($tenant)->create();

    expect($category->tenant_id)->toBe($tenant->getKey());
});

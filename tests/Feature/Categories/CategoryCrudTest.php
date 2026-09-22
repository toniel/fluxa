<?php

use App\Enums\CategoryType;
use App\Enums\TenantRole;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

/**
 * Tenant uji beserta pemiliknya.
 *
 * @return array{user: User, tenant: Tenant}
 */
function categoryTenant(string $domain): array
{
    $user = User::factory()->create();

    $tenant = Tenant::create(['name' => 'Tim Uji', 'owner_id' => $user->getKey()]);
    $tenant->domains()->create(['domain' => $domain, 'is_primary' => true]);

    categoryRole($tenant, $user, TenantRole::Owner);

    return ['user' => $user, 'tenant' => $tenant];
}

/**
 * Menaruh role spatie untuk user di tenant itu. Dijalankan lewat
 * tenancy()->initialize() supaya assignment ter-scope per team seperti alur
 * nyata.
 */
function categoryRole(Tenant $tenant, User $user, TenantRole $role): void
{
    $previous = tenant();

    try {
        tenancy()->initialize($tenant);

        $user->syncRoles([$role->value]);
    } finally {
        $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
    }
}

/**
 * User yang sudah login untuk tenant ini dengan role tertentu.
 */
function categoryUser(Tenant $tenant, TenantRole $role, string $name = 'Anggota'): User
{
    $user = User::factory()->create(['name' => $name]);

    categoryRole($tenant, $user, $role);

    return $user;
}

function categoryBaseUrl(string $domain): string
{
    return "http://{$domain}.fluxa.test";
}

function categoryPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Makan',
        'type' => CategoryType::Expense->value,
        'icon' => 'utensils',
        'is_default' => false,
    ], $overrides);
}

test('daftar kategori hanya menampilkan milik tenant sendiri', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect();

    $this->actingAs($owner)
        ->get(categoryBaseUrl('keluarga-uji').'/categories')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('categories/Index')
                ->has('categories', 1)
                ->where('categories.0.name', 'Makan')
                ->where('categories.0.type', 'expense')
                ->where('can.create', true)
                ->where('can.manage', true),
        );
});

test('owner dan admin bisa membuat kategori', function (TenantRole $role) {
    ['tenant' => $tenant] = categoryTenant('keluarga-uji');
    $user = categoryUser($tenant, $role);

    $this->actingAs($user)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $row = Category::query()->where('name', 'Makan')->first();

    expect($row)->not->toBeNull()
        ->and($row->tenant_id)->toBe($tenant->getKey());
})->with([
    'owner' => [TenantRole::Owner],
    'admin' => [TenantRole::Admin],
]);

test('halaman buat dan ubah memuat jenis serta daftar ikon', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->get(categoryBaseUrl('keluarga-uji').'/categories/create')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('categories/Create')
                ->has('types', 2)
                ->has('icons'),
        );

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect();

    $category = Category::query()->firstOrFail();

    $this->actingAs($owner)
        ->get(categoryBaseUrl('keluarga-uji').'/categories/'.$category->getKey().'/edit')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('categories/Edit')
                ->where('category.id', $category->getKey())
                ->where('category.name', 'Makan')
                ->has('types', 2)
                ->has('icons'),
        );
});

test('halaman buat dan ubah menutup akses member', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');

    $this->actingAs($member)
        ->get(categoryBaseUrl('keluarga-uji').'/categories/create')
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect();

    $category = Category::query()->firstOrFail();

    $this->actingAs($member)
        ->get(categoryBaseUrl('keluarga-uji').'/categories/'.$category->getKey().'/edit')
        ->assertForbidden();
});

test('member ditolak membuat, mengubah, dan menghapus kategori', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');

    $this->actingAs($member)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect();

    $category = Category::query()->firstOrFail();

    $this->actingAs($member)
        ->put(categoryBaseUrl('keluarga-uji').'/categories/'.$category->getKey(), categoryPayload(['name' => 'Makmur']))
        ->assertForbidden();

    $this->actingAs($member)
        ->delete(categoryBaseUrl('keluarga-uji').'/categories/'.$category->getKey())
        ->assertForbidden();

    expect(Category::query()->count())->toBe(1);
});

test('payload kosong atau jenis tidak dikenal ditolak', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', [])
        ->assertSessionHasErrors(['name', 'type']);

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload(['type' => 'pulsa']))
        ->assertSessionHasErrors(['type']);

    expect(Category::query()->count())->toBe(0);
});

test('nama kategori tidak boleh kembar untuk jenis yang sama di satu tenant', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect();

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertSessionHasErrors(['name']);

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload(['type' => CategoryType::Income->value]))
        ->assertRedirect();

    expect(Category::query()->count())->toBe(2);
});

test('tenant lain bebas memakai nama yang sama', function () {
    ['user' => $firstOwner] = categoryTenant('keluarga-uji');

    $this->actingAs($firstOwner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect();

    ['user' => $secondOwner] = categoryTenant('komunitas-uji');

    $this->actingAs($secondOwner)
        ->post(categoryBaseUrl('komunitas-uji').'/categories', categoryPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Category::query()->withoutTenantScope()->count())->toBe(2);
});

test('mengubah kategori memakai id di dalam payload tidak menimpa baris lain', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload(['name' => 'Pertama']))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload(['name' => 'Kedua', 'type' => CategoryType::Income->value]))
        ->assertRedirect();

    $first = Category::query()->where('name', 'Pertama')->firstOrFail();
    $second = Category::query()->where('name', 'Kedua')->firstOrFail();

    $this->actingAs($owner)
        ->put(categoryBaseUrl('keluarga-uji').'/categories/'.$first->getKey(), [
            ...categoryPayload(['name' => 'Diganti', 'type' => CategoryType::Expense->value]),
            'id' => $second->getKey(),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $second->refresh();

    expect($second->name)->toBe('Kedua');
});

test('mengubah kategori mengubah baris yang sama tanpa menambah jumlah baris', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect();

    $category = Category::query()->firstOrFail();

    $this->actingAs($owner)
        ->put(categoryBaseUrl('keluarga-uji').'/categories/'.$category->getKey(), categoryPayload(['name' => 'Makan Siang']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Category::query()->count())->toBe(1)
        ->and(Category::query()->firstOrFail()->name)->toBe('Makan Siang');
});

test('menghapus kategori menyisakan nol baris', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect();

    $category = Category::query()->firstOrFail();

    $this->actingAs($owner)
        ->delete(categoryBaseUrl('keluarga-uji').'/categories/'.$category->getKey())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Category::query()->count())->toBe(0);
});

test('baris milik tenant lain menjawab 404, bukan 403', function () {
    ['user' => $firstOwner] = categoryTenant('keluarga-uji');

    $this->actingAs($firstOwner)
        ->post(categoryBaseUrl('keluarga-uji').'/categories', categoryPayload())
        ->assertRedirect();

    $category = Category::query()->firstOrFail();

    ['user' => $secondOwner] = categoryTenant('komunitas-uji');

    $this->actingAs($secondOwner)
        ->get(categoryBaseUrl('komunitas-uji').'/categories/'.$category->getKey().'/edit')
        ->assertNotFound();

    $this->actingAs($secondOwner)
        ->put(categoryBaseUrl('komunitas-uji').'/categories/'.$category->getKey(), categoryPayload())
        ->assertNotFound();

    $this->actingAs($secondOwner)
        ->delete(categoryBaseUrl('komunitas-uji').'/categories/'.$category->getKey())
        ->assertNotFound();
});

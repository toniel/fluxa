<?php

use App\Enums\AccountType;
use App\Enums\TenantRole;
use App\Models\Account;
use App\Support\TenantContext;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

function accountPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Kas Harian',
        'type' => AccountType::Cash->value,
        'initial_balance' => '50000',
        'icon' => null,
        'color' => null,
        'is_archived' => false,
    ], $overrides);
}

test('daftar kantong hanya menampilkan milik tenant sendiri', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/accounts', accountPayload())
        ->assertRedirect();

    $this->actingAs($owner)
        ->get($url.'/accounts')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Index')
                ->has('accounts', 1)
                ->where('accounts.0.name', 'Kas Harian')
                ->where('accounts.0.balance', '50000.00')
                ->where('can.create', true)
                ->where('can.manage', true),
        );
});

test('membuat kantong mengisi balance sama dengan saldo awal', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', accountPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $account = Account::query()->where('name', 'Kas Harian')->first();

    expect($account)->not->toBeNull()
        ->and($account->tenant_id)->toBe($tenant->getKey())
        ->and($account->balance)->toBe('50000.00')
        ->and($account->initial_balance)->toBe('50000.00')
        ->and($account->created_by)->toBe($owner->getKey());
});

test('member boleh membuat kantong', function () {
    ['tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');

    $this->actingAs($member)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', accountPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Account::query()->where('name', 'Kas Harian')->exists())->toBeTrue();
});

test('member ditolak mengubah, mengarsipkan, dan menghapus kantong', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', accountPayload())
        ->assertRedirect();

    $account = Account::query()->firstOrFail();
    $url = categoryBaseUrl('keluarga-uji').'/accounts/'.$account->getKey();

    $this->actingAs($member)
        ->put($url, accountPayload(['name' => 'Dicuri']))
        ->assertForbidden();

    $this->actingAs($member)
        ->patch($url.'/archive')
        ->assertForbidden();

    $this->actingAs($member)
        ->delete($url)
        ->assertForbidden();

    expect(Account::query()->count())->toBe(1);
});

test('mengubah kantong tidak menyentuh balance atau saldo awal', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', accountPayload(['initial_balance' => '250000']))
        ->assertRedirect();

    $account = Account::query()->firstOrFail();

    $this->actingAs($owner)
        ->put(categoryBaseUrl('keluarga-uji').'/accounts/'.$account->getKey(), [
            'name' => 'Kas Ganti',
            'type' => AccountType::Bank->value,
            'initial_balance' => '999999',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $account->refresh();

    expect(Account::query()->count())->toBe(1)
        ->and($account->name)->toBe('Kas Ganti')
        ->and($account->type)->toBe(AccountType::Bank)
        ->and($account->initial_balance)->toBe('250000.00')
        ->and($account->balance)->toBe('250000.00');
});

test('payload kosong atau saldo awal negatif ditolak', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/accounts', [])
        ->assertSessionHasErrors(['name', 'type', 'initial_balance']);

    $this->actingAs($owner)
        ->post($url.'/accounts', accountPayload(['initial_balance' => '-1']))
        ->assertSessionHasErrors(['initial_balance']);

    expect(Account::query()->count())->toBe(0);
});

test('arsip dan pengembalian kantong mengubah tanda is_archived', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', accountPayload())
        ->assertRedirect();

    $account = Account::query()->firstOrFail();
    $url = categoryBaseUrl('keluarga-uji').'/accounts/'.$account->getKey();

    $this->actingAs($owner)
        ->patch($url.'/archive')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($account->refresh()->is_archived)->toBeTrue();

    $this->actingAs($owner)
        ->patch($url.'/archive')
        ->assertRedirect();

    expect($account->refresh()->is_archived)->toBeFalse();
});

test('mengubah kantong memakai id di dalam payload tidak menimpa baris lain', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/accounts', accountPayload(['name' => 'Pertama']))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/accounts', accountPayload(['name' => 'Kedua', 'type' => AccountType::Bank->value]))
        ->assertRedirect();

    $first = Account::query()->where('name', 'Pertama')->firstOrFail();
    $second = Account::query()->where('name', 'Kedua')->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/accounts/'.$first->getKey(), [
            'name' => 'Diganti',
            'type' => AccountType::Cash->value,
            'initial_balance' => '0',
            'id' => $second->getKey(),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $second->refresh();

    expect($second->name)->toBe('Kedua');
});

test('menghapus kantong menyisakan nol baris', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', accountPayload())
        ->assertRedirect();

    $account = Account::query()->firstOrFail();

    $this->actingAs($owner)
        ->delete(categoryBaseUrl('keluarga-uji').'/accounts/'.$account->getKey())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Account::query()->count())->toBe(0);
});

test('baris milik tenant lain menjawab 404, bukan 403', function () {
    ['user' => $firstOwner] = categoryTenant('keluarga-uji');

    $this->actingAs($firstOwner)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', accountPayload())
        ->assertRedirect();

    $account = Account::query()->firstOrFail();

    ['user' => $secondOwner] = categoryTenant('komunitas-uji');
    $url = categoryBaseUrl('komunitas-uji').'/accounts/'.$account->getKey();

    $this->actingAs($secondOwner)
        ->get($url.'/edit')
        ->assertNotFound();

    $this->actingAs($secondOwner)
        ->put($url, accountPayload())
        ->assertNotFound();

    $this->actingAs($secondOwner)
        ->patch($url.'/archive')
        ->assertNotFound();

    $this->actingAs($secondOwner)
        ->delete($url)
        ->assertNotFound();
});

test('factory kantong memakai for() untuk mengisi tenant_id', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $account = Account::factory()
        ->ofType(AccountType::Cash)
        ->for($tenant)
        ->create(['created_by' => $owner->getKey()]);

    expect($account->tenant_id)->toBe($tenant->getKey());
});

test('halaman buat dan ubah memuat daftar jenis', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->get($url.'/accounts/create')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Create')
                ->has('types', 4),
        );

    $this->actingAs($owner)
        ->post($url.'/accounts', accountPayload())
        ->assertRedirect();

    $account = Account::query()->firstOrFail();

    $this->actingAs($owner)
        ->get($url.'/accounts/'.$account->getKey().'/edit')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Edit')
                ->where('account.id', $account->getKey())
                ->where('account.initial_balance', '50000.00')
                ->has('types', 4),
        );
});

test('member boleh membuka halaman buat, tapi halaman ubah tertutup', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    $url = categoryBaseUrl('keluarga-uji');

    // Semua role boleh membuat kantong (matriks PRD), jadi halaman buat
    // terbuka untuk member.
    $this->actingAs($member)
        ->get($url.'/accounts/create')
        ->assertOk();

    $this->actingAs($owner)
        ->post($url.'/accounts', accountPayload())
        ->assertRedirect();

    $account = Account::query()->firstOrFail();

    $this->actingAs($member)
        ->get($url.'/accounts/'.$account->getKey().'/edit')
        ->assertForbidden();
});

test('builder menyaring kantong aktif dan terarsip', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    Account::factory()->for($tenant)->create(['created_by' => $owner->getKey()]);
    Account::factory()->for($tenant)->archived()->create(['created_by' => $owner->getKey()]);

    expect(Account::query()->active()->count())->toBe(1)
        ->and(Account::query()->archived()->count())->toBe(1);
});

test('filter jenis lewat filter() memakai filter lacodix', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    Account::factory()->for($tenant)->ofType(AccountType::Cash)->create(['created_by' => $owner->getKey()]);
    Account::factory()->for($tenant)->ofType(AccountType::Bank)->create(['created_by' => $owner->getKey()]);

    $cash = app(TenantContext::class)->runFor(
        $tenant,
        fn () => Account::query()->filter(['type' => 'cash'])->pluck('type')->map->value->all(),
    );

    expect($cash)->toBe(['cash']);
});

test('relasi creator menunjuk user yang membuat kantong', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $account = Account::factory()->for($tenant)->create(['created_by' => $owner->getKey()]);

    expect($account->creator->is($owner))->toBeTrue();
});

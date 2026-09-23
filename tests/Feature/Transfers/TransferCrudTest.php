<?php

use App\Enums\AccountType;
use App\Enums\TenantRole;
use App\Models\Account;
use App\Models\Tenant;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

/**
 * @return array{from: Account, to: Account}
 */
function transferFixtures(Tenant $tenant, User $owner): array
{
    $make = fn (string $name, string $balance): Account => Account::factory()
        ->for($tenant)
        ->ofType(AccountType::Cash)
        ->create(['name' => $name, 'initial_balance' => $balance, 'balance' => $balance.'.00', 'created_by' => $owner->getKey()]);

    return ['from' => $make('Kas Harian', '500000'), 'to' => $make('Bank', '1000000')];
}

function transferPayload(Account $from, Account $to, array $overrides = []): array
{
    return array_merge([
        'from_account_id' => $from->getKey(),
        'to_account_id' => $to->getKey(),
        'amount' => '100000',
        'description' => 'Isi ulang kas',
        'transfer_date' => '2026-09-19',
    ], $overrides);
}

test('daftar transfer hanya menampilkan milik tenant sendiri', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $this->actingAs($owner)
        ->get($url.'/transfers')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transfers/Index')
                ->has('transfers', 1)
                ->where('transfers.0.amount', '100000.00')
                ->where('transfers.0.from_account_name', 'Kas Harian')
                ->where('transfers.0.to_account_name', 'Bank')
                ->where('transfers.0.can_edit', true)
                ->where('can.create', true),
        );
});

test('semua role boleh membuat transfer', function (TenantRole $role) {
    ['tenant' => $tenant, 'user' => $owner] = categoryTenant('keluarga-uji');
    $user = $role === TenantRole::Owner ? $owner : categoryUser($tenant, $role);
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);

    $this->actingAs($user)
        ->post(categoryBaseUrl('keluarga-uji').'/transfers', transferPayload($from, $to))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Transfer::query()->count())->toBe(1);
})->with([
    'owner' => [TenantRole::Owner],
    'admin' => [TenantRole::Admin],
    'member' => [TenantRole::Member],
]);

test('transfer memindah saldo tanpa mengubah total', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/transfers', transferPayload($from, $to))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($from->refresh()->balance)->toBe('400000.00')
        ->and($to->refresh()->balance)->toBe('1100000.00');
});

test('mengubah nominal mengoreksi kedua saldo tanpa menambah baris', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $transfer = Transfer::query()->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/transfers/'.$transfer->getKey(), transferPayload($from, $to, ['amount' => '50000']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Transfer::query()->count())->toBe(1)
        ->and($from->refresh()->balance)->toBe('450000.00')
        ->and($to->refresh()->balance)->toBe('1050000.00');
});

test('ganti pasangan akun mengoreksi ketiga saldo yang terlibat', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);

    $third = Account::factory()->for($tenant)->ofType(AccountType::Cash)->create([
        'name' => 'GoPay', 'initial_balance' => '200000', 'balance' => '200000.00', 'created_by' => $owner->getKey(),
    ]);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $transfer = Transfer::query()->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/transfers/'.$transfer->getKey(), transferPayload($to, $third))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($from->refresh()->balance)->toBe('500000.00')
        ->and($to->refresh()->balance)->toBe('900000.00')
        ->and($third->refresh()->balance)->toBe('300000.00');
});

test('menghapus transfer mengembalikan kedua saldo', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $transfer = Transfer::query()->firstOrFail();

    $this->actingAs($owner)
        ->delete($url.'/transfers/'.$transfer->getKey())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($from->refresh()->balance)->toBe('500000.00')
        ->and($to->refresh()->balance)->toBe('1000000.00')
        ->and(Transfer::query()->count())->toBe(0)
        ->and(Transfer::query()->withTrashed()->count())->toBe(1);
});

test('member bisa mengubah miliknya sendiri tapi tidak milik orang lain', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($member)
        ->post($url.'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $own = Transfer::query()->firstOrFail();

    $this->actingAs($member)
        ->put($url.'/transfers/'.$own->getKey(), transferPayload($from, $to, ['amount' => '50000']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to, ['amount' => '10000']))
        ->assertRedirect();

    $other = Transfer::query()->latest('id')->firstOrFail();

    $this->actingAs($member)
        ->put($url.'/transfers/'.$other->getKey(), transferPayload($from, $to, ['amount' => '1']))
        ->assertForbidden();

    $this->actingAs($member)
        ->delete($url.'/transfers/'.$other->getKey())
        ->assertForbidden();

    expect($other->refresh()->amount)->toBe('10000.00');
});

test('payload tidak valid ditolak tanpa menggerakkan saldo', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transfers', [])
        ->assertSessionHasErrors(['from_account_id', 'to_account_id', 'amount', 'transfer_date']);

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $from))
        ->assertSessionHasErrors(['to_account_id']);

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to, ['amount' => '-5']))
        ->assertSessionHasErrors(['amount']);

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to, ['transfer_date' => '2999-01-01']))
        ->assertSessionHasErrors(['transfer_date']);

    expect(Transfer::query()->count())->toBe(0)
        ->and($from->refresh()->balance)->toBe('500000.00')
        ->and($to->refresh()->balance)->toBe('1000000.00');
});

test('kantong arsip, milik tenant lain, atau utang ditolak', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $from->update(['is_archived' => true]);

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to))
        ->assertSessionHasErrors(['from_account_id']);

    ['user' => $otherOwner, 'tenant' => $otherTenant] = categoryTenant('komunitas-uji');
    $foreign = Account::factory()->for($otherTenant)->ofType(AccountType::Cash)->create(['created_by' => $otherOwner->getKey()]);

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($foreign, $to))
        ->assertSessionHasErrors(['from_account_id']);

    $card = Account::factory()->for($tenant)->ofType(AccountType::CreditCard)->create(['created_by' => $owner->getKey()]);

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($to, $card))
        ->assertSessionHasErrors(['to_account_id']);

    expect(Transfer::query()->count())->toBe(0);
});

test('mengubah transfer memakai id di dalam payload tidak menimpa baris lain', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to, ['amount' => '10000']))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to, ['amount' => '20000']))
        ->assertRedirect();

    $first = Transfer::query()->oldest('id')->firstOrFail();
    $second = Transfer::query()->latest('id')->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/transfers/'.$first->getKey(), [
            ...transferPayload($from, $to, ['amount' => '99999']),
            'id' => $second->getKey(),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($second->refresh()->amount)->toBe('20000.00');
});

test('baris milik tenant lain menjawab 404, bukan 403', function () {
    ['user' => $firstOwner, 'tenant' => $firstTenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($firstTenant, $firstOwner);

    $this->actingAs($firstOwner)
        ->post(categoryBaseUrl('keluarga-uji').'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $transfer = Transfer::query()->firstOrFail();

    ['user' => $secondOwner] = categoryTenant('komunitas-uji');
    $url = categoryBaseUrl('komunitas-uji').'/transfers/'.$transfer->getKey();

    $this->actingAs($secondOwner)->get($url.'/edit')->assertNotFound();
    $this->actingAs($secondOwner)->put($url, transferPayload($from, $to))->assertNotFound();
    $this->actingAs($secondOwner)->delete($url)->assertNotFound();
});

test('halaman buat dan ubah memuat pilihan kantong', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->get($url.'/transfers/create')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transfers/Create')
                ->has('accounts', 2),
        );

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $transfer = Transfer::query()->firstOrFail();

    $this->actingAs($owner)
        ->get($url.'/transfers/'.$transfer->getKey().'/edit')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transfers/Edit')
                ->where('transfer.id', $transfer->getKey())
                ->has('accounts', 2),
        );
});

test('builder dan policy transfer mengikuti aturan transaksi', function () {
    ['tenant' => $tenant, 'user' => $owner] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);

    $transfer = Transfer::factory()->for($tenant)->create([
        'from_account_id' => $from->getKey(),
        'to_account_id' => $to->getKey(),
        'transfer_date' => '2026-09-10',
        'created_by' => $owner->getKey(),
    ]);

    expect(Transfer::query()->involvingAccount($from->getKey())->count())->toBe(1)
        ->and(Transfer::query()->betweenDates(Carbon\Carbon::parse('2026-09-01'), Carbon\Carbon::parse('2026-09-30'))->count())->toBe(1)
        ->and(Transfer::query()->latestFirst()->firstOrFail()->is($transfer))->toBeTrue()
        ->and($transfer->fromAccount->is($from))->toBeTrue()
        ->and($transfer->toAccount->is($to))->toBeTrue()
        ->and($transfer->creator->is($owner))->toBeTrue();

    tenancy()->initialize($tenant);

    try {
        expect(Gate::forUser($owner)->allows('update', $transfer))->toBeTrue()
            ->and(Gate::forUser($member)->allows('create', Transfer::class))->toBeTrue()
            ->and(Gate::forUser($member)->allows('update', $transfer))->toBeFalse();
    } finally {
        tenancy()->end();
    }
});

test('halaman detail menampilkan transfer milik tenant sendiri', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $transfer = Transfer::query()->firstOrFail();

    $this->actingAs($owner)
        ->get($url.'/transfers/'.$transfer->getKey())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transfers/Show')
                ->where('transfer.id', $transfer->getKey())
                ->where('transfer.amount', '100000.00')
                ->where('transfer.from_account_name', 'Kas Harian')
                ->where('transfer.to_account_name', 'Bank')
                ->where('transfer.can_edit', true)
                ->where('transfer.can_delete', true),
        );
});

test('member boleh membuka detail transfer milik orang lain tanpa tombol aksi', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    ['from' => $from, 'to' => $to] = transferFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $transfer = Transfer::query()->firstOrFail();

    $this->actingAs($member)
        ->get($url.'/transfers/'.$transfer->getKey())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transfers/Show')
                ->where('transfer.can_edit', false)
                ->where('transfer.can_delete', false),
        );
});

test('detail milik tenant lain menjawab 404', function () {
    ['user' => $firstOwner, 'tenant' => $firstTenant] = categoryTenant('keluarga-uji');
    ['from' => $from, 'to' => $to] = transferFixtures($firstTenant, $firstOwner);

    $this->actingAs($firstOwner)
        ->post(categoryBaseUrl('keluarga-uji').'/transfers', transferPayload($from, $to))
        ->assertRedirect();

    $transfer = Transfer::query()->firstOrFail();

    ['user' => $secondOwner] = categoryTenant('komunitas-uji');

    $this->actingAs($secondOwner)
        ->get(categoryBaseUrl('komunitas-uji').'/transfers/'.$transfer->getKey())
        ->assertNotFound();
});

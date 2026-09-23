<?php

use App\Enums\AccountType;
use App\Enums\TenantRole;
use App\Models\Account;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(PermissionSeeder::class);
});

test('riwayat memuat transaksi keluar-masuk dan transfer', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'income' => $income, 'expense' => $expense] = transactionFixtures($tenant, $owner);

    $other = Account::factory()->for($tenant)->ofType(AccountType::Cash)->create([
        'name' => 'Bank', 'initial_balance' => '0', 'balance' => '0.00', 'created_by' => $owner->getKey(),
    ]);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '25000', 'transaction_date' => '2026-09-18']))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $income, ['amount' => '100000', 'transaction_date' => '2026-09-19']))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/transfers', transferPayload($other, $account, ['amount' => '50000', 'transfer_date' => '2026-09-17']))
        ->assertRedirect();

    $this->actingAs($owner)
        ->get($url.'/accounts/'.$account->getKey())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Show')
                ->where('account.id', $account->getKey())
                ->has('history', 3)
                ->where('history.0.kind', 'transaction')
                ->where('history.0.direction', 'in')
                ->where('history.0.amount', '100000.00')
                ->where('history.1.kind', 'transaction')
                ->where('history.1.direction', 'out')
                ->where('history.2.kind', 'transfer')
                ->where('history.2.direction', 'in')
                ->where('history.2.subtitle', 'Dari Bank'),
        );
});

test('pelunasan muncul di riwayat kartu dan sumber', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['card' => $card, 'bank' => $bank] = billFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', billPayload($card, $bank))
        ->assertRedirect();

    $this->actingAs($owner)
        ->get($url.'/accounts/'.$card->getKey())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Show')
                ->has('history', 1)
                ->where('history.0.direction', 'out')
                ->where('history.0.subtitle', 'Bayar tagihan · dari Bank'),
        );

    $this->actingAs($owner)
        ->get($url.'/accounts/'.$bank->getKey())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Show')
                ->has('history', 1)
                ->where('history.0.direction', 'out'),
        );
});

test('member boleh membuka riwayat kantong mana pun', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense))
        ->assertRedirect();

    $this->actingAs($member)
        ->get($url.'/accounts/'.$account->getKey())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Show')
                ->has('history', 1)
                ->where('can.manage', false),
        );
});

test('riwayat milik tenant lain menjawab 404', function () {
    ['user' => $firstOwner, 'tenant' => $firstTenant] = categoryTenant('keluarga-uji');
    ['account' => $account] = transactionFixtures($firstTenant, $firstOwner);

    ['user' => $secondOwner] = categoryTenant('komunitas-uji');

    $this->actingAs($secondOwner)
        ->get(categoryBaseUrl('komunitas-uji').'/accounts/'.$account->getKey())
        ->assertNotFound();
});

test('kantong tanpa mutasi menampilkan riwayat kosong', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account] = transactionFixtures($tenant, $owner);

    $this->actingAs($owner)
        ->get(categoryBaseUrl('keluarga-uji').'/accounts/'.$account->getKey())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Show')
                ->has('history', 0),
        );
});

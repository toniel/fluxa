<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\CreditCardDetail;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(PermissionSeeder::class);
});

function creditCardPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Kartu Kredit',
        'type' => AccountType::CreditCard->value,
        'initial_balance' => '0',
        'credit_limit' => '10000000',
        'billing_cycle_start_day' => 16,
        'billing_cycle_end_day' => 15,
        'payment_due_offset_days' => 5,
        'default_interest_rate_monthly' => '2.00',
        'default_admin_fee_percentage' => '0.00',
    ], $overrides);
}

test('membuat kartu kredit menyimpan baris detail', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', creditCardPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $account = Account::query()->where('name', 'Kartu Kredit')->firstOrFail();
    $detail = $account->creditCardDetail;

    expect($account->type)->toBe(AccountType::CreditCard)
        ->and($account->tenant_id)->toBe($tenant->getKey())
        ->and($detail)->not->toBeNull()
        ->and($detail->billing_cycle_end_day)->toBe(15)
        ->and($detail->payment_due_offset_days)->toBe(5)
        ->and($detail->credit_limit)->toBe('10000000.00');
});

test('kantong biasa tidak punya baris detail', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/accounts', accountPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(CreditCardDetail::query()->count())->toBe(0);
});

test('siklus tagihan wajib untuk kartu kredit dan paylater', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/accounts', creditCardPayload(['billing_cycle_end_day' => null]))
        ->assertSessionHasErrors(['billing_cycle_end_day']);

    $this->actingAs($owner)
        ->post($url.'/accounts', creditCardPayload([
            'type' => AccountType::Paylater->value,
            'billing_cycle_start_day' => null,
            'payment_due_offset_days' => null,
        ]))
        ->assertSessionHasErrors(['billing_cycle_start_day', 'payment_due_offset_days']);

    expect(Account::query()->count())->toBe(0);
});

test('mengubah detail kartu kredit memperbarui baris yang sama', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/accounts', creditCardPayload())
        ->assertRedirect();

    $account = Account::query()->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/accounts/'.$account->getKey(), creditCardPayload([
            'credit_limit' => '20000000',
            'payment_due_offset_days' => 10,
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(CreditCardDetail::query()->count())->toBe(1)
        ->and($account->refresh()->creditCardDetail->credit_limit)->toBe('20000000.00')
        ->and($account->balance)->toBe('0.00');
});

test('ganti jenis ke tunai menghapus baris detail', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/accounts', creditCardPayload())
        ->assertRedirect();

    $account = Account::query()->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/accounts/'.$account->getKey(), accountPayload(['name' => 'Kartu Kredit']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(CreditCardDetail::query()->count())->toBe(0)
        ->and($account->refresh()->type)->toBe(AccountType::Cash);
});

test('daftar dan ubah memuat detail kartu', function () {
    ['user' => $owner] = categoryTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/accounts', creditCardPayload())
        ->assertRedirect();

    $account = Account::query()->firstOrFail();

    $this->actingAs($owner)
        ->get($url.'/accounts')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Index')
                ->where('accounts.0.credit_detail.credit_limit', '10000000.00')
                ->where('accounts.0.credit_detail.billing_cycle_end_day', 15),
        );

    $this->actingAs($owner)
        ->get($url.'/accounts/'.$account->getKey().'/edit')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('accounts/Edit')
                ->where('account.credit_detail.payment_due_offset_days', 5)
                ->has('types', 6),
        );
});

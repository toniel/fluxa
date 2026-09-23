<?php

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CreditCardBillingResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

/**
 * @return array{card: Account, bank: Account, expense: Category}
 */
function billFixtures(Tenant $tenant, User $owner): array
{
    $card = Account::factory()->for($tenant)->ofType(AccountType::CreditCard)->create([
        'name' => 'Kartu Kredit',
        'initial_balance' => '0',
        'balance' => '0.00',
        'created_by' => $owner->getKey(),
    ]);
    $card->creditCardDetail()->create([
        'billing_cycle_start_day' => 16,
        'billing_cycle_end_day' => 15,
        'payment_due_offset_days' => 5,
        'default_interest_rate_monthly' => '2.00',
        'default_admin_fee_percentage' => '0.00',
        'credit_limit' => '10000000',
    ]);

    $bank = Account::factory()->for($tenant)->ofType(AccountType::Bank)->create([
        'name' => 'Bank',
        'initial_balance' => '5000000',
        'balance' => '5000000.00',
        'created_by' => $owner->getKey(),
    ]);

    $expense = Category::factory()->for($tenant)->ofType(CategoryType::Expense)->create(['name' => 'Belanja']);

    return ['card' => $card, 'bank' => $bank, 'expense' => $expense];
}

function billPayload(Account $card, Account $bank, array $overrides = []): array
{
    return array_merge([
        'account_id' => $card->getKey(),
        'category_id' => null,
        'linked_account_id' => $bank->getKey(),
        'type' => TransactionType::BillPayment->value,
        'amount' => '1500000',
        'description' => 'Bayar tagihan September',
        'transaction_date' => '2026-09-19',
    ], $overrides);
}

/**
 * Invariant §3.5 yang diperluas liabilitas: balance = initial + Σ kaki
 * bertanda milik akun itu.
 */
function assertBalanceReconciles(Account $account): void
{
    $expected = $account->initial_balance;

    foreach (Transaction::query()->forAccount($account->getKey())->get() as $transaction) {
        $expected = bcadd($expected, $transaction->signedAmount($account), 2);
    }

    foreach (
        Transaction::query()->where('linked_account_id', $account->getKey())->get() as $payment
    ) {
        $expected = bcadd($expected, bcmul($payment->amount, '-1', 2), 2);
    }

    expect($account->refresh()->balance)->toBe(number_format((float) $expected, 2, '.', ''));
}

test('belanja kartu kredit menaikkan utang, bukan menurunkannya', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['card' => $card, 'expense' => $expense] = billFixtures($tenant, $owner);

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/transactions', transactionPayload($card, $expense, ['amount' => '2000000']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($card->refresh()->balance)->toBe('2000000.00');

    assertBalanceReconciles($card);
});

test('pemasukan kartu kredit menurunkan utang', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['card' => $card, 'income' => $income] = array_merge(
        billFixtures($tenant, $owner),
        ['income' => Category::factory()->for($tenant)->ofType(CategoryType::Income)->create(['name' => 'Refund'])],
    );

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/transactions', transactionPayload($card, $income, ['amount' => '500000']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($card->refresh()->balance)->toBe('-500000.00');

    assertBalanceReconciles($card);
});

test('pelunasan menurunkan utang dan saldo sumber sekaligus', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['card' => $card, 'bank' => $bank, 'expense' => $expense] = billFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($card, $expense, ['amount' => '2000000']))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/transactions', billPayload($card, $bank))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($card->refresh()->balance)->toBe('500000.00')
        ->and($bank->refresh()->balance)->toBe('3500000.00');

    assertBalanceReconciles($card);
    assertBalanceReconciles($bank);
});

test('mengubah dan menghapus pelunasan mengoreksi kedua kaki', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['card' => $card, 'bank' => $bank] = billFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', billPayload($card, $bank, ['amount' => '1500000']))
        ->assertRedirect();

    $payment = Transaction::query()->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/transactions/'.$payment->getKey(), billPayload($card, $bank, ['amount' => '1000000']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($card->refresh()->balance)->toBe('-1000000.00')
        ->and($bank->refresh()->balance)->toBe('4000000.00');

    $this->actingAs($owner)
        ->delete($url.'/transactions/'.$payment->getKey())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($card->refresh()->balance)->toBe('0.00')
        ->and($bank->refresh()->balance)->toBe('5000000.00');
});

test('aturan validasi pelunasan', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['card' => $card, 'bank' => $bank, 'expense' => $expense] = billFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    // Kartu kredit wajib: pelunasan dari kantong aset ditolak.
    $this->actingAs($owner)
        ->post($url.'/transactions', billPayload($bank, $bank))
        ->assertSessionHasErrors(['account_id']);

    // Sumber wajib, tidak boleh sama, tidak boleh utang.
    $this->actingAs($owner)
        ->post($url.'/transactions', billPayload($card, $bank, ['linked_account_id' => null]))
        ->assertSessionHasErrors(['linked_account_id']);

    $this->actingAs($owner)
        ->post($url.'/transactions', billPayload($card, $card))
        ->assertSessionHasErrors(['linked_account_id']);

    $other = Account::factory()->for($tenant)->ofType(AccountType::Paylater)->create(['created_by' => $owner->getKey()]);

    $this->actingAs($owner)
        ->post($url.'/transactions', billPayload($card, $other))
        ->assertSessionHasErrors(['linked_account_id']);

    // Pelunasan tidak memakai kategori; belanja biasa tidak memakai sumber.
    $this->actingAs($owner)
        ->post($url.'/transactions', billPayload($card, $bank, ['category_id' => $expense->getKey()]))
        ->assertSessionHasErrors(['category_id']);

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($card, $expense, ['linked_account_id' => $bank->getKey()]))
        ->assertSessionHasErrors(['linked_account_id']);

    expect(Transaction::query()->count())->toBe(0)
        ->and($card->refresh()->balance)->toBe('0.00')
        ->and($bank->refresh()->balance)->toBe('5000000.00');
});

test('resolver menentukan periode dan jatuh tempo dari tanggal belanja', function () {
    ['tenant' => $tenant, 'user' => $owner] = categoryTenant('keluarga-uji');
    ['card' => $card] = billFixtures($tenant, $owner);

    $resolver = app(CreditCardBillingResolver::class);
    $detail = $card->refresh()->creditCardDetail;

    $late = $resolver->resolvePeriod($detail, Carbon::parse('2026-09-19'));

    expect($late['period_end']->toDateString())->toBe('2026-10-15')
        ->and($late['due_date']->toDateString())->toBe('2026-10-20')
        ->and($late['period_start']->toDateString())->toBe('2026-09-16');

    $early = $resolver->resolvePeriod($detail, Carbon::parse('2026-09-10'));

    expect($early['period_end']->toDateString())->toBe('2026-09-15')
        ->and($early['due_date']->toDateString())->toBe('2026-09-20');

    // end_day 31 dijepit ke Februari, bukan melompat ke Maret.
    $detail->update(['billing_cycle_end_day' => 31]);
    $february = $resolver->resolvePeriod($detail->refresh(), Carbon::parse('2026-02-10'));

    expect($february['period_end']->toDateString())->toBe('2026-02-28');
});

test('halaman detail memuat info tagihan dan sumber pelunasan', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['card' => $card, 'bank' => $bank, 'expense' => $expense] = billFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($card, $expense, ['amount' => '2000000']))
        ->assertRedirect();

    $purchase = Transaction::query()->firstOrFail();

    $this->actingAs($owner)
        ->get($url.'/transactions/'.$purchase->getKey())
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('transactions/Show')
                ->where('transaction.statement_period_end', '2026-10-15')
                ->where('transaction.due_date', '2026-10-20'),
        );

    $this->actingAs($owner)
        ->post($url.'/transactions', billPayload($card, $bank))
        ->assertRedirect();

    $payment = Transaction::query()->latest('id')->firstOrFail();

    $this->actingAs($owner)
        ->get($url.'/transactions/'.$payment->getKey())
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('transactions/Show')
                ->where('transaction.linked_account_name', 'Bank')
                ->where('transaction.statement_period_end', null)
                ->where('transaction.type', 'bill_payment'),
        );
});

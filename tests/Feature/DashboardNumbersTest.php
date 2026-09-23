<?php

use App\Enums\AccountType;
use App\Models\Account;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

function dashboardFixtures(): array
{
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    ['account' => $account, 'income' => $income, 'expense' => $expense] = transactionFixtures($tenant, $owner);

    return ['user' => $owner] + compact('owner', 'tenant', 'account', 'income', 'expense');
}

function thisMonthDay(int $day): string
{
    $month = Carbon::now()->startOfMonth();

    return $month->copy()->day(min($day, $month->daysInMonth))->toDateString();
}

test('ringkasan bulan berjalan benar dan mengabaikan yang lain', function () {
    ['user' => $owner, 'tenant' => $tenant, 'account' => $account, 'income' => $income, 'expense' => $expense] = dashboardFixtures();
    $url = categoryBaseUrl('keluarga-uji');

    $archived = Account::factory()->for($tenant)->ofType(AccountType::Cash)->archived()->create([
        'name' => 'Lama', 'initial_balance' => '999999', 'balance' => '999999.00', 'created_by' => $owner->getKey(),
    ]);

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $income, ['amount' => '8000000', 'transaction_date' => thisMonthDay(5)]))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '500000', 'transaction_date' => thisMonthDay(6)]))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '7000000', 'transaction_date' => Carbon::now()->subMonthNoOverflow()->toDateString()]))
        ->assertRedirect();

    $this->actingAs($owner)
        ->get($url.'/dashboard')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('Dashboard')
                ->where('summary.income_this_month', '8000000.00')
                ->where('summary.expense_this_month', '500000.00')
                ->where('summary.total_balance', '550000.00')
                ->where('summary.greeting_name', strtok($owner->name, ' '))
                ->has('summary.cashflow')
                ->has('accounts', 1)
                ->where('accounts.0.name', $account->name)
                ->has('recent', 3)
                ->where('recent.0.amount', '500000.00'),
        );

    expect($archived->refresh()->balance)->toBe('999999.00');
});

test('breakdown dan ember mingguan sesuai transaksi', function () {
    ['user' => $owner, 'tenant' => $tenant, 'account' => $account, 'expense' => $expense] = dashboardFixtures();
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '100000', 'transaction_date' => thisMonthDay(2)]))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '200000', 'transaction_date' => thisMonthDay(10)]))
        ->assertRedirect();

    $this->actingAs($owner)
        ->get($url.'/dashboard')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('Dashboard')
                ->where('breakdown.0.category', $expense->name)
                ->where('breakdown.0.total', '300000.00')
                ->where('summary.cashflow.0.expense', '100000.00')
                ->where('summary.cashflow.1.expense', '200000.00'),
        );
});

test('dashboard kosong tanpa kantong dan transaksi', function () {
    ['user' => $owner] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->get(categoryBaseUrl('keluarga-uji').'/dashboard')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('Dashboard')
                ->where('summary.total_balance', '0.00')
                ->where('summary.income_this_month', '0.00')
                ->where('summary.expense_this_month', '0.00')
                ->has('accounts', 0)
                ->has('recent', 0)
                ->has('breakdown', 0),
        );
});

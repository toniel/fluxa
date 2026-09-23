<?php

use App\Enums\CategoryType;
use App\Enums\TenantRole;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('tipe transaksi tahu arah uang dan labelnya', function () {
    expect(TransactionType::Income->signum())->toBe(1)
        ->and(TransactionType::Expense->signum())->toBe(-1)
        ->and(TransactionType::Income->label())->toBe('Pemasukan')
        ->and(TransactionType::Expense->label())->toBe('Pengeluaran')
        ->and(TransactionType::values())->toBe(['income', 'expense']);
});

test('builder menyaring jenis, kantong, dan rentang tanggal', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $account = Account::factory()->for($tenant)->create(['created_by' => $owner->getKey()]);
    $other = Account::factory()->for($tenant)->create(['created_by' => $owner->getKey()]);
    $category = Category::factory()->for($tenant)->ofType(CategoryType::Expense)->create();

    Transaction::factory()->for($tenant)->ofType(TransactionType::Expense)->create([
        'account_id' => $account->getKey(),
        'category_id' => $category->getKey(),
        'amount' => '10000',
        'transaction_date' => '2026-09-10',
        'created_by' => $owner->getKey(),
    ]);
    Transaction::factory()->for($tenant)->ofType(TransactionType::Income)->create([
        'account_id' => $other->getKey(),
        'category_id' => null,
        'amount' => '50000',
        'transaction_date' => '2026-08-05',
        'created_by' => $owner->getKey(),
    ]);

    expect(Transaction::query()->ofType(TransactionType::Expense)->count())->toBe(1)
        ->and(Transaction::query()->forAccount($account->getKey())->count())->toBe(1)
        ->and(Transaction::query()->forCategory($category->getKey())->count())->toBe(1)
        ->and(Transaction::query()->forCategory(null)->count())->toBe(1)
        ->and(Transaction::query()->betweenDates(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'))->count())->toBe(1)
        ->and(Transaction::query()->inMonth(Carbon::parse('2026-09-15'))->count())->toBe(1)
        ->and(Transaction::query()->latestFirst()->firstOrFail()->transaction_date->toDateString())->toBe('2026-09-10');
});

test('jumlah per kategori dan nominal bertanda benar', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $account = Account::factory()->for($tenant)->create(['created_by' => $owner->getKey()]);
    $category = Category::factory()->for($tenant)->ofType(CategoryType::Expense)->create();

    $expense = Transaction::factory()->for($tenant)->ofType(TransactionType::Expense)->create([
        'account_id' => $account->getKey(),
        'category_id' => $category->getKey(),
        'amount' => '12000',
        'created_by' => $owner->getKey(),
    ]);

    expect($expense->signedAmount())->toBe('-12000.00')
        ->and((float) Transaction::query()->sumPerCategory()->firstOrFail()->total)->toBe(12000.0)
        ->and($expense->account->is($account))->toBeTrue()
        ->and($expense->category->is($category))->toBeTrue()
        ->and($expense->creator->is($owner))->toBeTrue();
});

test('policy transaksi mengikuti matriks role', function (TenantRole $role, bool $canManageAll) {
    ['tenant' => $tenant, 'user' => $owner] = categoryTenant('keluarga-uji');
    $user = $role === TenantRole::Owner ? $owner : categoryUser($tenant, $role);

    $account = Account::factory()->for($tenant)->create(['created_by' => $owner->getKey()]);
    $own = Transaction::factory()->for($tenant)->create([
        'account_id' => $account->getKey(),
        'created_by' => $user->getKey(),
    ]);
    $other = Transaction::factory()->for($tenant)->create([
        'account_id' => $account->getKey(),
        'created_by' => $owner->getKey(),
    ]);

    // Izin spatie ter-scope per tenant lewat team id stancl, jadi Gate
    // harus dicek di dalam tenancy yang aktif seperti alur HTTP nyata.
    tenancy()->initialize($tenant);

    try {
        expect(Gate::forUser($user)->allows('viewAny', Transaction::class))->toBeTrue()
            ->and(Gate::forUser($user)->allows('view', $own))->toBeTrue()
            ->and(Gate::forUser($user)->allows('create', Transaction::class))->toBeTrue()
            ->and(Gate::forUser($user)->allows('update', $own))->toBeTrue()
            ->and(Gate::forUser($user)->allows('update', $other))->toBe($canManageAll)
            ->and(Gate::forUser($user)->allows('delete', $other))->toBe($canManageAll);
    } finally {
        tenancy()->end();
    }
})->with([
    'owner' => [TenantRole::Owner, true],
    'admin' => [TenantRole::Admin, true],
    'member' => [TenantRole::Member, false],
]);

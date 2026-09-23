<?php

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TenantRole;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

/**
 * Kantong + kategori siap pakai untuk tenant uji.
 *
 * @return array{account: Account, income: Category, expense: Category}
 */
function transactionFixtures(Tenant $tenant, User $owner): array
{
    $account = Account::factory()->for($tenant)->ofType(AccountType::Cash)->create([
        'name' => 'Kas Harian',
        'initial_balance' => '50000',
        'balance' => '50000.00',
        'created_by' => $owner->getKey(),
    ]);

    $income = Category::factory()->for($tenant)->ofType(CategoryType::Income)->create(['name' => 'Gaji']);
    $expense = Category::factory()->for($tenant)->ofType(CategoryType::Expense)->create(['name' => 'Makan']);

    return ['account' => $account, 'income' => $income, 'expense' => $expense];
}

function transactionPayload(Account $account, Category $category, array $overrides = []): array
{
    return array_merge([
        'account_id' => $account->getKey(),
        'category_id' => $category->getKey(),
        'type' => $category->type->value,
        'amount' => '25000',
        'description' => 'Belanja mingguan',
        'transaction_date' => '2026-09-19',
    ], $overrides);
}

test('daftar transaksi hanya menampilkan milik tenant sendiri', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense))
        ->assertRedirect();

    $this->actingAs($owner)
        ->get($url.'/transactions')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.amount', '25000.00')
                ->where('transactions.0.type', 'expense')
                ->where('transactions.0.account_name', 'Kas Harian')
                ->where('transactions.0.category_name', 'Makan')
                ->where('transactions.0.can_edit', true)
                ->where('can.create', true),
        );
});

test('semua role boleh membuat transaksi', function (TenantRole $role) {
    ['tenant' => $tenant, 'user' => $owner] = categoryTenant('keluarga-uji');
    $user = $role === TenantRole::Owner ? $owner : categoryUser($tenant, $role);
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);

    $this->actingAs($user)
        ->post(categoryBaseUrl('keluarga-uji').'/transactions', transactionPayload($account, $expense))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Transaction::query()->count())->toBe(1);
})->with([
    'owner' => [TenantRole::Owner],
    'admin' => [TenantRole::Admin],
    'member' => [TenantRole::Member],
]);

test('pemasukan menambah saldo, pengeluaran mengurangi saldo', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'income' => $income, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $income, ['amount' => '100000']))
        ->assertRedirect();

    expect($account->refresh()->balance)->toBe('150000.00');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '30000']))
        ->assertRedirect();

    expect($account->refresh()->balance)->toBe('120000.00');
});

test('mengubah nominal menyesuaikan saldo tanpa menambah baris', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '25000']))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/transactions/'.$transaction->getKey(), transactionPayload($account, $expense, ['amount' => '10000']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Transaction::query()->count())->toBe(1)
        ->and($account->refresh()->balance)->toBe('40000.00');
});

test('pindah kantong mengoreksi kedua saldo', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $first, 'expense' => $expense] = transactionFixtures($tenant, $owner);

    $second = Account::factory()->for($tenant)->ofType(AccountType::Cash)->create([
        'name' => 'Bank',
        'initial_balance' => '100000',
        'balance' => '100000.00',
        'created_by' => $owner->getKey(),
    ]);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($first, $expense, ['amount' => '25000']))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/transactions/'.$transaction->getKey(), transactionPayload($second, $expense, ['amount' => '25000']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($first->refresh()->balance)->toBe('50000.00')
        ->and($second->refresh()->balance)->toBe('75000.00');
});

test('menghapus transaksi mengembalikan saldo', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '25000']))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    $this->actingAs($owner)
        ->delete($url.'/transactions/'.$transaction->getKey())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($account->refresh()->balance)->toBe('50000.00')
        ->and(Transaction::query()->count())->toBe(0)
        ->and(Transaction::query()->withTrashed()->count())->toBe(1);
});

test('member bisa mengubah miliknya sendiri tapi tidak milik orang lain', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($member)
        ->post($url.'/transactions', transactionPayload($account, $expense))
        ->assertRedirect();

    $own = Transaction::query()->firstOrFail();

    $this->actingAs($member)
        ->put($url.'/transactions/'.$own->getKey(), transactionPayload($account, $expense, ['amount' => '10000']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '5000']))
        ->assertRedirect();

    $other = Transaction::query()->latest('id')->firstOrFail();

    $this->actingAs($member)
        ->put($url.'/transactions/'.$other->getKey(), transactionPayload($account, $expense, ['amount' => '1']))
        ->assertForbidden();

    $this->actingAs($member)
        ->delete($url.'/transactions/'.$other->getKey())
        ->assertForbidden();

    expect($other->refresh()->amount)->toBe('5000.00');
});

test('payload tidak valid ditolak tanpa mengubah saldo', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'income' => $income, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', [])
        ->assertSessionHasErrors(['account_id', 'type', 'amount', 'transaction_date']);

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '-5']))
        ->assertSessionHasErrors(['amount']);

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['transaction_date' => '2999-01-01']))
        ->assertSessionHasErrors(['transaction_date']);

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $income, ['type' => 'expense']))
        ->assertSessionHasErrors(['category_id']);

    expect(Transaction::query()->count())->toBe(0)
        ->and($account->refresh()->balance)->toBe('50000.00');
});

test('kantong arsip atau milik tenant lain ditolak', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $account->update(['is_archived' => true]);

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense))
        ->assertSessionHasErrors(['account_id']);

    ['user' => $otherOwner, 'tenant' => $otherTenant] = categoryTenant('komunitas-uji');
    $foreign = Account::factory()->for($otherTenant)->ofType(AccountType::Cash)->create(['created_by' => $otherOwner->getKey()]);

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($foreign, $expense))
        ->assertSessionHasErrors(['account_id']);

    expect(Transaction::query()->count())->toBe(0);
});

test('mengubah transaksi memakai id di dalam payload tidak menimpa baris lain', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '10000']))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense, ['amount' => '20000']))
        ->assertRedirect();

    $first = Transaction::query()->oldest('id')->firstOrFail();
    $second = Transaction::query()->latest('id')->firstOrFail();

    $this->actingAs($owner)
        ->put($url.'/transactions/'.$first->getKey(), [
            ...transactionPayload($account, $expense, ['amount' => '99999']),
            'id' => $second->getKey(),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($second->refresh()->amount)->toBe('20000.00')
        ->and($account->refresh()->balance)->toBe('-69999.00');
});

test('baris milik tenant lain menjawab 404, bukan 403', function () {
    ['user' => $firstOwner, 'tenant' => $firstTenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($firstTenant, $firstOwner);

    $this->actingAs($firstOwner)
        ->post(categoryBaseUrl('keluarga-uji').'/transactions', transactionPayload($account, $expense))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    ['user' => $secondOwner] = categoryTenant('komunitas-uji');
    $url = categoryBaseUrl('komunitas-uji').'/transactions/'.$transaction->getKey();

    $this->actingAs($secondOwner)->get($url.'/edit')->assertNotFound();
    $this->actingAs($secondOwner)->put($url, transactionPayload($account, $expense))->assertNotFound();
    $this->actingAs($secondOwner)->delete($url)->assertNotFound();
});

test('halaman buat dan ubah memuat pilihan kantong serta kategori', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->get($url.'/transactions/create')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transactions/Create')
                ->has('accounts', 1)
                ->has('categories', 2)
                ->has('types', 3),
        );

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    $this->actingAs($owner)
        ->get($url.'/transactions/'.$transaction->getKey().'/edit')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transactions/Edit')
                ->where('transaction.id', $transaction->getKey())
                ->has('accounts', 1)
                ->has('categories', 2),
        );
});

test('halaman detail menampilkan transaksi milik tenant sendiri', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    $this->actingAs($owner)
        ->get($url.'/transactions/'.$transaction->getKey())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transactions/Show')
                ->where('transaction.id', $transaction->getKey())
                ->where('transaction.amount', '25000.00')
                ->where('transaction.account_name', 'Kas Harian')
                ->where('transaction.category_name', 'Makan')
                ->where('transaction.can_edit', true)
                ->where('transaction.can_delete', true),
        );
});

test('member boleh membuka detail transaksi milik orang lain tanpa tombol aksi', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', transactionPayload($account, $expense))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    $this->actingAs($member)
        ->get($url.'/transactions/'.$transaction->getKey())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('transactions/Show')
                ->where('transaction.can_edit', false)
                ->where('transaction.can_delete', false),
        );
});

test('detail milik tenant lain menjawab 404', function () {
    ['user' => $firstOwner, 'tenant' => $firstTenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($firstTenant, $firstOwner);

    $this->actingAs($firstOwner)
        ->post(categoryBaseUrl('keluarga-uji').'/transactions', transactionPayload($account, $expense))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    ['user' => $secondOwner] = categoryTenant('komunitas-uji');

    $this->actingAs($secondOwner)
        ->get(categoryBaseUrl('komunitas-uji').'/transactions/'.$transaction->getKey())
        ->assertNotFound();
});

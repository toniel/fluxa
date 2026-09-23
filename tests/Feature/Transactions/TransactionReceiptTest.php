<?php

use App\Models\Transaction;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(PermissionSeeder::class);
});

test('mengunggah struk menyimpan media dan url masuk data keluar', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', [
            ...transactionPayload($account, $expense),
            'receipt' => UploadedFile::fake()->image('struk.png'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->getMedia('receipt'))->toHaveCount(1)
        ->and($transaction->receipt_url)->toContain('/storage/');

    $this->actingAs($owner)
        ->get($url.'/transactions')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->where('transactions.0.receipt_url', $transaction->receipt_url),
        );
});

test('transaksi tanpa struk tetap tersimpan dengan url kosong', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/transactions', transactionPayload($account, $expense))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->getMedia('receipt'))->toHaveCount(0)
        ->and($transaction->receipt_url)->toBe('');
});

test('mengganti struk lewat update tetap satu file, remove_receipt menghapusnya', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/transactions', [
            ...transactionPayload($account, $expense),
            'receipt' => UploadedFile::fake()->image('struk.png'),
        ])
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();
    $editUrl = $url.'/transactions/'.$transaction->getKey();

    $this->actingAs($owner)
        ->put($editUrl, [
            ...transactionPayload($account, $expense),
            'receipt' => UploadedFile::fake()->image('baru.png'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($transaction->refresh()->getMedia('receipt'))->toHaveCount(1);

    $this->actingAs($owner)
        ->put($editUrl, [
            ...transactionPayload($account, $expense),
            'remove_receipt' => true,
        ])
        ->assertRedirect();

    expect($transaction->refresh()->getMedia('receipt'))->toHaveCount(0);
});

test('struk yang bukan gambar ditolak', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');
    ['account' => $account, 'expense' => $expense] = transactionFixtures($tenant, $owner);

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/transactions', [
            ...transactionPayload($account, $expense),
            'receipt' => UploadedFile::fake()->create('struk.txt', 1),
        ])
        ->assertSessionHasErrors(['receipt']);

    expect(Transaction::query()->count())->toBe(0);
});

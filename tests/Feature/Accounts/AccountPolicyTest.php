<?php

use App\Enums\TenantRole;
use App\Models\Account;
use App\Models\Tenant;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

/**
 * Jalankan callback dalam tenancy tenant tersebut. Permission spatie
 * ter-scope per team, jadi pengecekan Gate harus melihat team yang sama
 * dengan tempat role-nya di-assign.
 */
function withinTenancy(Tenant $tenant, callable $callback): mixed
{
    $previous = tenant();

    try {
        tenancy()->initialize($tenant);

        return $callback();
    } finally {
        $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
    }
}

test('viewAny dan view terbuka untuk semua role', function (TenantRole $role) {
    ['tenant' => $tenant] = categoryTenant('keluarga-uji');
    $user = categoryUser($tenant, $role);

    $account = Account::factory()
        ->for($tenant)
        ->create(['created_by' => $user->getKey()]);

    withinTenancy($tenant, function () use ($user, $account): void {
        expect(Gate::forUser($user)->allows('viewAny', Account::class))->toBeTrue()
            ->and(Gate::forUser($user)->allows('view', $account))->toBeTrue();
    });
})->with([
    'owner' => [TenantRole::Owner],
    'admin' => [TenantRole::Admin],
    'member' => [TenantRole::Member],
]);

test('semua role boleh membuat kantong', function (TenantRole $role) {
    ['tenant' => $tenant] = categoryTenant('keluarga-uji');
    $user = categoryUser($tenant, $role);

    withinTenancy($tenant, function () use ($user): void {
        expect(Gate::forUser($user)->allows('create', Account::class))->toBeTrue();
    });
})->with([
    'owner' => [TenantRole::Owner],
    'admin' => [TenantRole::Admin],
    'member' => [TenantRole::Member],
]);

test('hanya owner dan admin yang boleh mengubah, mengarsipkan, dan menghapus', function (TenantRole $role) {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('keluarga-uji');

    $account = Account::factory()
        ->for($tenant)
        ->create(['created_by' => $owner->getKey()]);

    $user = categoryUser($tenant, $role, 'Pelaku');

    withinTenancy($tenant, function () use ($user, $account, $role): void {
        $expected = in_array($role, [TenantRole::Owner, TenantRole::Admin], true);

        expect(Gate::forUser($user)->allows('update', $account))->toBe($expected)
            ->and(Gate::forUser($user)->allows('delete', $account))->toBe($expected)
            ->and(Gate::forUser($user)->allows('archive', $account))->toBe($expected);
    });
})->with([
    'owner' => [TenantRole::Owner],
    'admin' => [TenantRole::Admin],
    'member' => [TenantRole::Member],
]);

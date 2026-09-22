<?php

use App\Enums\CategoryType;
use App\Enums\TenantRole;
use App\Models\Category;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('viewAny dan view terbuka untuk semua role', function (TenantRole $role) {
    ['tenant' => $tenant] = categoryTenant('keluarga-uji');
    $user = categoryUser($tenant, $role);

    $category = Category::factory()->for($tenant)->ofType(CategoryType::Expense)->create();

    expect(Gate::forUser($user)->allows('viewAny', Category::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $category))->toBeTrue();
})->with([
    'owner' => [TenantRole::Owner],
    'admin' => [TenantRole::Admin],
    'member' => [TenantRole::Member],
]);

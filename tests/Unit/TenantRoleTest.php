<?php

use App\Enums\PermissionEnum;
use App\Enums\TenantRole;

test('label, nilai, dan pemetaan permission setiap role konsisten', function () {
    expect(TenantRole::Owner->value)->toBe('owner')
        ->and(TenantRole::Admin->value)->toBe('admin')
        ->and(TenantRole::Member->value)->toBe('member')
        ->and(TenantRole::Owner->label())->toBe('Pemilik')
        ->and(TenantRole::Admin->label())->toBe('Admin')
        ->and(TenantRole::Member->label())->toBe('Anggota');

    foreach (TenantRole::cases() as $role) {
        expect($role->permissionValues())->toBe(
            array_map(
                static fn (PermissionEnum $permission): string => $permission->value,
                $role->permissions(),
            ),
        );
    }
});

test('owner dan admin tidak bisa diundang lewat role bawaan undangan', function () {
    expect(TenantRole::invitable())->toBe([TenantRole::Admin, TenantRole::Member]);
});

test('matriks role sesuai PRD', function () {
    expect(TenantRole::Member->permissions())->toContain(PermissionEnum::TenantView)
        ->not->toContain(PermissionEnum::CategoriesManage);

    expect(TenantRole::Admin->permissions())->toContain(PermissionEnum::CategoriesManage)
        ->not->toContain(PermissionEnum::BillingView);

    expect(TenantRole::Owner->permissions())->toContain(
        PermissionEnum::BillingView,
        PermissionEnum::BillingManage,
        PermissionEnum::TenantSettings,
        PermissionEnum::TenantDelete,
    );
});

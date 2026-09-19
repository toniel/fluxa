<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Role per tenant. Nilainya dipakai apa adanya sebagai nama role
 * spatie/laravel-permission, jadi jangan diubah tanpa migrasi data.
 */
#[TypeScript]
enum TenantRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Pemilik',
            self::Admin => 'Admin',
            self::Member => 'Anggota',
        };
    }

    /**
     * Role yang boleh diberikan lewat undangan. Owner tidak termasuk: satu
     * tenant selalu punya tepat satu owner, dan perpindahannya lewat transfer
     * ownership, bukan lewat undangan.
     *
     * @return list<self>
     */
    public static function invitable(): array
    {
        return [self::Admin, self::Member];
    }

    /**
     * Pemetaan role ke permission, diturunkan dari matriks role PRD.
     *
     * Ini satu-satunya sumber kebenaran pemetaan tersebut: PermissionSeeder
     * dan test matriks role sama-sama membacanya dari sini.
     *
     * @return list<PermissionEnum>
     */
    public function permissions(): array
    {
        $member = [
            PermissionEnum::TenantView,
            PermissionEnum::AccountsCreate,
            PermissionEnum::TransactionsCreate,
            PermissionEnum::TransactionsManageOwn,
            PermissionEnum::TransfersCreate,
            PermissionEnum::TransfersManageOwn,
        ];

        $admin = [
            ...$member,
            PermissionEnum::AccountsManage,
            PermissionEnum::CategoriesManage,
            PermissionEnum::TransactionsManage,
            PermissionEnum::TransfersManage,
            PermissionEnum::MembersInvite,
            PermissionEnum::MembersRemove,
        ];

        return match ($this) {
            self::Member => $member,
            self::Admin => $admin,
            self::Owner => [
                ...$admin,
                PermissionEnum::MembersManageRole,
                PermissionEnum::TenantSettings,
                PermissionEnum::TenantDelete,
                PermissionEnum::BillingView,
                PermissionEnum::BillingManage,
            ],
        };
    }

    /**
     * @return list<string>
     */
    public function permissionValues(): array
    {
        return array_map(
            static fn (PermissionEnum $permission): string => $permission->value,
            $this->permissions(),
        );
    }
}

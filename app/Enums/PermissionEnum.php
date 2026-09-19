<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Permission diturunkan langsung dari matriks role di PRD.
 *
 * Permission menjawab "role ini boleh melakukan jenis aksi ini?". Pertanyaan
 * "boleh tidak pada BARIS ini?" dijawab Policy, bukan di sini — karena itulah
 * aturan "member hanya boleh mengubah transaksi miliknya sendiri" muncul
 * sebagai TransactionsManageOwn plus pengecekan created_by di Policy, bukan
 * sebagai permission tersendiri per baris.
 */
#[TypeScript]
enum PermissionEnum: string
{
    case TenantView = 'tenant.view';
    case TenantSettings = 'tenant.settings';
    case TenantDelete = 'tenant.delete';

    case AccountsCreate = 'accounts.create';
    case AccountsManage = 'accounts.manage';

    case CategoriesManage = 'categories.manage';

    case TransactionsCreate = 'transactions.create';
    case TransactionsManage = 'transactions.manage';
    case TransactionsManageOwn = 'transactions.manage-own';

    case TransfersCreate = 'transfers.create';
    case TransfersManage = 'transfers.manage';
    case TransfersManageOwn = 'transfers.manage-own';

    case MembersInvite = 'members.invite';
    case MembersRemove = 'members.remove';
    case MembersManageRole = 'members.manage-role';

    case BillingView = 'billing.view';
    case BillingManage = 'billing.manage';

    public function label(): string
    {
        return match ($this) {
            self::TenantView => 'Lihat data tenant',
            self::TenantSettings => 'Ubah pengaturan tenant',
            self::TenantDelete => 'Hapus tenant',
            self::AccountsCreate => 'Buat kantong',
            self::AccountsManage => 'Edit dan hapus kantong',
            self::CategoriesManage => 'Kelola kategori',
            self::TransactionsCreate => 'Buat transaksi',
            self::TransactionsManage => 'Edit dan hapus semua transaksi',
            self::TransactionsManageOwn => 'Edit dan hapus transaksi sendiri',
            self::TransfersCreate => 'Buat transfer',
            self::TransfersManage => 'Edit dan hapus semua transfer',
            self::TransfersManageOwn => 'Edit dan hapus transfer sendiri',
            self::MembersInvite => 'Undang anggota',
            self::MembersRemove => 'Keluarkan anggota',
            self::MembersManageRole => 'Ubah role anggota',
            self::BillingView => 'Lihat billing',
            self::BillingManage => 'Ubah langganan',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

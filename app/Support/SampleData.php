<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Data contoh untuk pratinjau tampilan.
 *
 * SELURUH isi kelas ini karangan dan tidak mewakili data siapa pun. Ia ada
 * hanya supaya halaman bisa dinilai secara visual sebelum lapisan data asli
 * dibangun, dan setiap halaman yang memakainya menampilkan penanda "data
 * contoh" ke pengguna. Hapus kelas ini begitu controller sungguhan mendarat.
 */
final class SampleData
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function accounts(): array
    {
        return [
            ['id' => 1, 'name' => 'Kas Harian', 'type' => 'cash', 'balance' => '1250000.00', 'icon' => 'wallet', 'is_archived' => false, 'tx_count' => 7],
            ['id' => 2, 'name' => 'BCA Bersama', 'type' => 'bank', 'balance' => '8420000.00', 'icon' => 'landmark', 'is_archived' => false, 'tx_count' => 13],
            ['id' => 3, 'name' => 'GoPay Belanja', 'type' => 'ewallet', 'balance' => '385000.00', 'icon' => 'smartphone', 'is_archived' => false, 'tx_count' => 5],
            ['id' => 4, 'name' => 'Tabungan Kurban', 'type' => 'other', 'balance' => '2400000.00', 'icon' => 'piggy-bank', 'is_archived' => false, 'tx_count' => 1],
            ['id' => 5, 'name' => 'Dompet Lama', 'type' => 'cash', 'balance' => '0.00', 'icon' => 'wallet', 'is_archived' => true, 'tx_count' => 0],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function categories(): array
    {
        return [
            ['id' => 1, 'name' => 'Gaji', 'type' => 'income', 'icon' => 'banknote', 'is_default' => true, 'usage' => 2],
            ['id' => 2, 'name' => 'Iuran Warga', 'type' => 'income', 'icon' => 'hand-coins', 'is_default' => false, 'usage' => 6],
            ['id' => 3, 'name' => 'Makan', 'type' => 'expense', 'icon' => 'utensils', 'is_default' => true, 'usage' => 18],
            ['id' => 4, 'name' => 'Transport', 'type' => 'expense', 'icon' => 'car', 'is_default' => true, 'usage' => 9],
            ['id' => 5, 'name' => 'Belanja', 'type' => 'expense', 'icon' => 'shopping-bag', 'is_default' => true, 'usage' => 7],
            ['id' => 6, 'name' => 'Tagihan', 'type' => 'expense', 'icon' => 'receipt', 'is_default' => true, 'usage' => 4],
            ['id' => 7, 'name' => 'Lainnya', 'type' => 'expense', 'icon' => 'ellipsis', 'is_default' => true, 'usage' => 2],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function transactions(): array
    {
        return [
            ['id' => 1, 'type' => 'expense', 'amount' => '85000.00', 'description' => 'Belanja sayur mingguan', 'date' => '2026-09-19', 'account' => 'Kas Harian', 'category' => 'Belanja', 'icon' => 'shopping-bag', 'creator' => 'Sinta', 'can_edit' => true],
            ['id' => 2, 'type' => 'expense', 'amount' => '32000.00', 'description' => 'Ojek ke pasar', 'date' => '2026-09-19', 'account' => 'GoPay Belanja', 'category' => 'Transport', 'icon' => 'car', 'creator' => 'Rudi', 'can_edit' => false],
            ['id' => 3, 'type' => 'expense', 'amount' => '340500.00', 'description' => 'Listrik September', 'date' => '2026-09-18', 'account' => 'BCA Bersama', 'category' => 'Tagihan', 'icon' => 'receipt', 'creator' => 'Budi', 'can_edit' => true],
            ['id' => 4, 'type' => 'expense', 'amount' => '289000.00', 'description' => 'Internet bulanan', 'date' => '2026-09-18', 'account' => 'BCA Bersama', 'category' => 'Tagihan', 'icon' => 'receipt', 'creator' => 'Budi', 'can_edit' => true],
            ['id' => 5, 'type' => 'income', 'amount' => '8000000.00', 'description' => 'Gaji bulanan', 'date' => '2026-09-17', 'account' => 'BCA Bersama', 'category' => 'Gaji', 'icon' => 'banknote', 'creator' => 'Budi', 'can_edit' => true],
            ['id' => 6, 'type' => 'expense', 'amount' => '125000.00', 'description' => 'Makan bersama', 'date' => '2026-09-16', 'account' => 'Kas Harian', 'category' => 'Makan', 'icon' => 'utensils', 'creator' => 'Sinta', 'can_edit' => true],
            ['id' => 7, 'type' => 'expense', 'amount' => '47500.00', 'description' => 'Kopi dan roti', 'date' => '2026-09-16', 'account' => 'GoPay Belanja', 'category' => 'Makan', 'icon' => 'utensils', 'creator' => 'Rudi', 'can_edit' => false],
            ['id' => 8, 'type' => 'income', 'amount' => '450000.00', 'description' => 'Iuran warga blok C', 'date' => '2026-09-15', 'account' => 'BCA Bersama', 'category' => 'Iuran Warga', 'icon' => 'hand-coins', 'creator' => 'Budi', 'can_edit' => true],
            ['id' => 9, 'type' => 'expense', 'amount' => '67500.00', 'description' => 'Galon dan gas', 'date' => '2026-09-14', 'account' => 'Kas Harian', 'category' => 'Belanja', 'icon' => 'shopping-bag', 'creator' => 'Rudi', 'can_edit' => false],
            ['id' => 10, 'type' => 'expense', 'amount' => '150000.00', 'description' => 'Servis motor', 'date' => '2026-09-12', 'account' => 'Kas Harian', 'category' => 'Transport', 'icon' => 'car', 'creator' => 'Sinta', 'can_edit' => true],
            ['id' => 11, 'type' => 'expense', 'amount' => '95000.00', 'description' => 'Belanja bulanan tambahan', 'date' => '2026-09-10', 'account' => 'GoPay Belanja', 'category' => 'Belanja', 'icon' => 'shopping-bag', 'creator' => 'Sinta', 'can_edit' => true],
            ['id' => 12, 'type' => 'income', 'amount' => '250000.00', 'description' => 'Iuran warga blok A', 'date' => '2026-09-08', 'account' => 'BCA Bersama', 'category' => 'Iuran Warga', 'icon' => 'hand-coins', 'creator' => 'Budi', 'can_edit' => true],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function transfers(): array
    {
        return [
            ['id' => 1, 'amount' => '500000.00', 'description' => 'Isi ulang kas harian', 'date' => '2026-09-18', 'from' => 'BCA Bersama', 'to' => 'Kas Harian', 'creator' => 'Budi'],
            ['id' => 2, 'amount' => '200000.00', 'description' => 'Top up e-wallet belanja', 'date' => '2026-09-15', 'from' => 'BCA Bersama', 'to' => 'GoPay Belanja', 'creator' => 'Sinta'],
            ['id' => 3, 'amount' => '300000.00', 'description' => 'Setoran tabungan kurban', 'date' => '2026-09-10', 'from' => 'BCA Bersama', 'to' => 'Tabungan Kurban', 'creator' => 'Budi'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function members(): array
    {
        // Sudut pandang di sini adalah Budi, sang owner. Matriks role di
        // TenantRole::permissions() menentukan can_remove/can_change_role:
        // owner boleh keduanya untuk admin/member, tidak pernah untuk dirinya
        // sendiri maupun sesama owner.
        return [
            ['id' => 1, 'name' => 'Budi Pemilik', 'email' => 'owner@fluxa.test', 'role' => 'owner', 'joined_at' => '2026-01-12', 'tx_count' => 18, 'is_current_user' => true, 'can_change_role' => false, 'can_remove' => false],
            ['id' => 2, 'name' => 'Sinta Admin', 'email' => 'admin@fluxa.test', 'role' => 'admin', 'joined_at' => '2026-02-03', 'tx_count' => 6, 'is_current_user' => false, 'can_change_role' => true, 'can_remove' => true],
            ['id' => 3, 'name' => 'Rudi Anggota', 'email' => 'member@fluxa.test', 'role' => 'member', 'joined_at' => '2026-05-21', 'tx_count' => 4, 'is_current_user' => false, 'can_change_role' => true, 'can_remove' => true],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function invitations(): array
    {
        return [
            ['id' => 1, 'email' => 'calon@fluxa.test', 'role' => 'member', 'status' => 'pending', 'expires_at' => '2026-09-26'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function dashboard(): array
    {
        return [
            'total_balance' => '12455000.00',
            'income_this_month' => '8450000.00',
            'expense_this_month' => '650000.00',
            'period_label' => 'September 2026',
            'greeting_name' => 'Budi',
            'cashflow' => [
                ['label' => 'Mgg 1', 'income' => '0.00', 'expense' => '820000.00'],
                ['label' => 'Mgg 2', 'income' => '450000.00', 'expense' => '640000.00'],
                ['label' => 'Mgg 3', 'income' => '8000000.00', 'expense' => '1250000.00'],
                ['label' => 'Mgg 4', 'income' => '0.00', 'expense' => '390000.00'],
                ['label' => 'Mgg 5', 'income' => '0.00', 'expense' => '0.00'],
            ],
            'breakdown' => [
                ['category' => 'Tagihan', 'icon' => 'receipt', 'total' => '340500.00'],
                ['category' => 'Belanja', 'icon' => 'shopping-bag', 'total' => '152500.00'],
                ['category' => 'Makan', 'icon' => 'utensils', 'total' => '125000.00'],
                ['category' => 'Transport', 'icon' => 'car', 'total' => '32000.00'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function billing(): array
    {
        return [
            'plan' => ['name' => 'Free', 'slug' => 'free', 'price' => '0.00', 'billing_period' => 'monthly'],
            'status' => 'active',
            'period_end' => null,
            'usage' => ['members' => 3, 'max_members' => 3, 'accounts' => 4, 'max_accounts' => 3],
            'upgrade' => ['name' => 'Pro', 'price' => '35000.00', 'billing_period' => 'monthly'],
            // Baris diturunkan dari skema plans.features di PRD (max_members,
            // max_accounts, custom_subdomain, export) - bukan daftar fitur
            // bebas, supaya perbandingan ini konsisten dengan apa yang memang
            // dirancang untuk paket berbayar.
            'comparison' => [
                ['feature' => 'Kantong', 'free' => '3', 'pro' => 'Tanpa batas'],
                ['feature' => 'Anggota tenant', 'free' => '3', 'pro' => 'Tanpa batas'],
                ['feature' => 'Subdomain', 'free' => 'Acak', 'pro' => 'Pilihan sendiri'],
                ['feature' => 'Export laporan (PDF/Excel)', 'free' => false, 'pro' => true],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function tenant(): array
    {
        return [
            'name' => 'Keluarga Demo',
            'subdomain' => 'keluarga-demo',
            'member_count' => 3,
            'memberships' => [
                ['id' => 1, 'name' => 'Keluarga Demo', 'role' => 'owner', 'subdomain' => 'keluarga-demo', 'is_current' => true],
                ['id' => 2, 'name' => 'Komunitas RT 05', 'role' => 'owner', 'subdomain' => 'rt-05', 'is_current' => false],
            ],
        ];
    }
}

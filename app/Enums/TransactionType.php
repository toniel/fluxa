<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case BillPayment = 'bill_payment';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
            self::BillPayment => 'Bayar tagihan',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public function signum(): int
    {
        return match ($this) {
            self::Income => 1,
            self::Expense, self::BillPayment => -1,
        };
    }
}

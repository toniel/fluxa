<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Jenis kantong. CreditCard dan Paylater menyusul di phase 2; menambah case
 * di sini tidak butuh migrasi karena kolom `type` bertipe string.
 */
#[TypeScript]
enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Ewallet = 'ewallet';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Bank => 'Rekening bank',
            self::Ewallet => 'E-wallet',
            self::Other => 'Lainnya',
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

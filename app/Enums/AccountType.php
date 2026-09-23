<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Jenis kantong. Kartu kredit dan paylater adalah liabilitas: arah mutasi
 * saldonya terbalik (lihat balanceDirection), utang naik saat belanja.
 */
#[TypeScript]
enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Ewallet = 'ewallet';
    case CreditCard = 'credit_card';
    case Paylater = 'paylater';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Bank => 'Rekening bank',
            self::Ewallet => 'E-wallet',
            self::CreditCard => 'Kartu kredit',
            self::Paylater => 'Paylater',
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

    public function isLiability(): bool
    {
        return match ($this) {
            self::CreditCard, self::Paylater => true,
            default => false,
        };
    }

    /**
     * Pengali arah mutasi saldo: aset +1, liabilitas -1. Belanja di kartu
     * kredit menaikkan balance (utang), bukan menurunkannya.
     */
    public function balanceDirection(): int
    {
        return $this->isLiability() ? -1 : 1;
    }
}

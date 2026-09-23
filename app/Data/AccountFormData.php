<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AccountType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Bentuk kantong masuk, apa yang dikirim form create/update.
 *
 * `initial_balance` ikut dikirim saat update (form menguncinya read-only),
 * tapi action update mengabaikannya: saldo awal tidak bisa diubah sesudah
 * kantong dibuat. Tanpa id: target update datang dari route binding, bukan
 * dari payload.
 *
 * Field `credit_*` hanya bermakna untuk kartu kredit/paylater dan wajib diisi
 * untuk kedua jenis itu (lihat rules()).
 */
#[TypeScript]
class AccountFormData extends Data
{
    public function __construct(
        #[Required, Max(50)]
        public string $name,
        #[Enum(AccountType::class)]
        public AccountType $type,
        #[Required, Numeric, Min(0), Max(999999999999.99)]
        public string $initial_balance,
        #[Nullable, Numeric, Min(0), Max(999999999999.99)]
        public ?string $credit_limit,
        #[Nullable, IntegerType, Min(1), Max(31)]
        public ?int $billing_cycle_start_day,
        #[Nullable, IntegerType, Min(1), Max(31)]
        public ?int $billing_cycle_end_day,
        #[Nullable, IntegerType, Min(0), Max(93)]
        public ?int $payment_due_offset_days,
        #[Nullable, Numeric, Min(0), Max(100)]
        public ?string $default_interest_rate_monthly,
        #[Nullable, Numeric, Min(0), Max(100)]
        public ?string $default_admin_fee_percentage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $requiredIfLiability = 'required_if:type,credit_card,paylater';

        return [
            'billing_cycle_start_day' => $requiredIfLiability,
            'billing_cycle_end_day' => $requiredIfLiability,
            'payment_due_offset_days' => $requiredIfLiability,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CreditCardDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pengaturan tagihan untuk kantong kartu kredit/paylater, one-to-one dengan
 * Account. Tanpa BelongsToTenant: tidak ada route yang menyentuhnya langsung,
 * ia selalu dibaca dan ditulis lewat akun induknya yang ter-scope tenant.
 *
 * @property int $id
 * @property int $account_id
 * @property int $billing_cycle_start_day
 * @property int $billing_cycle_end_day
 * @property int $payment_due_offset_days
 * @property numeric-string $default_interest_rate_monthly
 * @property numeric-string $default_admin_fee_percentage
 * @property numeric-string|null $credit_limit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'account_id',
    'billing_cycle_start_day',
    'billing_cycle_end_day',
    'payment_due_offset_days',
    'default_interest_rate_monthly',
    'default_admin_fee_percentage',
    'credit_limit',
])]
class CreditCardDetail extends Model
{
    /** @use HasFactory<CreditCardDetailFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_interest_rate_monthly' => 'decimal:2',
            'default_admin_fee_percentage' => 'decimal:2',
            'credit_limit' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

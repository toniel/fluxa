<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CreditCardDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCardDetail>
 */
class CreditCardDetailFactory extends Factory
{
    protected $model = CreditCardDetail::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'billing_cycle_start_day' => 16,
            'billing_cycle_end_day' => 15,
            'payment_due_offset_days' => 5,
            'default_interest_rate_monthly' => '2.00',
            'default_admin_fee_percentage' => '0.00',
            'credit_limit' => '10000000',
        ];
    }
}

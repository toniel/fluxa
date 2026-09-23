<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_account_id' => Account::factory(),
            'to_account_id' => Account::factory(),
            'amount' => fake()->randomFloat(2, 1, 500000),
            'description' => fake()->sentence(3),
            'transfer_date' => fake()->date(),
            'created_by' => User::factory(),
        ];
    }
}

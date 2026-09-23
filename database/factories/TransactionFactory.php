<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'category_id' => null,
            'type' => fake()->randomElement(TransactionType::cases())->value,
            'amount' => fake()->randomFloat(2, 1, 500000),
            'description' => fake()->sentence(3),
            'transaction_date' => fake()->date(),
            'created_by' => User::factory(),
        ];
    }

    public function ofType(TransactionType $type): static
    {
        return $this->state(fn (array $attributes): array => ['type' => $type->value]);
    }
}

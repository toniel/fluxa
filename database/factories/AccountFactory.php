<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement(AccountType::cases())->value,
            // Test yang peduli siapa penciptanya memakai
            // ->create(['created_by' => $user->getKey()]).
            'created_by' => User::factory(),
            'balance' => '0.00',
            'initial_balance' => '0.00',
            'icon' => null,
            'color' => null,
            'is_archived' => false,
        ];
    }

    public function ofType(AccountType $type): static
    {
        return $this->state(fn (array $attributes): array => ['type' => $type->value]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['is_archived' => true]);
    }
}

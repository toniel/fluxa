<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement([CategoryType::Income, CategoryType::Expense])->value,
            'icon' => null,
            'is_default' => false,
        ];
    }

    public function ofType(CategoryType $type): static
    {
        return $this->state(fn (array $attributes): array => ['type' => $type->value]);
    }

    /**
     * Kategori bawaan yang di-seed, ditandai is_default.
     */
    public function defaults(): static
    {
        return $this->state(fn (array $attributes): array => ['is_default' => true]);
    }
}

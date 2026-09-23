<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'price' => '0',
            'billing_period' => BillingPeriod::Monthly->value,
            'features' => ['max_members' => 3, 'max_accounts' => 3, 'custom_subdomain' => false, 'export' => false],
            'is_active' => true,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Enums\TenantRole;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantInvitation>
 */
class TenantInvitationFactory extends Factory
{
    protected $model = TenantInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'role' => TenantRole::Member->value,
            'token' => fake()->unique()->sha256(),
            'invited_by' => User::factory(),
            'status' => InvitationStatus::Pending->value,
            'expires_at' => fake()->dateTimeBetween('+1 day', '+7 days'),
        ];
    }
}

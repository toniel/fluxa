<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BillingPeriod;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Idempotent dan aman di produksi: paket dikenali dari slug, harga dan
     * fitur boleh berubah tanpa deploy.
     */
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'price' => '0',
                'billing_period' => BillingPeriod::Monthly->value,
                'features' => [
                    'max_members' => 3,
                    'max_accounts' => 3,
                    'custom_subdomain' => false,
                    'export' => false,
                ],
                'is_active' => true,
            ],
        );

        Plan::updateOrCreate(
            ['slug' => 'pro-monthly'],
            [
                'name' => 'Pro',
                'price' => '35000',
                'billing_period' => BillingPeriod::Monthly->value,
                'features' => [
                    'max_members' => null,
                    'max_accounts' => null,
                    'custom_subdomain' => true,
                    'export' => true,
                ],
                'is_active' => true,
            ],
        );
    }
}

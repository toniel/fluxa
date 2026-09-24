<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;

/**
 * Penegak batas paket (free vs pro). Satu-satunya tempat yang membaca
 * angka limit dari plans.features; action bertanya ke sini sebelum menulis.
 *
 * null pada limit angka berarti tanpa batas. Tanpa paket yang bisa dibaca
 * (belum seed), pemeriksaan dibuka (fail open) supaya pekerjaan operasional
 * seperti seeder tidak terkunci oleh data yang belum ada.
 */
final class PlanFeatureChecker
{
    public function atMemberLimit(Tenant $tenant): bool
    {
        return $this->atLimit($tenant, 'max_members', $tenant->members()->count());
    }

    public function atAccountLimit(Tenant $tenant): bool
    {
        return $this->atLimit($tenant, 'max_accounts', $tenant->accounts()->count());
    }

    private function atLimit(Tenant $tenant, string $key, int $used): bool
    {
        $max = $this->planFor($tenant)?->feature($key);

        if ($max === null) {
            return false;
        }

        return $used >= (int) $max;
    }

    private function planFor(Tenant $tenant): ?Plan
    {
        $subscription = Subscription::query()
            ->with('plan')
            ->where('tenant_id', $tenant->getKey())
            ->latest('id')
            ->first();

        if ($subscription instanceof Subscription) {
            return $subscription->plan;
        }

        return Plan::query()->bySlug('free');
    }
}

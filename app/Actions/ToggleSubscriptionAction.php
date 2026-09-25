<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleSubscriptionAction
{
    use AsAction;

    /**
     * Mock upgrade/downgrade: bolak-balik antara free dan pro-monthly.
     * Berdiri di model nyata supaya halaman, validasi izin, dan pemakaian
     * limit teruji hari ini; webhook Xendit menggantikan isi method ini
     * nanti tanpa mengubah pemanggilnya.
     *
     * Menentukan target live di sini; logika menetapkan paket dan penurunan
     * ke free (subdomain kembali acak) dipinjam ke SetTenantPlanAction agar
     * satu sumber kebenaran.
     */
    public function handle(Tenant $tenant): Subscription
    {
        return app(TenantContext::class)->runFor($tenant, function () use ($tenant): Subscription {
            $pro = Plan::query()->bySlug('pro-monthly') ?? throw new \RuntimeException('Paket pro-monthly belum di-seed.');
            $free = Plan::query()->bySlug('free') ?? throw new \RuntimeException('Paket free belum di-seed.');

            $subscription = Subscription::query()->latest('id')->first();
            $target = $subscription?->plan_id === $pro->getKey() ? $free : $pro;

            return SetTenantPlanAction::run($tenant, $target);
        });
    }
}

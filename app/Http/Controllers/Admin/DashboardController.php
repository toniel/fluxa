<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Statistik lintas tenant untuk super-admin. Semua query dibungkus
     * withoutScope: di central domain tidak ada tenant aktif dan TenantScope
     * akan melempar bila dipanggil tanpa itu.
     */
    public function __invoke(): Response
    {
        $stats = app(TenantContext::class)->withoutScope(fn (): array => $this->statistics());

        return Inertia::render('admin/Dashboard', $stats);
    }

    /**
     * @return array<string, mixed>
     */
    private function statistics(): array
    {
        $activeSubs = Subscription::query()
            ->with('plan')
            ->active()
            ->get();

        $mrr = '0.00';

        foreach ($activeSubs as $subscription) {
            $mrr = bcadd($mrr, $subscription->plan->price, 2);
        }

        $activeByPlan = $activeSubs->countBy('plan_id');

        return [
            'totals' => [
                'users' => User::query()->count(),
                'users_new_7d' => User::query()->createdSince(now()->subDays(7))->count(),
                'tenants' => Tenant::query()->count(),
                'subscriptions_active' => $activeSubs->count(),
                'mrr' => $mrr,
            ],
            'signups' => $this->weeklySignups(),
            'plans' => Plan::query()->orderBy('price')->get()->map(
                fn (Plan $plan): array => [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => (string) $plan->price,
                    'active_count' => $activeByPlan->get($plan->getKey(), 0),
                ],
            )->all(),
            'recent_tenants' => $this->recentTenants(),
        ];
    }

    /**
     * instanceof di bawah bukan formalitas: koleksi stancl tidak membawa
     * template generik, jadi analisis statis melihat Model, bukan Tenant.
     *
     * @return list<array{id: int, name: string, subdomain: string|null, members_count: int, created_at: string}>
     */
    private function recentTenants(): array
    {
        $recent = [];

        foreach (Tenant::query()->latest('id')->limit(5)->get() as $tenant) {
            if (! $tenant instanceof Tenant) {
                continue;
            }

            $recent[] = [
                'id' => $tenant->getKey(),
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain(),
                'members_count' => $tenant->members()->count(),
                'created_at' => $tenant->created_at->toDateString(),
            ];
        }

        return $recent;
    }

    /**
     * Pendaftar per pekan, 8 pekan terakhir, dihitung dari created_at.
     *
     * @return list<array{label: string, count: int}>
     */
    private function weeklySignups(): array
    {
        $buckets = [];
        $start = now()->subWeeks(7)->startOfWeek();

        for ($week = 0; $week < 8; $week++) {
            $from = $start->copy()->addWeeks($week);
            $to = $from->copy()->endOfWeek();

            $buckets[] = [
                'label' => $from->translatedFormat('d M'),
                'count' => User::query()
                    ->createdBetween($from, $to)
                    ->count(),
            ];
        }

        return $buckets;
    }
}

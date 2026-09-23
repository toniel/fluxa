<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\StartFreeSubscriptionAction;
use App\Actions\ToggleSubscriptionAction;
use App\Data\PlanData;
use App\Data\SubscriptionData;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request, StartFreeSubscriptionAction $ensure): Response
    {
        Gate::authorize('viewAny', Subscription::class);

        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant !== null, 404);

        $subscription = $ensure->handle($tenant);
        $subscription->load('plan');

        $plan = $subscription->plan;
        $free = Plan::query()->bySlug('free');
        $pro = Plan::query()->bySlug('pro-monthly');

        return Inertia::render('billing/Index', [
            'subscription' => new SubscriptionData(
                id: $subscription->getKey(),
                plan: $this->planData($plan),
                status: $subscription->status,
                current_period_end: $subscription->current_period_end?->toDateString(),
            ),
            'upgrade' => $pro === null || $pro->getKey() === $plan->getKey() ? null : [
                'name' => $pro->name,
                'price' => (string) $pro->price,
                'billing_period' => $pro->billing_period->value,
            ],
            'usage' => [
                'members' => $tenant->members()->count(),
                'max_members' => $plan->feature('max_members'),
                'accounts' => $tenant->accounts()->count(),
                'max_accounts' => $plan->feature('max_accounts'),
            ],
            'comparison' => $this->comparison($free, $pro),
            'can' => [
                'manage' => $request->user()->can('update', $subscription),
            ],
        ]);
    }

    public function store(ToggleSubscriptionAction $action): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant !== null, 404);

        $subscription = Subscription::query()->latest('id')->first();

        if ($subscription === null) {
            Gate::authorize('create', Subscription::class);
        } else {
            Gate::authorize('update', $subscription);
        }

        $action->handle($tenant);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paket langganan diperbarui.']);

        return to_route('billing.index');
    }

    private function planData(Plan $plan): PlanData
    {
        return new PlanData(
            id: $plan->getKey(),
            name: $plan->name,
            slug: $plan->slug,
            price: (string) $plan->price,
            billing_period: $plan->billing_period->value,
            is_active: $plan->is_active,
        );
    }

    /**
     * Baris perbandingan Free vs Pro dari fitur kedua paket. null pada
     * batas angka berarti tanpa batas.
     *
     * @return list<array{feature: string, free: string|bool, pro: string|bool}>
     */
    private function comparison(?Plan $free, ?Plan $pro): array
    {
        $limit = static fn (?Plan $plan, string $key): string => $plan === null
            ? '—'
            : (string) ($plan->feature($key) ?? 'Tanpa batas');

        $flag = static fn (?Plan $plan, string $key): bool => (bool) ($plan?->feature($key, false));

        return [
            ['feature' => 'Kantong', 'free' => $limit($free, 'max_accounts'), 'pro' => $limit($pro, 'max_accounts')],
            ['feature' => 'Anggota tenant', 'free' => $limit($free, 'max_members'), 'pro' => $limit($pro, 'max_members')],
            ['feature' => 'Subdomain', 'free' => 'Acak', 'pro' => $flag($pro, 'custom_subdomain') ? 'Pilihan sendiri' : false],
            ['feature' => 'Export laporan (PDF/Excel)', 'free' => $flag($free, 'export'), 'pro' => $flag($pro, 'export')],
        ];
    }
}

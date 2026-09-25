<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\SetTenantPlanAction;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(): Response
    {
        $tenants = app(TenantContext::class)->withoutScope(
            fn () => Tenant::query()
                ->withCount('members')
                ->latest('id')
                ->paginate(12)
                ->withQueryString(),
        );

        $rows = app(TenantContext::class)->withoutScope(function () use ($tenants) {
            $items = $tenants->getCollection();

            $ids = [];

            foreach ($items as $tenant) {
                if ($tenant instanceof Tenant) {
                    $ids[] = $tenant->getKey();
                }
            }

            $subscriptions = $items->isEmpty()
                ? collect()
                : Subscription::query()
                    ->with('plan')
                    ->forTenants($ids)
                    ->latest('id')
                    ->get()
                    ->keyBy('tenant_id');

            return $items->map(
                fn (Tenant $tenant): array => [
                    'id' => $tenant->getKey(),
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain(),
                    'member_count' => $tenant->members_count,
                    'plan_slug' => $subscriptions->get($tenant->getKey())?->plan?->slug,
                    'created_at' => $tenant->created_at->toDateString(),
                ],
            )->values()->all();
        });

        return Inertia::render('admin/Tenants', [
            'tenants' => [
                'data' => $rows,
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    public function update(Request $request, Tenant $tenant, SetTenantPlanAction $action): RedirectResponse
    {
        $data = $request->validate([
            'plan_slug' => ['required', Rule::in(Plan::query()->activeSlugs())],
        ]);

        // Sudah terfilter activeSlugs, tapi dipakai bySlug supaya objek Plan
        // tidak dijamin lewat string di luar daftar tadi.
        $plan = Plan::query()->bySlug($data['plan_slug']);

        abort_unless($plan instanceof Plan, 422);

        $action->handle($tenant, $plan);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Paket {$tenant->name} diperbarui."]);

        return to_route('admin.tenants.index');
    }
}

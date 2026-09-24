<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\StartFreeSubscriptionAction;
use App\Actions\UpdateTenantSubdomainAction;
use App\Data\TenantData;
use App\Data\TenantSettingsFormData;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\TenantDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TenantSettingController extends Controller
{
    public function edit(Request $request, StartFreeSubscriptionAction $ensure): Response
    {
        $tenant = $this->tenant();
        $subscription = $ensure->handle($tenant);
        $subscription->load('plan');

        return Inertia::render('tenant/Settings', [
            'tenant' => new TenantData(
                id: $tenant->getKey(),
                name: $tenant->name,
                subdomain: $tenant->subdomain(),
                created_at: $tenant->created_at->toDateString(),
                member_count: $tenant->members()->count(),
                memberships: TenantDestination::membershipsOf($request->user(), $tenant->getKey()),
            ),
            'plan' => [
                'name' => $subscription->plan->name,
                'slug' => $subscription->plan->slug,
            ],
            'can' => [
                'update' => Gate::allows('update', $tenant),
            ],
        ]);
    }

    public function update(
        TenantSettingsFormData $data,
        StartFreeSubscriptionAction $ensure,
        UpdateTenantSubdomainAction $rename,
    ): RedirectResponse {
        $tenant = $this->tenant();

        Gate::authorize('update', $tenant);

        $tenant->update(['name' => $data->name]);

        // Subdomain hanya diproses bila diisi dan berubah. Batas paket
        // adalah status baris, bukan izin: jawabannya 422, bukan 403.
        if ($data->subdomain !== null && $data->subdomain !== $tenant->subdomain()) {
            $subscription = $ensure->handle($tenant);
            $subscription->load('plan');

            abort_unless((bool) $subscription->plan->feature('custom_subdomain', false), 422, 'Subdomain pilihan sendiri tersedia di paket Pro.');

            $rename->handle($tenant->refresh(), $data->subdomain);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengaturan disimpan.']);

        return to_route('tenant.settings');
    }

    public function destroy(): RedirectResponse|SymfonyResponse
    {
        $tenant = $this->tenant();

        Gate::authorize('delete', $tenant);

        $tenant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant dihapus.']);

        // Keluar dari subdomain yang baru saja dihapus menuju central.
        return Inertia::location(route('tenants.index'));
    }

    private function tenant(): Tenant
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }
}

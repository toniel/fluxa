<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\TenantDestination;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Konteks tenant untuk layout (switcher + topbar): bentuknya
            // sama dengan yang halaman pratinjau kirim per-halaman, supaya
            // komponennya tidak perlu tahu sumbernya.
            'tenant' => fn (): ?array => $this->tenantProps($request),
        ];
    }

    /**
     * @return array{name: string, memberships: list<array{id: int, name: string, subdomain: string|null, url: string, role: string|null, is_current: bool}>}|null
     */
    private function tenantProps(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $tenant = app(TenantContext::class)->tenant();

        if (! $tenant instanceof Tenant) {
            return null;
        }

        return [
            'name' => $tenant->name,
            'memberships' => TenantDestination::membershipsOf($user, $tenant->getKey()),
        ];
    }
}

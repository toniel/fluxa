<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateTenantAction;
use App\Data\TenantCreateFormData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Buat tenant baru dari central domain (di luar tenant context).
 */
class TenantController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('tenants/Create');
    }

    public function store(
        TenantCreateFormData $data,
        Request $request,
        CreateTenantAction $action,
    ): RedirectResponse|SymfonyResponse {
        $tenant = $action->handle($request->user(), $data->name);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tenant {$tenant->name} dibuat."]);

        // Masuk ke subdomain baru: lintas origin, jadi kunjungan penuh.
        return Inertia::location($tenant->url('/dashboard'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\InviteMemberAction;
use App\Actions\RevokeInvitationAction;
use App\Data\InvitationFormData;
use App\Http\Controllers\Controller;
use App\Models\TenantInvitation;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InvitationController extends Controller
{
    /**
     * Undang sekaligus kirim ulang: email yang sudah pernah diundang
     * mendapat token baru (link lama mati) dan notifikasi lagi.
     */
    public function store(InvitationFormData $data, Request $request, InviteMemberAction $action): RedirectResponse
    {
        Gate::authorize('create', TenantInvitation::class);

        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant !== null, 404);

        $action->handle($tenant, $request->user(), $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Undangan dikirim.']);

        return to_route('members.index');
    }

    public function destroy(TenantInvitation $invitation, RevokeInvitationAction $action): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();

        // Model ini tanpa global scope (halaman terima hidup di luar tenant
        // context), jadi kepemilikan tenant dicek eksplisit: 404, bukan 403,
        // supaya id asing tidak terkonfirmasi.
        abort_unless($tenant !== null && $invitation->tenant_id === $tenant->getKey(), 404);

        Gate::authorize('delete', $invitation);

        $action->handle($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Undangan dibatalkan.']);

        return to_route('members.index');
    }
}

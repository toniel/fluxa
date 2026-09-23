<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AcceptInvitationAction;
use App\Enums\InvitationStatus;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvitationAcceptanceController extends Controller
{
    /**
     * Halaman terima undangan. Tanpa auth dan tanpa tenant context: pengklik
     * belum tentu login atau punya tenant, token-lah otoritasnya.
     */
    public function show(Request $request, string $token): Response
    {
        $invitation = TenantInvitation::query()->byToken($token);

        abort_if($invitation === null, 404);

        if ($invitation->status === InvitationStatus::Pending && $invitation->isExpired()) {
            $invitation->update(['status' => InvitationStatus::Expired->value]);
        }

        $invitation->load(['tenant', 'invitedBy']);

        return Inertia::render('invitations/Show', [
            'invitation' => [
                'tenant_name' => $invitation->tenant->name,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'status' => $invitation->status->value,
                'inviter_name' => $invitation->invitedBy->name,
            ],
            'token' => $token,
            'state' => $this->resolveState($invitation, $request->user()),
        ]);
    }

    public function store(Request $request, string $token, AcceptInvitationAction $action): RedirectResponse|SymfonyResponse
    {
        $invitation = TenantInvitation::query()->byToken($token);

        abort_if($invitation === null, 404);
        abort_unless($invitation->isPending(), 410);
        abort_unless($invitation->matches($request->user()), 403);

        $tenant = $action->handle($invitation, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Bergabung ke {$tenant->name}."]);

        return Inertia::location($tenant->url('/dashboard'));
    }

    /**
     * Seluruh percabangan PRD dalam satu tempat yang bisa dites.
     */
    private function resolveState(TenantInvitation $invitation, ?User $user): string
    {
        if ($user === null) {
            return 'guest';
        }

        if ($invitation->tenant->members()->whereKey($user->getKey())->exists()) {
            return 'already_member';
        }

        if ($invitation->status !== InvitationStatus::Pending || $invitation->isExpired()) {
            return $invitation->status->value;
        }

        if (! $invitation->matches($user)) {
            return 'email_mismatch';
        }

        return 'ready';
    }
}

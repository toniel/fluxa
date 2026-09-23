<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\RemoveMemberAction;
use App\Actions\UpdateMemberRoleAction;
use App\Data\MemberRoleFormData;
use App\Data\TenantInvitationData;
use App\Data\TenantMemberData;
use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Support\TenantContext;
use App\Support\TenantDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $this->tenant();
        $user = $request->user();

        $joinedAt = $tenant->members()->pluck('tenant_user.joined_at', 'users.id')->all();

        $members = $tenant->members()->orderBy('users.name')->get()->map(
            fn (User $member) => $this->memberData($tenant, $user, $member, $joinedAt),
        )->all();

        $invitations = TenantInvitation::query()
            ->forTenant($tenant)
            ->pending()
            ->orderBy('created_at')
            ->get()
            ->map(
                fn (TenantInvitation $invitation) => $this->invitationData($user, $invitation),
            )->all();

        return Inertia::render('members/Index', [
            'members' => $members,
            'invitations' => $invitations,
            'roles' => array_map(
                static fn (TenantRole $role): string => $role->value,
                TenantRole::invitable(),
            ),
            'can' => [
                'invite' => $user->can('create', TenantInvitation::class),
            ],
        ]);
    }

    public function update(MemberRoleFormData $data, User $user, UpdateMemberRoleAction $action): RedirectResponse
    {
        $tenant = $this->tenant();

        Gate::authorize('updateRole', [$tenant, $user]);

        if (! in_array($data->role, TenantRole::invitable(), true)) {
            abort(422, 'Role pemilik tidak bisa diberikan lewat sini.');
        }

        $action->handle($tenant, $user, $data->role);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role anggota diperbarui.']);

        return to_route('members.index');
    }

    public function destroy(User $user, RemoveMemberAction $action): RedirectResponse
    {
        $tenant = $this->tenant();

        Gate::authorize('removeMember', [$tenant, $user]);

        $action->handle($tenant, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anggota dikeluarkan.']);

        return to_route('members.index');
    }

    private function tenant(): Tenant
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    /**
     * @param  array<int, mixed>  $joinedAt  tanggal bergabung per user id
     */
    private function memberData(Tenant $tenant, User $viewer, User $member, array $joinedAt): TenantMemberData
    {
        $rawJoinedAt = $joinedAt[$member->getKey()] ?? null;
        $role = TenantDestination::roleIn($tenant, $member);

        return new TenantMemberData(
            id: $member->getKey(),
            name: $member->name,
            email: $member->email,
            role: $role === null ? TenantRole::Member->value : $role->value,
            joined_at: is_string($rawJoinedAt) ? Carbon::parse($rawJoinedAt)->toDateString() : null,
            is_owner: $tenant->owner_id === $member->getKey(),
            is_current_user: $viewer->getKey() === $member->getKey(),
            can_change_role: $viewer->can('updateRole', [$tenant, $member]),
            can_remove: $viewer->can('removeMember', [$tenant, $member]),
        );
    }

    private function invitationData(User $viewer, TenantInvitation $invitation): TenantInvitationData
    {
        return new TenantInvitationData(
            id: $invitation->getKey(),
            email: $invitation->email,
            role: $invitation->role,
            status: $invitation->status,
            expires_at: $invitation->expires_at->toDateString(),
            accept_url: route('invitations.show', $invitation->token),
            can_resend: $viewer->can('create', TenantInvitation::class),
            can_revoke: $viewer->can('delete', $invitation),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\UpsertAccountAction;
use App\Data\AccountData;
use App\Data\AccountFormData;
use App\Enums\AccountType;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('accounts/Index', [
            'accounts' => AccountData::collect(
                Account::query()->orderedForListing()->with('media')->get(),
            ),
            'can' => [
                'create' => $request->user()->can('create', Account::class),
                'manage' => $this->canManage($request),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Account::class);

        return Inertia::render('accounts/Create', [
            'types' => AccountType::values(),
        ]);
    }

    public function store(
        AccountFormData $data,
        Request $request,
        UpsertAccountAction $action,
    ): RedirectResponse {
        Gate::authorize('create', Account::class);

        $request->validate(['logo' => ['nullable', 'image', 'max:2048']]);

        $action->handle(
            $data,
            user: $request->user(),
            logo: $request->file('logo'),
            removeLogo: $request->boolean('remove_logo'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kantong disimpan.']);

        return to_route('accounts.index');
    }

    public function edit(Account $account): Response
    {
        Gate::authorize('update', $account);

        return Inertia::render('accounts/Edit', [
            'account' => AccountData::from($account->load('media')),
            'types' => AccountType::values(),
        ]);
    }

    public function update(
        AccountFormData $data,
        Account $account,
        Request $request,
        UpsertAccountAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $account);

        $request->validate(['logo' => ['nullable', 'image', 'max:2048']]);

        $action->handle(
            $data,
            $account,
            $request->user(),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kantong disimpan.']);

        return to_route('accounts.index');
    }

    public function destroy(Account $account): RedirectResponse
    {
        Gate::authorize('delete', $account);

        $account->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kantong dihapus.']);

        return to_route('accounts.index');
    }

    private function canManage(Request $request): bool
    {
        return $request->user()->can(PermissionEnum::AccountsManage->value);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\UpsertAccountAction;
use App\Data\AccountData;
use App\Data\AccountFormData;
use App\Data\CreditCardDetailData;
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
            'accounts' => Account::query()->orderedForListing()
                ->with(['media', 'creditCardDetail'])
                ->get()
                ->map(fn (Account $account) => $this->toData($account))
                ->all(),
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
            'account' => $this->toData($account->load(['media', 'creditCardDetail'])),
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

    private function toData(Account $account): AccountData
    {
        $detail = $account->creditCardDetail;

        return new AccountData(
            id: $account->getKey(),
            name: $account->name,
            type: $account->type,
            balance: (string) $account->balance,
            initial_balance: (string) $account->initial_balance,
            is_archived: $account->is_archived,
            logo_url: $account->logo_url,
            credit_detail: $detail === null ? null : new CreditCardDetailData(
                billing_cycle_start_day: $detail->billing_cycle_start_day,
                billing_cycle_end_day: $detail->billing_cycle_end_day,
                payment_due_offset_days: $detail->payment_due_offset_days,
                default_interest_rate_monthly: (string) $detail->default_interest_rate_monthly,
                default_admin_fee_percentage: (string) $detail->default_admin_fee_percentage,
                credit_limit: $detail->credit_limit === null ? null : (string) $detail->credit_limit,
            ),
        );
    }
}

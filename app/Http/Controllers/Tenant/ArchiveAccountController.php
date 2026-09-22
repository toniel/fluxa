<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Render hanya untuk toggle arsip, supaya AccountController tetap murni REST
 * (konvensi 1: controller hanya aksi REST; invokable dihitung sebagai wiring).
 */
class ArchiveAccountController extends Controller
{
    public function __invoke(Account $account): RedirectResponse
    {
        Gate::authorize('archive', $account);

        $account->update(['is_archived' => ! $account->is_archived]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $account->is_archived
                ? 'Kantong diarsipkan.'
                : 'Kantong dikembalikan.',
        ]);

        return to_route('accounts.index');
    }
}

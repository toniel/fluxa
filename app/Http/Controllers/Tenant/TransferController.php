<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\UpsertTransferAction;
use App\Data\TransferData;
use App\Data\TransferFormData;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransferController extends Controller
{
    public function index(Request $request): Response
    {
        $transfers = Transfer::query()
            ->with(['fromAccount', 'toAccount', 'creator'])
            ->filterByQueryString()
            ->searchByQueryString()
            ->sortByQueryString()
            ->latestFirst()
            ->get();

        return Inertia::render('transfers/Index', [
            'transfers' => $transfers->map(
                fn (Transfer $transfer) => $this->toData($request, $transfer),
            )->all(),
            'accounts' => Account::query()->active()->orderedForListing()->get(['id', 'name', 'type', 'balance']),
            'can' => [
                'create' => $request->user()->can('create', Transfer::class),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Transfer::class);

        return Inertia::render('transfers/Create', $this->options());
    }

    public function show(Transfer $transfer): Response
    {
        Gate::authorize('view', $transfer);

        $transfer->load(['fromAccount', 'toAccount', 'creator']);

        return Inertia::render('transfers/Show', [
            'transfer' => $this->toData(request(), $transfer),
        ]);
    }

    public function store(TransferFormData $data, Request $request, UpsertTransferAction $action): RedirectResponse
    {
        Gate::authorize('create', Transfer::class);

        $action->handle($data, user: $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transfer disimpan.']);

        return to_route('transfers.index');
    }

    public function edit(Transfer $transfer): Response
    {
        Gate::authorize('update', $transfer);

        $transfer->load(['fromAccount', 'toAccount', 'creator']);

        return Inertia::render('transfers/Edit', [
            'transfer' => $this->toData(request(), $transfer),
            ...$this->options(),
        ]);
    }

    public function update(TransferFormData $data, Transfer $transfer, UpsertTransferAction $action): RedirectResponse
    {
        Gate::authorize('update', $transfer);

        $action->handle($data, $transfer);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transfer disimpan.']);

        return to_route('transfers.index');
    }

    public function destroy(Transfer $transfer, UpsertTransferAction $action): RedirectResponse
    {
        Gate::authorize('delete', $transfer);

        $action->delete($transfer);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transfer dihapus.']);

        return to_route('transfers.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            // Kartu kredit dan paylater tidak ikut transfer (pakai Bayar
            // tagihan); tipe dikirim supaya form bisa menjelaskan alasannya.
            'accounts' => Account::query()->active()->orderedForListing()->get(['id', 'name', 'type', 'balance']),
        ];
    }

    private function toData(Request $request, Transfer $transfer): TransferData
    {
        return new TransferData(
            id: $transfer->getKey(),
            from_account_id: $transfer->from_account_id,
            from_account_name: $transfer->fromAccount->name,
            to_account_id: $transfer->to_account_id,
            to_account_name: $transfer->toAccount->name,
            amount: (string) $transfer->amount,
            description: $transfer->description,
            transfer_date: $transfer->transfer_date->toDateString(),
            creator_name: $transfer->creator->name,
            can_edit: $request->user()->can('update', $transfer),
            can_delete: $request->user()->can('delete', $transfer),
        );
    }
}

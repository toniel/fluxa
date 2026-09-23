<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\UpsertTransactionAction;
use App\Data\TransactionData;
use App\Data\TransactionFormData;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $transactions = Transaction::query()
            ->with(['account', 'category', 'creator', 'media'])
            ->filterByQueryString()
            ->searchByQueryString()
            ->sortByQueryString()
            ->latestFirst()
            ->get();

        return Inertia::render('transactions/Index', [
            'transactions' => $transactions->map(
                fn (Transaction $transaction) => $this->toData($request, $transaction),
            )->all(),
            'accounts' => Account::query()->active()->orderedForListing()->get(['id', 'name', 'balance']),
            'categories' => Category::query()->orderedForListing()->get(['id', 'name', 'type']),
            'types' => TransactionType::values(),
            'can' => [
                'create' => $request->user()->can('create', Transaction::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Transaction::class);

        return Inertia::render('transactions/Create', $this->options());
    }

    public function show(Transaction $transaction): Response
    {
        Gate::authorize('view', $transaction);

        $transaction->load(['account', 'category', 'creator', 'media']);

        return Inertia::render('transactions/Show', [
            'transaction' => $this->toData(request(), $transaction),
        ]);
    }

    public function store(
        TransactionFormData $data,
        Request $request,
        UpsertTransactionAction $action,
    ): RedirectResponse {
        Gate::authorize('create', Transaction::class);

        $request->validate(['receipt' => ['nullable', 'image', 'max:2048']]);

        $action->handle(
            $data,
            user: $request->user(),
            receipt: $request->file('receipt'),
            removeReceipt: $request->boolean('remove_receipt'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaksi disimpan.']);

        return to_route('transactions.index');
    }

    public function edit(Transaction $transaction): Response
    {
        Gate::authorize('update', $transaction);

        $transaction->load(['account', 'category', 'creator', 'media']);

        return Inertia::render('transactions/Edit', [
            'transaction' => $this->toData(request(), $transaction),
            ...$this->options(),
        ]);
    }

    public function update(
        TransactionFormData $data,
        Transaction $transaction,
        Request $request,
        UpsertTransactionAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $transaction);

        $request->validate(['receipt' => ['nullable', 'image', 'max:2048']]);

        $action->handle(
            $data,
            $transaction,
            receipt: $request->file('receipt'),
            removeReceipt: $request->boolean('remove_receipt'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaksi disimpan.']);

        return to_route('transactions.index');
    }

    public function destroy(Transaction $transaction, UpsertTransactionAction $action): RedirectResponse
    {
        Gate::authorize('delete', $transaction);

        $action->delete($transaction);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaksi dihapus.']);

        return to_route('transactions.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'accounts' => Account::query()->active()->orderedForListing()->get(['id', 'name', 'balance']),
            'categories' => Category::query()->orderedForListing()->get(['id', 'name', 'type']),
            'types' => TransactionType::values(),
        ];
    }

    private function toData(Request $request, Transaction $transaction): TransactionData
    {
        return new TransactionData(
            id: $transaction->getKey(),
            account_id: $transaction->account_id,
            account_name: $transaction->account->name,
            category_id: $transaction->category_id,
            category_name: $transaction->category?->name,
            category_icon: $transaction->category?->icon,
            type: $transaction->type,
            amount: (string) $transaction->amount,
            description: $transaction->description,
            transaction_date: $transaction->transaction_date->toDateString(),
            creator_name: $transaction->creator->name,
            receipt_url: $transaction->receipt_url,
            can_edit: $request->user()->can('update', $transaction),
            can_delete: $request->user()->can('delete', $transaction),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @extends Builder<Transaction>
 */
class TransactionQueryBuilder extends Builder
{
    public function ofType(TransactionType $type): static
    {
        return $this->where('type', $type->value);
    }

    public function forAccount(int $accountId): static
    {
        return $this->where('account_id', $accountId);
    }

    /**
     * Semua baris yang menyentuh kantong: milik langsung maupun kaki
     * pelunasan dari kantong sumber.
     */
    public function involvingAccount(int $accountId): static
    {
        return $this->where(function (Builder $query) use ($accountId): void {
            $query->where('account_id', $accountId)->orWhere('linked_account_id', $accountId);
        });
    }

    public function forCategory(?int $categoryId): static
    {
        return $categoryId === null
            ? $this->whereNull('category_id')
            : $this->where('category_id', $categoryId);
    }

    public function betweenDates(CarbonInterface $from, CarbonInterface $to): static
    {
        return $this->whereDate('transaction_date', '>=', $from)->whereDate('transaction_date', '<=', $to);
    }

    public function inMonth(CarbonInterface $month): static
    {
        return $this->whereDate('transaction_date', '>=', $month->copy()->startOfMonth())
            ->whereDate('transaction_date', '<=', $month->copy()->endOfMonth());
    }

    public function latestFirst(): static
    {
        return $this->orderByDesc('transaction_date')->orderByDesc('id');
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function sumPerCategory(): Collection
    {
        return $this->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get();
    }
}

<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Transfer;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Transfer>
 */
class TransferQueryBuilder extends Builder
{
    public function betweenDates(CarbonInterface $from, CarbonInterface $to): static
    {
        return $this->whereDate('transfer_date', '>=', $from)->whereDate('transfer_date', '<=', $to);
    }

    public function involvingAccount(int $accountId): static
    {
        return $this->where(function (Builder $query) use ($accountId): void {
            $query->where('from_account_id', $accountId)->orWhere('to_account_id', $accountId);
        });
    }

    public function latestFirst(): static
    {
        return $this->orderByDesc('transfer_date')->orderByDesc('id');
    }
}

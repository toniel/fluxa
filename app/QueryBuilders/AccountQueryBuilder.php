<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Builder<Account>
 */
class AccountQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('is_archived', false);
    }

    public function archived(): static
    {
        return $this->where('is_archived', true);
    }

    public function orderedForListing(): static
    {
        return $this->orderBy('is_archived')->orderBy('name');
    }

    /**
     * Kunci baris akun urut id menaik di dalam transaksi DB.
     *
     * Urutan menaik mencegah deadlock saat dua operasi menyentuh pasangan
     * akun yang sama dari arah berlawanan.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Account>
     */
    public function lockedByIds(array $ids): Collection
    {
        return $this->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
    }
}

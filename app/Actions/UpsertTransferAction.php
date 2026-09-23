<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\TransferFormData;
use App\Models\Account;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class UpsertTransferAction
{
    use AsAction;

    /**
     * Buat transfer, atau perbarui yang dikirim pemanggil.
     *
     * Target update datang dari route binding, bukan dari payload. Kedua
     * akun dikunci urut id menaik dalam satu transaksi DB; total saldo
     * tenant tidak berubah, hanya berpindah kantong.
     *
     * Kantong utang (kartu kredit/paylater) tidak bisa ikut transfer:
     * mengisi kartu lewat sini sama dengan pelunasan yang tercatat sebagai
     * transfer biasa dan mengotori arus kas — pakai Bayar tagihan.
     */
    public function handle(
        TransferFormData $data,
        ?Transfer $transfer = null,
        ?User $user = null,
    ): Transfer {
        $from = $this->accountFor($data->from_account_id, 'from_account_id');
        $to = $this->accountFor($data->to_account_id, 'to_account_id');

        if ($transfer instanceof Transfer) {
            return DB::transaction(function () use ($data, $transfer, $from, $to): Transfer {
                $oldFrom = $this->accountFor($transfer->from_account_id, 'from_account_id');
                $oldTo = $this->accountFor($transfer->to_account_id, 'to_account_id');

                $ids = array_unique([$oldFrom->getKey(), $oldTo->getKey(), $from->getKey(), $to->getKey()]);
                sort($ids);
                Account::query()->lockedByIds($ids);

                $this->adjust($oldFrom->getKey(), $this->positive($transfer->amount));
                $this->adjust($oldTo->getKey(), $this->negate($transfer->amount));

                $transfer->update([
                    'from_account_id' => $data->from_account_id,
                    'to_account_id' => $data->to_account_id,
                    'amount' => $data->amount,
                    'description' => $data->description,
                    'transfer_date' => $data->transfer_date,
                ]);

                $this->adjust($data->from_account_id, $this->negate($data->amount));
                $this->adjust($data->to_account_id, $this->positive($data->amount));

                return $transfer->refresh();
            });
        }

        return DB::transaction(function () use ($data, $from, $to, $user): Transfer {
            $ids = [$from->getKey(), $to->getKey()];
            sort($ids);
            Account::query()->lockedByIds($ids);

            $transfer = new Transfer([
                'from_account_id' => $data->from_account_id,
                'to_account_id' => $data->to_account_id,
                'amount' => $data->amount,
                'description' => $data->description,
                'transfer_date' => $data->transfer_date,
                'created_by' => $user?->getKey(),
            ]);
            $transfer->save();

            $this->adjust($transfer->from_account_id, $this->negate($transfer->amount));
            $this->adjust($transfer->to_account_id, $this->positive($transfer->amount));

            return $transfer;
        });
    }

    /**
     * Hapus transfer (soft delete) sambil mengembalikan kedua saldo.
     */
    public function delete(Transfer $transfer): void
    {
        DB::transaction(function () use ($transfer): void {
            $from = $this->accountFor($transfer->from_account_id, 'from_account_id');
            $to = $this->accountFor($transfer->to_account_id, 'to_account_id');

            $ids = [$from->getKey(), $to->getKey()];
            sort($ids);
            Account::query()->lockedByIds($ids);

            $this->adjust($from->getKey(), $this->positive($transfer->amount));
            $this->adjust($to->getKey(), $this->negate($transfer->amount));

            $transfer->delete();
        });
    }

    private function accountFor(int $accountId, string $field): Account
    {
        $account = Account::query()->whereKey($accountId)->first();

        if (! $account instanceof Account) {
            throw ValidationException::withMessages([
                $field => 'Kantong tidak ditemukan.',
            ]);
        }

        if ($account->is_archived) {
            throw ValidationException::withMessages([
                $field => 'Kantong yang diarsipkan tidak bisa dipakai transfer.',
            ]);
        }

        if ($account->type->isLiability()) {
            throw ValidationException::withMessages([
                $field => 'Kartu kredit dan paylater tidak bisa ikut transfer; pakai Bayar tagihan.',
            ]);
        }

        return $account;
    }

    private function adjust(int $accountId, string $delta): void
    {
        if (! is_numeric($delta)) {
            throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
        }

        $query = Account::query()->whereKey($accountId);

        if (bccomp($delta, '0', 2) < 0) {
            $query->decrement('balance', (float) bcmul($delta, '-1', 2));
        } else {
            $query->increment('balance', (float) $delta);
        }
    }

    private function positive(string $amount): string
    {
        if (! is_numeric($amount)) {
            throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
        }

        return $amount;
    }

    private function negate(string $amount): string
    {
        if (! is_numeric($amount)) {
            throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
        }

        return bcmul($amount, '-1', 2);
    }
}

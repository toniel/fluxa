<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\TransactionFormData;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class UpsertTransactionAction
{
    use AsAction;

    /**
     * Buat transaksi, atau perbarui yang dikirim pemanggil.
     *
     * Target update datang dari route binding, bukan dari payload. Setiap
     * tulis berjalan dalam satu transaksi DB: SEMUA akun yang terlibat
     * (termasuk kaki pelunasan) dikunci urut id menaik, lalu tiap kaki
     * disesuaikan supaya dua request bersamaan tidak saling menimpa.
     *
     * Struk difoto lewat spatie medialibrary (koleksi `receipt`, satu file)
     * dan selalu opsional. `removeReceipt` menghapus struk yang ada.
     */
    public function handle(
        TransactionFormData $data,
        ?Transaction $transaction = null,
        ?User $user = null,
        ?UploadedFile $receipt = null,
        bool $removeReceipt = false,
    ): Transaction {
        $account = $this->accountFor($data->account_id);
        $linked = $this->linkedFor($data, $account);
        $this->checkCategory($data->category_id, $data->type);

        if ($transaction instanceof Transaction) {
            return DB::transaction(function () use ($data, $transaction, $account, $linked, $receipt, $removeReceipt): Transaction {
                $oldAccount = $this->accountFor($transaction->account_id);
                $oldLinked = $transaction->linked_account_id !== null
                    ? $this->accountFor($transaction->linked_account_id)
                    : null;

                $ids = array_unique(array_filter([
                    $oldAccount->getKey(),
                    $oldLinked?->getKey(),
                    $account->getKey(),
                    $linked?->getKey(),
                ]));
                sort($ids);
                Account::query()->lockedByIds($ids);

                foreach ($this->legsFor($transaction->type, $transaction->amount, $oldAccount, $oldLinked) as [$legId, $legDelta]) {
                    $this->adjust($legId, $this->negate($legDelta));
                }

                $transaction->update([
                    'account_id' => $data->account_id,
                    'category_id' => $data->category_id,
                    'linked_account_id' => $data->linked_account_id,
                    'type' => $data->type->value,
                    'amount' => $data->amount,
                    'description' => $data->description,
                    'transaction_date' => $data->transaction_date,
                ]);

                foreach ($this->legsFor($data->type, $data->amount, $account, $linked) as [$legId, $legDelta]) {
                    $this->adjust($legId, $legDelta);
                }

                $this->applyReceipt($transaction, $receipt, $removeReceipt);

                return $transaction->refresh();
            });
        }

        return DB::transaction(function () use ($data, $account, $linked, $user, $receipt, $removeReceipt): Transaction {
            $ids = array_unique(array_filter([$account->getKey(), $linked?->getKey()]));
            sort($ids);
            Account::query()->lockedByIds($ids);

            $transaction = new Transaction([
                'account_id' => $data->account_id,
                'category_id' => $data->category_id,
                'linked_account_id' => $data->linked_account_id,
                'type' => $data->type->value,
                'amount' => $data->amount,
                'description' => $data->description,
                'transaction_date' => $data->transaction_date,
                'created_by' => $user?->getKey(),
            ]);
            $transaction->save();

            foreach ($this->legsFor($data->type, $data->amount, $account, $linked) as [$legId, $legDelta]) {
                $this->adjust($legId, $legDelta);
            }

            $this->applyReceipt($transaction, $receipt, $removeReceipt);

            return $transaction;
        });
    }

    /**
     * Hapus transaksi (soft delete) sambil membalikkan efek saldonya.
     */
    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $account = $this->accountFor($transaction->account_id);
            $linked = $transaction->linked_account_id !== null
                ? $this->accountFor($transaction->linked_account_id)
                : null;

            $ids = array_unique(array_filter([$account->getKey(), $linked?->getKey()]));
            sort($ids);
            Account::query()->lockedByIds($ids);

            foreach ($this->legsFor($transaction->type, $transaction->amount, $account, $linked) as [$legId, $legDelta]) {
                $this->adjust($legId, $this->negate($legDelta));
            }

            $transaction->delete();
        });
    }

    private function applyReceipt(Transaction $transaction, ?UploadedFile $receipt, bool $removeReceipt): void
    {
        // Koleksi satu file mengganti file lama otomatis saat menerima yang
        // baru, jadi tidak perlu menghapus manual dulu seperti pada logo
        // kantong.
        if ($removeReceipt) {
            $transaction->clearMediaCollection('receipt');

            return;
        }

        if ($receipt !== null) {
            $transaction->addMedia($receipt)->toMediaCollection('receipt');
        }
    }

    private function accountFor(int $accountId): Account
    {
        $account = Account::query()->whereKey($accountId)->first();

        if (! $account instanceof Account) {
            throw ValidationException::withMessages([
                'account_id' => 'Kantong tidak ditemukan.',
            ]);
        }

        if ($account->is_archived) {
            throw ValidationException::withMessages([
                'account_id' => 'Kantong yang diarsipkan tidak bisa dipakai transaksi baru.',
            ]);
        }

        return $account;
    }

    private function checkCategory(?int $categoryId, TransactionType $type): void
    {
        if ($type === TransactionType::BillPayment) {
            if ($categoryId !== null) {
                throw ValidationException::withMessages([
                    'category_id' => 'Pelunasan tagihan tidak memakai kategori.',
                ]);
            }

            return;
        }

        if ($categoryId === null) {
            return;
        }

        $category = Category::query()->whereKey($categoryId)->first();

        if (! $category instanceof Category) {
            throw ValidationException::withMessages([
                'category_id' => 'Kategori tidak ditemukan.',
            ]);
        }

        if ($category->type->value !== $type->value) {
            throw ValidationException::withMessages([
                'category_id' => 'Kategori tidak sesuai dengan jenis transaksi.',
            ]);
        }
    }

    /**
     * Akun sumber pelunasan: wajib untuk bill_payment, terlarang untuk jenis
     * lain. Sumbernya harus kantong aset (membayar kartu dengan kartu tidak
     * didukung) dan berbeda dari kartunya.
     */
    private function linkedFor(TransactionFormData $data, Account $account): ?Account
    {
        if ($data->type !== TransactionType::BillPayment) {
            if ($data->linked_account_id !== null) {
                throw ValidationException::withMessages([
                    'linked_account_id' => 'Akun sumber hanya untuk bayar tagihan.',
                ]);
            }

            return null;
        }

        if (! $account->type->isLiability()) {
            throw ValidationException::withMessages([
                'account_id' => 'Bayar tagihan hanya untuk kantong kartu kredit atau paylater.',
            ]);
        }

        if ($data->linked_account_id === null) {
            throw ValidationException::withMessages([
                'linked_account_id' => 'Pilih kantong sumber pembayaran.',
            ]);
        }

        $linked = $this->accountFor($data->linked_account_id);

        if ($linked->getKey() === $account->getKey()) {
            throw ValidationException::withMessages([
                'linked_account_id' => 'Sumber pembayaran tidak boleh sama dengan kartunya.',
            ]);
        }

        if ($linked->type->isLiability()) {
            throw ValidationException::withMessages([
                'linked_account_id' => 'Sumber pembayaran harus kantong aset, bukan utang.',
            ]);
        }

        return $linked;
    }

    /**
     * Kaki-kaki saldo transaksi: pasangan [account_id, delta]. Pelunasan
     * menurunkan kedua kaki; selain itu arah mengikuti jenis akun.
     *
     * @return list<array{int, string}>
     */
    private function legsFor(TransactionType $type, string $amount, Account $account, ?Account $linked): array
    {
        if (! is_numeric($amount)) {
            throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
        }

        if ($type === TransactionType::BillPayment) {
            if (! $linked instanceof Account) {
                throw ValidationException::withMessages([
                    'linked_account_id' => 'Pilih kantong sumber pembayaran.',
                ]);
            }

            $delta = bcmul($amount, '-1', 2);

            return [
                [$account->getKey(), $delta],
                [$linked->getKey(), $delta],
            ];
        }

        return [[$account->getKey(), $this->signed($type, $amount, $account->type->balanceDirection())]];
    }

    private function adjust(int $accountId, string $delta): void
    {
        if (! is_numeric($delta)) {
            throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
        }

        // increment/decrement menulis `balance + ?` di sisi DB, jadi dua
        // request bersamaan tidak saling menimpa. API-nya menerima float;
        // aman untuk uang desimal(15,2) karena PHP mencetak float dengan
        // representasi desimal terpendek yang bolak-balik tepat, lalu SQL
        // mem-parsingnya sebagai literal desimal eksak.
        $query = Account::query()->whereKey($accountId);

        if (bccomp($delta, '0', 2) < 0) {
            $query->decrement('balance', (float) bcmul($delta, '-1', 2));
        } else {
            $query->increment('balance', (float) $delta);
        }
    }

    private function signed(TransactionType $type, string $amount, int $direction): string
    {
        if (! is_numeric($amount)) {
            throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
        }

        return bcmul($amount, (string) ($type->signum() * $direction), 2);
    }

    private function negate(string $signed): string
    {
        if (! is_numeric($signed)) {
            throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
        }

        return bcmul($signed, '-1', 2);
    }
}

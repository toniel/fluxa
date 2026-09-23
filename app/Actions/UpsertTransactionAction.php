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
     * tulis berjalan dalam satu transaksi DB: baris akun dikunci urut id
     * menaik, lalu saldo disesuaikan lewat ekspresi SQL supaya dua request
     * bersamaan tidak saling menimpa.
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
        $this->checkCategory($data->category_id, $data->type);

        if ($transaction instanceof Transaction) {
            return DB::transaction(function () use ($data, $transaction, $account, $receipt, $removeReceipt): Transaction {
                $oldAccountId = $transaction->account_id;
                $oldSigned = $transaction->signedAmount();

                $ids = array_unique([$oldAccountId, $account->getKey()]);
                sort($ids);
                Account::query()->lockedByIds($ids);

                $this->adjust($oldAccountId, $this->negate($oldSigned));

                $transaction->update([
                    'account_id' => $data->account_id,
                    'category_id' => $data->category_id,
                    'type' => $data->type->value,
                    'amount' => $data->amount,
                    'description' => $data->description,
                    'transaction_date' => $data->transaction_date,
                ]);

                $this->adjust($data->account_id, $transaction->fresh()?->signedAmount() ?? $this->signed($data->type, $data->amount));

                $this->applyReceipt($transaction, $receipt, $removeReceipt);

                return $transaction->refresh();
            });
        }

        return DB::transaction(function () use ($data, $account, $user, $receipt, $removeReceipt): Transaction {
            Account::query()->lockedByIds([$account->getKey()]);

            $transaction = new Transaction([
                'account_id' => $data->account_id,
                'category_id' => $data->category_id,
                'type' => $data->type->value,
                'amount' => $data->amount,
                'description' => $data->description,
                'transaction_date' => $data->transaction_date,
                'created_by' => $user?->getKey(),
            ]);
            $transaction->save();

            $this->adjust($transaction->account_id, $transaction->signedAmount());

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
            Account::query()->lockedByIds([$transaction->account_id]);

            $this->adjust($transaction->account_id, $this->negate($transaction->signedAmount()));

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

    private function signed(TransactionType $type, string $amount): string
    {
        if (! is_numeric($amount)) {
            throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
        }

        return bcmul($amount, (string) $type->signum(), 2);
    }

    private function negate(string $signed): string
    {
        if (! is_numeric($signed)) {
            throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
        }

        return bcmul($signed, '-1', 2);
    }
}

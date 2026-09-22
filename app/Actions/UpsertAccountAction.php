<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\AccountFormData;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsAction;

class UpsertAccountAction
{
    use AsAction;

    /**
     * Buat kantong, atau perbarui yang dikirim pemanggil.
     *
     * Saat create, `balance` disamakan dengan `initial_balance`: saldo berjalan
     * kantong baru selalu mulai dari nol relatif. Saat update, `balance` dan
     * `initial_balance` sengaja tidak tersentuh (initial_balance terkunci;
     * balance hanya bisa bergerak lewat Action transaksi kelak).
     *
     * Logo diunggah lewat spatie medialibrary (koleksi `logo`, satu file).
     * `removeLogo` menghapus logo yang ada, misal tombol bersihkan di form.
     *
     * Target update datang dari route binding, bukan dari payload.
     */
    public function handle(
        AccountFormData $data,
        ?Account $account = null,
        ?User $user = null,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
    ): Account {
        if ($account instanceof Account) {
            $account->update(Arr::except($data->toArray(), ['initial_balance']));

            $this->applyLogo($account, $logo, $removeLogo);

            return $account;
        }

        // new Account() + forceFill: balance bukan fillable, dan satu-satunya
        // jalan mengisinya adalah di sini.
        $account = new Account($data->toArray() + ['created_by' => $user?->getKey()]);
        $account->forceFill(['balance' => $data->initial_balance]);
        $account->save();

        $this->applyLogo($account, $logo, $removeLogo);

        return $account;
    }

    private function applyLogo(Account $account, ?UploadedFile $logo, bool $removeLogo): void
    {
        // Logo single-file: medialibrary mengganti file lama OTOMATIS saat
        // koleksi bernama 'logo' menerima file baru — file lama dihapus
        // belakangan, setelah yang baru berhasil tersimpan. Menghapus
        // koleksi manual duluan (clearMediaCollection) bikin dua operasi
        // disk terpisah yang di lingkungan ini kadang bentrok di tengah
        // proses (file lama hilang, file baru belum sempat masuk).
        if ($removeLogo) {
            $account->clearMediaCollection('logo');

            return;
        }

        if ($logo !== null) {
            $account->addMedia($logo)->toMediaCollection('logo');
        }
    }
}

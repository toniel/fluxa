<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\AccountFormData;
use App\Models\Account;
use App\Models\User;
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
     * Target update datang dari route binding, bukan dari payload.
     */
    public function handle(AccountFormData $data, ?Account $account = null, ?User $user = null): Account
    {
        if ($account instanceof Account) {
            $account->update(Arr::except($data->toArray(), ['initial_balance']));

            return $account;
        }

        // new Account() + forceFill: balance bukan fillable, dan satu-satunya
        // jalan mengisinya adalah di sini.
        $account = new Account($data->toArray() + ['created_by' => $user?->getKey()]);
        $account->forceFill(['balance' => $data->initial_balance]);
        $account->save();

        return $account;
    }
}

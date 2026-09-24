<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\AccountFormData;
use App\Models\Account;
use App\Models\User;
use App\Support\PlanFeatureChecker;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class UpsertAccountAction
{
    use AsAction;

    /**
     * Buat kantong, atau perbarui yang dikirim pemanggil.
     *
     * Saat create, `balance` disamakan dengan `initial_balance`: saldo berjalan
     * kantong baru selalu mulai dari nol relatif. Untuk kartu kredit/paylater
     * itu berarti utang awal. Saat update, `balance` dan `initial_balance`
     * sengaja tidak tersentuh (initial_balance terkunci; balance hanya bisa
     * bergerak lewat Action transaksi).
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
            $account->update(Arr::except($data->toArray(), [
                'initial_balance',
                'credit_limit',
                'billing_cycle_start_day',
                'billing_cycle_end_day',
                'payment_due_offset_days',
                'default_interest_rate_monthly',
                'default_admin_fee_percentage',
            ]));

            $this->applyCreditDetail($account->refresh(), $data);
            $this->applyLogo($account, $logo, $removeLogo);

            return $account;
        }

        // new Account() + forceFill: balance bukan fillable, dan satu-satunya
        // jalan mengisinya adalah di sini.
        $this->assertWithinAccountLimit();

        $account = new Account(Arr::except($data->toArray(), [
            'credit_limit',
            'billing_cycle_start_day',
            'billing_cycle_end_day',
            'payment_due_offset_days',
            'default_interest_rate_monthly',
            'default_admin_fee_percentage',
        ]) + ['created_by' => $user?->getKey()]);
        $account->forceFill(['balance' => $data->initial_balance]);
        $account->save();

        $this->applyCreditDetail($account, $data);
        $this->applyLogo($account, $logo, $removeLogo);

        return $account;
    }

    /**
     * Batas kantong hanya untuk pembuatan, bukan ubah: mengunci jumlah saat
     * tenant sudah penuh. Tanpa tenant aktif (seeder, console) pemeriksaan
     * dibuka supaya pekerjaan operasional tidak terkunci.
     */
    private function assertWithinAccountLimit(): void
    {
        $tenant = app(TenantContext::class)->tenant();

        if ($tenant === null) {
            return;
        }

        if (app(PlanFeatureChecker::class)->atAccountLimit($tenant)) {
            throw ValidationException::withMessages([
                'name' => 'Paket ini mencapai batas kantong. Upgrade untuk menambah.',
            ]);
        }
    }

    private function applyCreditDetail(Account $account, AccountFormData $data): void
    {
        if (! $account->type->isLiability()) {
            $account->creditCardDetail()->delete();

            return;
        }

        if (
            $data->billing_cycle_start_day === null
            || $data->billing_cycle_end_day === null
            || $data->payment_due_offset_days === null
        ) {
            throw ValidationException::withMessages([
                'billing_cycle_end_day' => 'Lengkapi pengaturan siklus tagihan.',
            ]);
        }

        $account->creditCardDetail()->updateOrCreate([], [
            'billing_cycle_start_day' => $data->billing_cycle_start_day,
            'billing_cycle_end_day' => $data->billing_cycle_end_day,
            'payment_due_offset_days' => $data->payment_due_offset_days,
            'default_interest_rate_monthly' => $data->default_interest_rate_monthly ?? '0',
            'default_admin_fee_percentage' => $data->default_admin_fee_percentage ?? '0',
            'credit_limit' => $data->credit_limit,
        ]);
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

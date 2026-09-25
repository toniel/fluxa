<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteSuperAdminCommand extends Command
{
    protected $signature = 'user:promote {email : Email user yang dijadikan super-admin} {--revoke : Cabut kembali status super-admin}';

    protected $description = 'Jadikan (atau cabut) super-admin platform berdasarkan email';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user instanceof User) {
            $this->error("User dengan email {$this->argument('email')} tidak ditemukan.");

            return self::FAILURE;
        }

        // forceFill disengaja: flag ini tidak boleh fillable (tidak ada
        // jalur request yang boleh menyentuhnya), hanya console ini.
        $user->forceFill(['is_super_admin' => ! $this->option('revoke')])->save();

        $this->info($this->option('revoke')
            ? "Status super-admin {$user->email} dicabut."
            : "{$user->email} sekarang super-admin.");

        return self::SUCCESS;
    }
}

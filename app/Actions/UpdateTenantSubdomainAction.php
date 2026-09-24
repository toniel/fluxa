<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateTenantSubdomainAction
{
    use AsAction;

    /**
     * Ganti subdomain utama tenant. Alamat lama TIDAK dihapus: ditandai
     * redirects_to supaya tautan yang sudah dibagikan tetap bisa dibuka.
     */
    public function handle(Tenant $tenant, string $subdomain): Tenant
    {
        $subdomain = Str::lower(trim($subdomain));

        $this->checkAvailable($subdomain, $tenant);

        return DB::transaction(function () use ($tenant, $subdomain): Tenant {
            $current = $tenant->domains()->where('is_primary', true)->first();

            if ($current !== null && $current->domain !== $subdomain) {
                $current->update(['is_primary' => false, 'redirects_to' => $subdomain]);
            }

            $tenant->domains()->updateOrCreate(
                ['domain' => $subdomain],
                ['is_primary' => true, 'redirects_to' => null],
            );

            return $tenant->refresh();
        });
    }

    private function checkAvailable(string $subdomain, Tenant $tenant): void
    {
        if ($subdomain === '') {
            throw ValidationException::withMessages([
                'subdomain' => 'Subdomain tidak boleh kosong.',
            ]);
        }

        if (strlen($subdomain) < 3 || strlen($subdomain) > 30 || ! preg_match('/^[a-z0-9-]+$/', $subdomain)) {
            throw ValidationException::withMessages([
                'subdomain' => 'Subdomain 3-30 karakter huruf, angka, dan strip.',
            ]);
        }

        if (in_array($subdomain, config('fluxa.reserved_subdomains', []), true)) {
            throw ValidationException::withMessages([
                'subdomain' => 'Subdomain ini tidak tersedia.',
            ]);
        }

        $taken = $tenant->domains()->where('domain', $subdomain)->exists()
            ? false
            : Domain::query()->where('domain', $subdomain)->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'subdomain' => 'Subdomain ini sudah dipakai.',
            ]);
        }
    }
}

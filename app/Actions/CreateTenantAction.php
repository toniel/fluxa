<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CategoryType;
use App\Enums\TenantRole;
use App\Models\Category;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateTenantAction
{
    use AsAction;

    /**
     * Buat tenant baru untuk user: tenant + subdomain + pivot + role owner +
     * kategori bawaan + langganan free. Satu transaksi DB.
     */
    public function handle(User $user, string $name, ?string $subdomain = null): Tenant
    {
        return DB::transaction(function () use ($user, $name, $subdomain): Tenant {
            $tenant = Tenant::create([
                'name' => $name,
                'owner_id' => $user->getKey(),
            ]);

            $tenant->domains()->create([
                'domain' => $subdomain ?? $this->generateSubdomain($name),
                'is_primary' => true,
            ]);

            $tenant->members()->attach($user->getKey(), ['joined_at' => now()]);

            // Dua context dijaga sekaligus: runFor mengisi tenant_id lewat
            // creating hook, tenancy()->initialize memasang team id spatie
            // lewat SyncPermissionTeam supaya syncRoles ter-scope benar.
            // Dipanggil dari central domain (tanpa tenancy aktif), jadi aman
            // diakhiri di finally.
            app(TenantContext::class)->runFor($tenant, function () use ($tenant, $user): void {
                tenancy()->initialize($tenant);

                try {
                    $user->syncRoles([TenantRole::Owner->value]);
                    $this->seedDefaultCategories();
                    app(StartFreeSubscriptionAction::class)->handle($tenant);
                } finally {
                    tenancy()->end();
                }
            });

            return $tenant;
        });
    }

    private function seedDefaultCategories(): void
    {
        $configured = config('fluxa.default_categories');

        if (! is_array($configured)) {
            return;
        }

        foreach ($configured as $category) {
            if (! is_array($category)) {
                continue;
            }

            $name = $category['name'] ?? null;
            $type = isset($category['type']) && is_string($category['type'])
                ? CategoryType::tryFrom($category['type'])
                : null;

            if (! is_string($name) || ! $type instanceof CategoryType) {
                continue;
            }

            Category::firstOrCreate(
                ['name' => $name, 'type' => $type->value],
                [
                    'icon' => isset($category['icon']) && is_string($category['icon']) ? $category['icon'] : null,
                    'is_default' => true,
                ],
            );
        }
    }

    private function generateSubdomain(string $name): string
    {
        $reserved = config('fluxa.reserved_subdomains', []);

        do {
            $candidate = Str::slug(Str::limit($name, 24, '')) ?: 'tenant';
            $candidate .= '-'.Str::lower(Str::random(4));
        } while (in_array($candidate, $reserved, true) || $this->subdomainTaken($candidate));

        return $candidate;
    }

    private function subdomainTaken(string $subdomain): bool
    {
        return Domain::query()->where('domain', $subdomain)->exists();
    }
}

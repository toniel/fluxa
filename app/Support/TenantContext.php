<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;

/**
 * Tenant aktif untuk sebuah request.
 *
 * Di HTTP, tenant datang dari stancl/tenancy: middleware InitializeTenancyBySubdomain
 * me-resolve tenant dari subdomain dan stancl menaruhnya di container, sehingga
 * fallback di bawah membaca `tenant()` tanpa perlu middleware sendiri. Di konsol
 * dan test, tenant disetel eksplisit lewat runFor()/set(), misalnya saat seeder
 * atau aksi membuat baris untuk tenant tertentu.
 */
final class TenantContext
{
    /** Tenant yang disetel eksplisit, mengalahkan tenant dari stancl. */
    private ?Tenant $pinned = null;

    /** Kedalaman tanpa scope; di atas nol artinya bypass sedang aktif. */
    private int $bypassDepth = 0;

    public function tenant(): ?Tenant
    {
        return $this->pinned ?? $this->stanclTenant();
    }

    public function id(): ?int
    {
        return $this->tenant()?->getKey();
    }

    public function check(): bool
    {
        return $this->tenant() !== null;
    }

    public function set(Tenant $tenant): void
    {
        $this->pinned = $tenant;
    }

    public function forget(): void
    {
        $this->pinned = null;
    }

    /**
     * Jalankan callback seolah-olah tenant tertentu yang aktif.
     *
     * Dipakai konsol, seeder, test, dan action lintas tenant (misalnya saat
     * membuat baris untuk tenant baru sebelum tenancy stancl aktif).
     */
    public function runFor(Tenant $tenant, callable $callback): mixed
    {
        $previous = $this->pinned;

        $this->pinned = $tenant;

        try {
            return $callback();
        } finally {
            $this->pinned = $previous;
        }
    }

    /**
     * Jalankan callback tanpa global scope tenant.
     *
     * Dipakai jalur yang memang lintas-tenant dan sudah memvalidasi id sendiri
     * (pemilih tenant sebelum login, seeding lintas tenant).
     */
    public function withoutScope(callable $callback): mixed
    {
        $this->bypassDepth++;

        try {
            return $callback();
        } finally {
            $this->bypassDepth--;
        }
    }

    public function isBypassed(): bool
    {
        return $this->bypassDepth > 0;
    }

    private function stanclTenant(): ?Tenant
    {
        $tenant = tenant();

        return $tenant instanceof Tenant ? $tenant : null;
    }
}

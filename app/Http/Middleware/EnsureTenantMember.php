<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengunci subdomain tenant ke anggotanya.
 *
 * Harus berjalan SETELAH InitializeTenancyBySubdomain: tenant aktif sudah
 * di-resolve dari subdomain, dan di sini kita pastikan user yang login memang
 * berhak atasnya. Tanpa pagar ini, siapa pun yang login bisa membuka subdomain
 * tenant lain, dan TenantScope justru menyuguhkan data tenant itu kepadanya.
 *
 * Jawabannya 404 (bukan 403), konsisten dengan EnsureSuperAdmin dan kebijakan
 * di bootstrap/app.php: orang luar tidak boleh bisa membedakan "tenant tidak
 * ada" dari "tenant ada tapi bukan milikmu".
 */
class EnsureTenantMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = tenant();

        if ($user === null || ! $tenant instanceof Tenant) {
            abort(404);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Owner selalu berhak atas tenantnya; pengecekan pivot menjaga anggota
        // biasa. Keduanya dipakai agar data owner lama tetap aman meski baris
        // pivotnya hilang.
        $isOwner = $tenant->owner_id === $user->getKey();
        $isMember = $tenant->members()->whereKey($user->getKey())->exists();

        if (! $isOwner && ! $isMember) {
            abort(404);
        }

        return $next($request);
    }
}

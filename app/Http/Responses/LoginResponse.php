<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Support\TenantDestination;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menggantikan tujuan bawaan Fortify ('/dashboard' di config/fortify.php).
 *
 * Path itu tidak pernah bisa benar di sini: dashboard hanya dilayani di
 * subdomain tenant, sehingga di central domain ia dijawab 404 oleh
 * PreventAccessFromCentralDomains.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $destination = TenantDestination::afterLogin($user);

        // Redirect biasa tidak bisa menyeberang origin di sini: Inertia
        // mengikuti redirect lewat XHR, dan XHR lintas origin diblokir CORS
        // sehingga user tertahan di halaman login tanpa pesan apa pun.
        // Inertia::location() menjawab 409 + X-Inertia-Location, yang membuat
        // klien melakukan kunjungan halaman penuh ke subdomain tenant.
        if (parse_url($destination, PHP_URL_HOST) !== $request->getHost()) {
            return Inertia::location($destination);
        }

        return redirect()->to($destination);
    }
}

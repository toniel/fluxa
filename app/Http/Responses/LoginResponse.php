<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Support\TenantDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Menggantikan tujuan bawaan Fortify ('/dashboard' di config/fortify.php).
 *
 * Path itu tidak pernah bisa benar di sini: dashboard hanya dilayani di
 * subdomain tenant, sehingga di central domain ia dijawab 404 oleh
 * PreventAccessFromCentralDomains.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        /** @var Request $request */
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        return redirect()->away(TenantDestination::afterLogin($user));
    }
}

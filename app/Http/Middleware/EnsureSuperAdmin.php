<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang area /admin di central domain. Jawabannya 404 (bukan 403) supaya
 * keberadaan panel admin tidak terkonfirmasi ke user biasa.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isSuperAdmin()) {
            abort(404);
        }

        return $next($request);
    }
}

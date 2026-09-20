<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tujuan user yang sudah login saat membuka route tamu. Default
        // Laravel adalah '/dashboard', yang di central domain dijawab 404
        // karena dashboard hanya dilayani di subdomain tenant. Ini terpisah
        // dari config('fortify.home'), jadi keduanya harus disetel.
        $middleware->redirectUsersTo('/tenants');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // AddLinkHeadersForPreloadedAssets sengaja tidak dipasang. Ia mengirim
        // header Link berisi seluruh aset halaman, dan header itu tumbuh tiap
        // kali komponen bertambah: pada /transactions ia sudah 3090 byte,
        // membuat total header melewati 4096 byte dan nginx menjawab 502
        // "upstream sent too big header". Manfaat preload-nya kecil untuk
        // aplikasi Inertia yang asetnya sudah disebut di HTML.
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Subdomain yang tidak terdaftar dijawab 404, bukan 500. Selain lebih
        // benar secara HTTP, ini menutup kebocoran informasi: orang yang
        // menebak-nebak subdomain tidak boleh bisa membedakan "tenant ini tidak
        // ada" dari "tenant ini ada tapi bukan milikmu".
        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $e): Response {
            abort(404);
        });
    })->create();

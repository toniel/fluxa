<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Route yang dilayani di {subdomain}.{central_domain}. Middleware-nya
| dideklarasikan di sini, bukan di TenancyServiceProvider::mapRoutes(),
| karena provider itu hanya mengelompokkan file tanpa membungkus middleware.
|
| PreventAccessFromCentralDomains membuat route di bawah ini menjadi 404
| kalau diakses lewat central domain.
|
*/

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function (): void {
    // Sementara sampai PR berikutnya memasang auth, keanggotaan, dan halaman
    // aplikasi. Dipakai untuk membuktikan identifikasi subdomain bekerja.
    //
    // Sengaja BUKAN '/': route tenant didaftarkan sebelum routes/web.php, jadi
    // '/' di sini akan menyerobot homepage central domain dan membuatnya 404.
    // Pemisahan yang benar (Route::domain() untuk central) menyusul bersama
    // peta route lengkap.
    Route::get('tenant-check', fn (): string => 'Tenant aktif: '.tenant('name').' (id '.tenant('id').')');
});

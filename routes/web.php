<?php

use App\Http\Controllers\TenantPickerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| Dilayani di central domain: landing, autentikasi, dan pemilih tenant.
| Route aplikasi utama ada di routes/tenant.php, di subdomain masing-masing.
|
*/

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function (): void {
    Route::get('tenants', [TenantPickerController::class, 'index'])->name('tenants.index');
});

require __DIR__.'/settings.php';

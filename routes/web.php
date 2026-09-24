<?php

use App\Http\Controllers\Auth\GoogleCallbackController;
use App\Http\Controllers\Auth\GoogleRedirectController;
use App\Http\Controllers\BillingWebhookController;
use App\Http\Controllers\InvitationAcceptanceController;
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

Route::get('auth/google/redirect', GoogleRedirectController::class)->name('auth.google.redirect');
Route::get('auth/google/callback', GoogleCallbackController::class)->name('auth.google.callback');

Route::middleware(['auth'])->group(function (): void {
    Route::get('tenants', [TenantPickerController::class, 'index'])->name('tenants.index');
});

// Halaman terima undangan hidup di central domain, di luar tenant context:
// pengklik belum tentu login atau punya tenant, token-lah otoritasnya.
Route::get('invitations/{token}', [InvitationAcceptanceController::class, 'show'])->name('invitations.show');
Route::post('invitations/{token}/accept', [InvitationAcceptanceController::class, 'store'])
    ->middleware('auth')->name('invitations.accept');

// Webhook billing dari provider (tanpa auth/CSRF; verifikasi di controller).
Route::post('billing/webhook', BillingWebhookController::class)->name('billing.webhook');

require __DIR__.'/settings.php';

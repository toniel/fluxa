<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\AccountController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\MemberController;
use App\Http\Controllers\Tenant\TenantSettingController;
use App\Http\Controllers\Tenant\TransactionController;
use App\Http\Controllers\Tenant\TransferController;
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
| Belum ada middleware auth dan keanggotaan di sini: halaman-halaman ini
| masih pratinjau tampilan dengan data contoh. Keduanya masuk bersama lapisan
| otorisasi, sebelum ada data asli yang bisa bocor.
|
*/

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('members', [MemberController::class, 'index'])->name('members.index');
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('settings/tenant', [TenantSettingController::class, 'edit'])->name('tenant.settings');
});

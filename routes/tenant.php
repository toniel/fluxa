<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\AccountController;
use App\Http\Controllers\Tenant\ArchiveAccountController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\InvitationController;
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
| Kategori dan kantong sudah memakai data asli, jadi grup di atasnya sudah
| diberi middleware auth. Halaman lain (dashboard, transaksi, transfer,
| anggota, billing, pengaturan) masih pratinjau tampilan dengan data contoh;
| masing-masing berhenti memakai SampleData begitu lapisan datanya mendarat.
|
*/

Route::middleware([
    'web',
    'auth',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('accounts', AccountController::class);
    Route::patch('accounts/{account}/archive', ArchiveAccountController::class)->name('accounts.archive');
    Route::resource('transactions', TransactionController::class);
    Route::resource('transfers', TransferController::class);
    Route::resource('categories', CategoryController::class)->except('show');
    Route::get('members', [MemberController::class, 'index'])->name('members.index');
    Route::patch('members/{user}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::resource('invitations', InvitationController::class)->only(['store', 'destroy']);
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing/toggle', [BillingController::class, 'store'])->name('billing.toggle');
    Route::get('settings/tenant', [TenantSettingController::class, 'edit'])->name('tenant.settings');
    Route::patch('settings/tenant', [TenantSettingController::class, 'update'])->name('tenant.settings.update');
    Route::delete('settings/tenant', [TenantSettingController::class, 'destroy'])->name('tenant.settings.destroy');
});

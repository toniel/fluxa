<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\TenantDestination;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pemilih tenant di central domain.
 *
 * Ini tujuan setelah login untuk user yang punya lebih dari satu tenant, dan
 * satu-satunya halaman yang masuk akal untuk user yang belum punya tenant sama
 * sekali. Datanya nyata, bukan contoh: diambil dari keanggotaan user.
 */
class TenantPickerController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('tenants/Index', [
            'memberships' => TenantDestination::membershipsOf($request->user()),
        ]);
    }
}

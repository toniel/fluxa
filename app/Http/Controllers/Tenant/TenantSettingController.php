<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\PreviewState;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('tenant/Settings', [
            'state' => PreviewState::fromRequest($request),
            'tenant' => SampleData::tenant(),
        ]);
    }
}

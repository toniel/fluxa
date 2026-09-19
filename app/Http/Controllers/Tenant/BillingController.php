<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\PreviewState;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('billing/Index', [
            'state' => PreviewState::fromRequest($request),
            'billing' => SampleData::billing(),
            'tenant' => SampleData::tenant(),
        ]);
    }
}

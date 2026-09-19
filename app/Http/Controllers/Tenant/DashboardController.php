<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\PreviewState;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $state = PreviewState::fromRequest($request);
        $dashboard = SampleData::dashboard();

        return Inertia::render('Dashboard', [
            'state' => $state,
            'summary' => $dashboard,
            'breakdown' => $state->rows($dashboard['breakdown']),
            'accounts' => $state->rows(SampleData::accounts()),
            'recent' => array_slice($state->rows(SampleData::transactions()), 0, 4),
            'tenant' => SampleData::tenant(),
        ]);
    }
}

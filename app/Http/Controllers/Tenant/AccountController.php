<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\PreviewState;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $state = PreviewState::fromRequest($request);

        return Inertia::render('accounts/Index', [
            'state' => $state,
            'accounts' => $state->rows(SampleData::accounts()),
            'tenant' => SampleData::tenant(),
        ]);
    }
}

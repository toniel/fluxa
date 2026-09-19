<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\PreviewState;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $state = PreviewState::fromRequest($request);

        return Inertia::render('categories/Index', [
            'state' => $state,
            'categories' => $state->rows(SampleData::categories()),
            'tenant' => SampleData::tenant(),
        ]);
    }
}

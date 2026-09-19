<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\PreviewState;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $state = PreviewState::fromRequest($request);

        return Inertia::render('members/Index', [
            'state' => $state,
            'members' => $state->rows(SampleData::members()),
            'invitations' => $state->rows(SampleData::invitations()),
            'tenant' => SampleData::tenant(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\PreviewState;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransferController extends Controller
{
    public function index(Request $request): Response
    {
        $state = PreviewState::fromRequest($request);

        return Inertia::render('transfers/Index', [
            'state' => $state,
            'transfers' => $state->rows(SampleData::transfers()),
            // Pilihan kantong dikirim apa adanya: belum pernah transfer
            // bukan berarti tidak punya kantong untuk ditransfer.
            'accounts' => SampleData::accounts(),
            'tenant' => SampleData::tenant(),
        ]);
    }
}

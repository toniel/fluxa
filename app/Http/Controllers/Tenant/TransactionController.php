<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\PreviewState;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $state = PreviewState::fromRequest($request);

        return Inertia::render('transactions/Index', [
            'state' => $state,
            'transactions' => $state->rows(SampleData::transactions()),
            // Pilihan form dikirim apa adanya, tidak ikut state kosong:
            // tidak punya transaksi bukan berarti tidak punya kantong.
            'accounts' => SampleData::accounts(),
            'categories' => SampleData::categories(),
            'tenant' => SampleData::tenant(),
        ]);
    }
}

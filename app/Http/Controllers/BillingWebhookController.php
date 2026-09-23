<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\HandleGatewayWebhookAction;
use App\Billing\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook billing di central domain: tanpa auth dan tanpa CSRF. Satu-satunya
 * pertahanan adalah verifikasi signature adapter — jangan tambah logika di
 * sini, taruh di action atau adapter.
 */
class BillingWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, HandleGatewayWebhookAction $action): JsonResponse
    {
        abort_unless($gateway->verifyWebhook($request), 403, 'Tanda webhook tidak valid.');

        $action->handle($gateway, $request);

        return response()->json(['ok' => true]);
    }
}

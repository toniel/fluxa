<?php

declare(strict_types=1);

namespace App\Billing;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Adapter tanpa provider: mencatat ke log, mengembalikan id palsu. Dipakai
 * lokal saat kunci gateway belum ada, dan sebagai bukti kontrak PaymentGateway
 * bisa dipenuhi lebih dari satu implementasi.
 */
final class LogGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'log';
    }

    public function createCustomer(Tenant $tenant, User $owner): string
    {
        $id = 'log-cus-'.Str::lower(Str::random(8));

        Log::info('Billing [log]: customer dibuat.', [
            'tenant_id' => $tenant->getKey(),
            'external_id' => $id,
        ]);

        return $id;
    }

    public function createRecurring(Plan $plan, string $externalCustomerId, string $returnUrl): GatewayCheckout
    {
        $id = 'log-sub-'.Str::lower(Str::random(8));

        Log::info('Billing [log]: recurring dibuat.', [
            'plan' => $plan->slug,
            'external_id' => $id,
        ]);

        return new GatewayCheckout($returnUrl, $id);
    }

    public function cancelRecurring(string $externalSubscriptionId): void
    {
        Log::info('Billing [log]: recurring dihentikan.', [
            'external_id' => $externalSubscriptionId,
        ]);
    }

    public function verifyWebhook(Request $request): bool
    {
        return true;
    }

    public function parseEvent(Request $request): GatewayEvent
    {
        return GatewayEvent::tryFrom((string) $request->input('event')) ?? GatewayEvent::Unknown;
    }

    public function externalSubscriptionId(Request $request): ?string
    {
        $id = $request->input('external_id');

        return is_string($id) && $id !== '' ? $id : null;
    }
}

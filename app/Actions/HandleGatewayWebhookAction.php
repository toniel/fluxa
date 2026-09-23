<?php

declare(strict_types=1);

namespace App\Actions;

use App\Billing\GatewayEvent;
use App\Billing\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class HandleGatewayWebhookAction
{
    use AsAction;

    /**
     * Terapkan event webhook ke langganan. Berjalan di luar tenancy
     * (central domain), jadi query dibungkus withoutScope dan baris
     * dicocokkan lewat id eksternal + nama gateway.
     *
     * Event tak dikenal atau id tak cocok dicatat dan diabaikan (tetap 200)
     * supaya provider tidak mengulanginya tanpa henti.
     */
    public function handle(PaymentGateway $gateway, Request $request): void
    {
        $event = $gateway->parseEvent($request);

        if ($event === GatewayEvent::Unknown) {
            Log::warning('Billing: event webhook tak dikenal.', ['gateway' => $gateway->name()]);

            return;
        }

        $externalId = $gateway->externalSubscriptionId($request);

        if ($externalId === null) {
            Log::warning('Billing: webhook tanpa id langganan.', ['gateway' => $gateway->name()]);

            return;
        }

        app(TenantContext::class)->withoutScope(function () use ($gateway, $externalId, $event): void {
            $subscription = Subscription::query()
                ->where('gateway', $gateway->name())
                ->where('external_subscription_id', $externalId)
                ->first();

            if (! $subscription instanceof Subscription) {
                Log::warning('Billing: langganan tak cocok.', [
                    'gateway' => $gateway->name(),
                    'external_id' => $externalId,
                ]);

                return;
            }

            match ($event) {
                GatewayEvent::SubscriptionActivated => $subscription->update([
                    'status' => SubscriptionStatus::Active->value,
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                    'cancelled_at' => null,
                ]),
                GatewayEvent::PaymentSucceeded => $subscription->update([
                    'status' => SubscriptionStatus::Active->value,
                    'current_period_end' => now()->addMonth(),
                ]),
                GatewayEvent::PaymentFailed => $subscription->update([
                    'status' => SubscriptionStatus::PastDue->value,
                ]),
                GatewayEvent::SubscriptionStopped => $subscription->update([
                    'status' => SubscriptionStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]),
            };
        });
    }
}

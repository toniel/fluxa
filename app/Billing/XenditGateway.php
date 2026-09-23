<?php

declare(strict_types=1);

namespace App\Billing;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Adapter Xendit (Recurring Payment). Satu-satunya tempat yang mengenal
 * endpoint, format auth, dan nama event Xendit.
 */
final class XenditGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'xendit';
    }

    public function createCustomer(Tenant $tenant, User $owner): string
    {
        $response = Http::withBasicAuth($this->secretKey(), '')
            ->post($this->baseUrl().'/customers', [
                'reference_id' => "tenant-{$tenant->getKey()}",
                'email' => $owner->email,
                'given_names' => $owner->name,
            ])
            ->throw()
            ->json();

        return (string) ($response['id'] ?? throw new GatewayException('Xendit tidak mengembalikan id customer.'));
    }

    public function createRecurring(Plan $plan, string $externalCustomerId, string $returnUrl): GatewayCheckout
    {
        $externalId = "tenant-plan-{$plan->slug}-".Str::lower(Str::random(8));

        $response = Http::withBasicAuth($this->secretKey(), '')
            ->post($this->baseUrl().'/recurring_payments', [
                'external_id' => $externalId,
                'payer_email' => $externalCustomerId,
                'interval' => 'MONTH',
                'interval_count' => 1,
                'amount' => (float) $plan->price,
                'success_redirect_url' => $returnUrl,
            ])
            ->throw()
            ->json();

        $id = $response['id'] ?? throw new GatewayException('Xendit tidak mengembalikan id recurring.');

        return new GatewayCheckout(
            checkoutUrl: (string) ($response['checkout_url'] ?? $response['invoice_url'] ?? $returnUrl),
            externalSubscriptionId: (string) $id,
        );
    }

    public function cancelRecurring(string $externalSubscriptionId): void
    {
        Http::withBasicAuth($this->secretKey(), '')
            ->post($this->baseUrl()."/recurring_payments/{$externalSubscriptionId}/stop")
            ->throw();
    }

    public function verifyWebhook(Request $request): bool
    {
        $token = (string) config('billing.gateways.xendit.callback_token');

        return $token !== '' && hash_equals($token, (string) $request->header('x-callback-token'));
    }

    public function parseEvent(Request $request): GatewayEvent
    {
        return match ((string) $request->input('event')) {
            'recurring.plan.activated' => GatewayEvent::SubscriptionActivated,
            'recurring.cycle.succeeded', 'invoice.paid' => GatewayEvent::PaymentSucceeded,
            'recurring.cycle.failed', 'invoice.expired' => GatewayEvent::PaymentFailed,
            'recurring.plan.stopped' => GatewayEvent::SubscriptionStopped,
            default => GatewayEvent::Unknown,
        };
    }

    public function externalSubscriptionId(Request $request): ?string
    {
        $id = $request->input('recurring_payment_id') ?? $request->input('external_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function secretKey(): string
    {
        $key = (string) config('billing.gateways.xendit.secret_key');

        if ($key === '') {
            throw new GatewayException('XENDIT_SECRET_KEY belum diisi.');
        }

        return $key;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('billing.gateways.xendit.base_url', 'https://api.xendit.co'), '/');
    }
}

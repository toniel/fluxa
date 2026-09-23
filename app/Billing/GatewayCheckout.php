<?php

declare(strict_types=1);

namespace App\Billing;

/**
 * Hasil pembuatan langganan berulang: tautan checkout untuk user plus id
 * eksternal untuk dicocokkan saat webhook datang.
 */
final readonly class GatewayCheckout
{
    public function __construct(
        public string $checkoutUrl,
        public string $externalSubscriptionId,
    ) {}
}

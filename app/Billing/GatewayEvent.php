<?php

declare(strict_types=1);

namespace App\Billing;

/**
 * Kejadian webhook yang sudah dinormalisasi: setiap adapter menerjemahkan
 * event khas providernya ke salah satu nilai ini, sehingga inti billing
 * tidak mengenal nama event provider mana pun.
 */
enum GatewayEvent: string
{
    case SubscriptionActivated = 'subscription.activated';
    case PaymentSucceeded = 'payment.succeeded';
    case PaymentFailed = 'payment.failed';
    case SubscriptionStopped = 'subscription.stopped';
    case Unknown = 'unknown';
}

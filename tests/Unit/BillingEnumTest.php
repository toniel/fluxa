<?php

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;

test('nilai dan label periode serta status langganan konsisten', function () {
    expect(BillingPeriod::values())->toBe(['monthly', 'yearly'])
        ->and(BillingPeriod::Yearly->label())->toBe('tahun')
        ->and(SubscriptionStatus::values())->toBe(['active', 'past_due', 'cancelled', 'expired'])
        ->and(SubscriptionStatus::Active->label())->toBe('Aktif')
        ->and(SubscriptionStatus::PastDue->label())->toBe('Menunggak')
        ->and(SubscriptionStatus::Cancelled->label())->toBe('Dibatalkan')
        ->and(SubscriptionStatus::Expired->label())->toBe('Kedaluwarsa');
});

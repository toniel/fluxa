<?php

use App\Billing\GatewayEvent;
use App\Billing\GatewayException;
use App\Billing\LogGateway;
use App\Billing\PaymentGateway;
use App\Billing\XenditGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, PlanSeeder::class]);
});

test('gateway dipilih dari config dan nama tak dikenal gagal keras', function () {
    expect(app(PaymentGateway::class))->toBeInstanceOf(LogGateway::class);

    config()->set('billing.gateway', 'xendit');

    expect(app(PaymentGateway::class))->toBeInstanceOf(XenditGateway::class);

    config()->set('billing.gateway', 'midtrans');

    try {
        app(PaymentGateway::class);
        $resolved = true;
    } catch (InvalidArgumentException) {
        $resolved = false;
    }

    expect($resolved)->toBeFalse();
});

test('adapter xendit memanggil API dan memetakan event', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $plan = Plan::query()->bySlug('pro-monthly');

    config()->set('billing.gateways.xendit.secret_key', 'test-key');

    Http::fake([
        '*/customers' => Http::response(['id' => 'cus-123'], 200),
        '*/recurring_payments' => Http::response(['id' => 'sub-456', 'invoice_url' => 'https://pay.x/1'], 200),
        '*/recurring_payments/sub-456/stop' => Http::response([], 200),
    ]);

    $gateway = app(XenditGateway::class);

    expect($gateway->name())->toBe('xendit')
        ->and($gateway->createCustomer($tenant, $owner))->toBe('cus-123');

    $checkout = $gateway->createRecurring($plan, 'cus-123', 'https://app/return');

    expect($checkout->externalSubscriptionId)->toBe('sub-456')
        ->and($checkout->checkoutUrl)->toBe('https://pay.x/1');

    $gateway->cancelRecurring('sub-456');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/stop'));

    $request = Request::create('/billing/webhook', 'POST', ['event' => 'invoice.paid']);

    expect($gateway->parseEvent($request))->toBe(GatewayEvent::PaymentSucceeded)
        ->and($gateway->parseEvent(Request::create('/billing/webhook', 'POST', ['event' => 'aneh'])))
        ->toBe(GatewayEvent::Unknown);
});

test('adapter xendit menolak tanpa kunci dan tanpa token valid', function () {
    ['tenant' => $tenant, 'user' => $owner] = memberTenant('keluarga-uji');
    config()->set('billing.gateways.xendit.secret_key', '');
    config()->set('billing.gateways.xendit.callback_token', 'benar');

    $gateway = app(XenditGateway::class);

    try {
        $gateway->createCustomer($tenant, $owner);
        $thrown = false;
    } catch (GatewayException) {
        $thrown = true;
    }

    expect($thrown)->toBeTrue()
        ->and($gateway->verifyWebhook(Request::create('/billing/webhook', 'POST')))
        ->toBeFalse()
        ->and($gateway->verifyWebhook(Request::create('/billing/webhook', 'POST', [], [], [], ['HTTP_X_CALLBACK_TOKEN' => 'benar'])))
        ->toBeTrue();
});

test('webhook tanpa verifikasi dijawab 403', function () {
    config()->set('billing.gateway', 'xendit');
    config()->set('billing.gateways.xendit.callback_token', 'benar');

    $this->postJson('/billing/webhook', ['event' => 'recurring.plan.activated'])
        ->assertForbidden();
});

test('webhook mengubah status langganan yang cocok', function () {
    ['tenant' => $tenant] = memberTenant('keluarga-uji');
    $plan = Plan::query()->bySlug('free');

    $subscription = Subscription::factory()->for($tenant)->create([
        'plan_id' => $plan->getKey(),
        'gateway' => 'log',
        'external_subscription_id' => 'log-sub-1',
        'status' => SubscriptionStatus::Active->value,
    ]);

    $this->postJson('/billing/webhook', [
        'event' => 'payment.failed',
        'external_id' => 'log-sub-1',
    ])->assertOk();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::PastDue);

    $this->postJson('/billing/webhook', [
        'event' => 'payment.succeeded',
        'external_id' => 'log-sub-1',
    ])->assertOk();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Active);

    // Event tak dikenal dan id asing: 200 tapi diabaikan.
    $this->postJson('/billing/webhook', ['event' => 'aneh'])
        ->assertOk();

    $this->postJson('/billing/webhook', [
        'event' => 'payment.failed',
        'external_id' => 'log-sub-asing',
    ])->assertOk();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Active);
});

<?php

namespace App\Providers;

use App\Billing\LogGateway;
use App\Billing\PaymentGateway;
use App\Billing\XenditGateway;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped (bukan singleton) supaya aman di Octane: setiap request
        // mendapat instance TenantContext yang bersih.
        $this->app->scoped(TenantContext::class);

        // Provider billing dipilih dari config; nama tak dikenal gagal keras
        // supaya salah ketik ketahuan saat deploy, bukan saat webhook masuk.
        $this->app->bind(PaymentGateway::class, function (): PaymentGateway {
            return match ((string) config('billing.gateway')) {
                'xendit' => app(XenditGateway::class),
                'log' => app(LogGateway::class),
                default => throw new \InvalidArgumentException('BILLING_GATEWAY tidak dikenal.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::preventLazyLoading(
            ! app()->isProduction(),
        );

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

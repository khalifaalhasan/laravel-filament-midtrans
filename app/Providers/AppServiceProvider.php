<?php

namespace App\Providers;

use App\Payments\MidtransGateway;
use App\Payments\PaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, fn () => new MidtransGateway(
            (string) config('midtrans.server_key'),
            (bool) config('midtrans.is_production'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

namespace Spatie\StripeWebhooks;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StripeWebhooksServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/stripe-webhooks.php' => config_path('stripe-webhooks.php'),
            ], 'config');
        }

        Route::macro('stripeWebhooks', function ($url) {
            return Route::post($url, '\Spatie\StripeWebhooks\StripeWebhooksController');
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/stripe-webhooks.php', 'stripe-webhooks');
    }
}

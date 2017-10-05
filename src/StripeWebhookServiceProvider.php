<?php

namespace Spatie\StripeWebhooks;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StripeWebhookServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/uptime-monitor.php' => config_path('uptime-monitor.php'),
            ], 'config');
        }

        if (! class_exists('CreateStripeWebhookCallsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/create_stripe_webhooks_table.php.stub' => database_path('migrations/'.$timestamp.'_create_stripe_webhooks_table.php'),
            ], 'migrations');
        }

        Route::macro('stripeWebhooks', function ($url) {
            return Route::post($url, 'Spatie\StripeWebhooks\StripeWebhookController');
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/stripe-webhooks.php', 'stripe-webhooks');
    }
}

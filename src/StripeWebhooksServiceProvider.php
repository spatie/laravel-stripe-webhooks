<?php

namespace Spatie\StripeWebhooks;

use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class StripeWebhooksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-stripe-webhooks')
            ->hasConfigFile();

        Route::macro('stripeWebhooks', function ($url) {
            return Route::post($url, '\Spatie\StripeWebhooks\StripeWebhooksController');
        });
    }
}

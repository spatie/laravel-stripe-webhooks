
[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/support-ukraine.svg?t=1" />](https://supportukrainenow.org)

# Handle Stripe Webhooks in a Laravel application

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-stripe-webhooks.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-stripe-webhooks)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/spatie/laravel-stripe-webhooks/run-tests?label=tests)
![Check & fix styling](https://github.com/spatie/laravel-stripe-webhooks/workflows/Check%20&%20fix%20styling/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-stripe-webhooks.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-stripe-webhooks)

[Stripe](https://stripe.com) can notify your application of events using webhooks. This package can help you handle those webhooks. Out of the box it will verify the Stripe signature of all incoming requests. All valid calls will be logged to the database. You can easily define jobs or events that should be dispatched when specific events hit your app.

This package will not handle what should be done after the webhook request has been validated and the right job or event is called. You should still code up any work (eg. regarding payments) yourself.

Before using this package we highly recommend reading [the entire documentation on webhooks over at Stripe](https://stripe.com/docs/webhooks).

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-stripe-webhooks.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-stripe-webhooks)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

### Upgrading

Please see [UPGRADING](UPGRADING.md) for details.

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-stripe-webhooks
```

The service provider will automatically register itself.

You must publish the config file with:
```bash
php artisan vendor:publish --provider="Spatie\StripeWebhooks\StripeWebhooksServiceProvider"
```

This is the contents of the config file that will be published at `config/stripe-webhooks.php`:

```php
return [
    /*
     * Stripe will sign each webhook using a secret. You can find the used secret at the
     * webhook configuration settings: https://dashboard.stripe.com/account/webhooks.
     */
    'signing_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
     * You can define a default job that should be run for all other Stripe event type
     * without a job defined in next configuration.
     * You may leave it empty to store the job in database but without processing it.
     */
    'default_job' => '',

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Stripe event type with the `.` replaced by a `_`.
     *
     * You can find a list of Stripe webhook types here:
     * https://stripe.com/docs/api#event_types.
     */
    'jobs' => [
        // 'source_chargeable' => \App\Jobs\StripeWebhooks\HandleChargeableSource::class,
        // 'charge_failed' => \App\Jobs\StripeWebhooks\HandleFailedCharge::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\WebhookClient\Models\WebhookCall.
     */
    'model' => \Spatie\WebhookClient\Models\WebhookCall::class,

    /**
     * This class determines if the webhook call should be stored and processed.
     */
    'profile' => \Spatie\StripeWebhooks\StripeWebhookProfile::class,

    /*
     * When disabled, the package will not verify if the signature is valid.
     * This can be handy in local environments.
     */
    'verify_signature' => env('STRIPE_SIGNATURE_VERIFY', true),
];
```

In the `signing_secret` key of the config file you should add a valid webhook secret. You can find the secret used at [the webhook configuration settings on the Stripe dashboard](https://dashboard.stripe.com/account/webhooks).

Next, you must publish the migration with:
```bash
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="webhook-client-migrations"
```

After the migration has been published you can create the `webhook_calls` table by running the migrations:

```bash
php artisan migrate
```

Finally, take care of the routing: At [the Stripe dashboard](https://dashboard.stripe.com/account/webhooks) you must configure at what url Stripe webhooks should hit your app. In the routes file of your app you must pass that route to `Route::stripeWebhooks`:

```php
Route::stripeWebhooks('webhook-route-configured-at-the-stripe-dashboard');
```

Behind the scenes this will register a `POST` route to a controller provided by this package. Because Stripe has no way of getting a csrf-token, you must add that route to the `except` array of the `VerifyCsrfToken` middleware:

```php
protected $except = [
    'webhook-route-configured-at-the-stripe-dashboard',
];
```

## Usage

Stripe will send out webhooks for several event types. You can find the [full list of events types](https://stripe.com/docs/api#event_types) in the Stripe documentation.

Stripe will sign all requests hitting the webhook url of your app. This package will automatically verify if the signature is valid. If it is not, the request was probably not sent by Stripe.

Unless something goes terribly wrong, this package will always respond with a `200` to webhook requests. Sending a `200` will prevent Stripe from resending the same event over and over again.
Stripe might occasionally send a duplicate webhook request [more than once](https://stripe.com/docs/webhooks/best-practices#duplicate-events). This package makes sure that each request will only be processed once.
All webhook requests with a valid signature will be logged in the `webhook_calls` table. The table has a `payload` column where the entire payload of the incoming webhook is saved.

If the signature is not valid, the request will not be logged in the `webhook_calls` table but a `Spatie\StripeWebhooks\WebhookFailed` exception will be thrown.
If something goes wrong during the webhook request the thrown exception will be saved in the `exception` column. In that case the controller will send a `500` instead of `200`.

There are two ways this package enables you to handle webhook requests: you can opt to queue a job or listen to the events the package will fire.

### Handling webhook requests using jobs
If you want to do something when a specific event type comes in you can define a job that does the work. Here's an example of such a job:

```php
<?php

namespace App\Jobs\StripeWebhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Models\WebhookCall;

class HandleChargeableSource implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    public function handle()
    {
        // do your work here

        // you can access the payload of the webhook call with `$this->webhookCall->payload`
    }
}
```

We highly recommend that you make this job queueable, because this will minimize the response time of the webhook requests. This allows you to handle more stripe webhook requests and avoid timeouts.

After having created your job you must register it at the `jobs` array in the `stripe-webhooks.php` config file. The key should be the name of [the stripe event type](https://stripe.com/docs/api#event_types) where but with the `.` replaced by `_`. The value should be the fully qualified classname.

```php
// config/stripe-webhooks.php

'jobs' => [
    'source_chargeable' => \App\Jobs\StripeWebhooks\HandleChargeableSource::class,
],
```

In case you want to configure one job as default to process all undefined event, you may set the job at `default_job` in
the `stripe-webhooks.php` config file. The value should be the fully qualified classname.

By default, the configuration is an empty string `''`, which will only store the event in database but without handling.

```php
// config/stripe-webhooks.php
'default_job' => \App\Jobs\StripeWebhooks\HandleOtherEvent::class,
```

### Handling webhook requests using events

Instead of queueing jobs to perform some work when a webhook request comes in, you can opt to listen to the events this package will fire. Whenever a valid request hits your app, the package will fire a `stripe-webhooks::<name-of-the-event>` event.

The payload of the events will be the instance of `WebhookCall` that was created for the incoming request.

Let's take a look at how you can listen for such an event. In the `EventServiceProvider` you can register listeners.

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    'stripe-webhooks::source.chargeable' => [
        App\Listeners\ChargeSource::class,
    ],
];
```

Here's an example of such a listener:

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Models\WebhookCall;

class ChargeSource implements ShouldQueue
{
    public function handle(WebhookCall $webhookCall)
    {
        // do your work here

        // you can access the payload of the webhook call with `$webhookCall->payload`
    }
}
```

We highly recommend that you make the event listener queueable, as this will minimize the response time of the webhook requests. This allows you to handle more Stripe webhook requests and avoid timeouts.

The above example is only one way to handle events in Laravel. To learn the other options, read [the Laravel documentation on handling events](https://laravel.com/docs/5.5/events).

## Advanced usage

### Retry handling a webhook

All incoming webhook requests are written to the database. This is incredibly valuable when something goes wrong while handling a webhook call. You can easily retry processing the webhook call, after you've investigated and fixed the cause of failure, like this:

```php
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\StripeWebhooks\ProcessStripeWebhookJob;

dispatch(new ProcessStripeWebhookJob(WebhookCall::find($id)));
```

### Performing custom logic

You can add some custom logic that should be executed before and/or after the scheduling of the queued job by using your own model. You can do this by specifying your own model in the `model` key of the `stripe-webhooks` config file. The class should extend `Spatie\StripeWebhooks\ProcessStripeWebhookJob`.

Here's an example:

```php
use Spatie\StripeWebhooks\ProcessStripeWebhookJob;

class MyCustomStripeWebhookJob extends ProcessStripeWebhookJob
{
    public function handle()
    {
        // do some custom stuff beforehand

        parent::handle();

        // do some custom stuff afterwards
    }
}
```

### Determine if a request should be processed

You may use your own logic to determine if a request should be processed or not. You can do this by specifying your own profile in the `profile` key of the `stripe-webhooks` config file. The class should implement `Spatie\WebhookClient\WebhookProfile\WebhookProfile`.

In this example we will make sure to only process a request if it wasn't processed before.

```php
use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class StripeWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        return ! WebhookCall::where('payload->id', $request->get('id'))->exists();
    }
}
```

### Handling multiple signing secrets

When using [Stripe Connect](https://stripe.com/connect) you might want to the package to handle multiple endpoints and secrets. Here's how to configurate that behaviour.

If you are using the `Route::stripeWebhooks` macro, you can append the `configKey` as follows:

```php
Route::stripeWebhooks('webhook-url/{configKey}');
```

Alternatively, if you are manually defining the route, you can add `configKey` like so:

```php
Route::post('webhook-url/{configKey}', '\Spatie\StripeWebhooks\StripeWebhooksController');
```

If this route parameter is present the verify middleware will look for the secret using a different config key, by appending the given the parameter value to the default config key. E.g. If Stripe posts to `webhook-url/my-named-secret` you'd add a new config named `signing_secret_my-named-secret`.

Example config for Connect might look like:

```php
// secret for when Stripe posts to webhook-url/account
'signing_secret_account' => 'whsec_abc',
// secret for when Stripe posts to webhook-url/connect
'signing_secret_connect' => 'whsec_123',
```

### Transforming the Webhook payload into a Stripe Object

You can transform the Webhook payload into a Stripe object to assist in accessing its various methods and properties.

To do this, use the `Stripe\Event::constructFrom($payload)` method with the `WebhookCall`'s payload:

```php
use Stripe\Event;

// ...

public function handle(WebhookCall $webhookCall)
{
    /** @var \Stripe\StripeObject|null */
    $stripeObject = Event::constructFrom($webhookCall->payload)->data?->object;
}
```

For example, if you have setup a Stripe webhook for the `invoice.created` event, you can transform the payload into a `StripeInvoice` object:

```php
/** @var \Stripe\StripeInvoice|null */
$stripeInvoice = Event::constructFrom($webhookCall->payload)->data?->object;

// $stripeInvoice->status
// $stripeInvoice->amount_due
// $stripeInvoice->amount_paid
// $stripeInvoice->amount_remaining

foreach ($stripeInvoice->lines as $invoiceLine) {
    // ...
}
```

### About Cashier

[Laravel Cashier](https://laravel.com/docs/5.5/billing#handling-stripe-webhooks) allows you to easily handle Stripe subscriptions. You may install it in the same application together with `laravel-stripe-webhooks`. There are no known conflicts.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about what has changed recently.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

A big thank you to [Sebastiaan Luca](https://twitter.com/sebastiaanluca) who generously shared his Stripe webhook solution that inspired this package.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

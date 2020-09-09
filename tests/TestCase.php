<?php

namespace Spatie\StripeWebhooks\Tests;

use CreateWebhookCallsTable;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\StripeWebhooks\StripeWebhooksServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config(['stripe-webhooks.signing_secret' => 'test_signing_secret']);
    }

    protected function setUpDatabase()
    {
        include_once __DIR__.'/../vendor/spatie/laravel-webhook-client/database/migrations/create_webhook_calls_table.php.stub';

        (new CreateWebhookCallsTable())->up();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            StripeWebhooksServiceProvider::class,
        ];
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct()
            {
            }

            public function report(Exception $e)
            {
            }

            public function render($request, Exception $exception)
            {
                throw $exception;
            }
        });
    }

    protected function determineStripeSignature(array $payload, string $configKey = null): string
    {
        $secret = ($configKey) ?
            config("stripe-webhooks.signing_secret_{$configKey}") :
            config('stripe-webhooks.signing_secret');

        $timestamp = time();

        $timestampedPayload = $timestamp.'.'.json_encode($payload);

        $signature = hash_hmac('sha256', $timestampedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}

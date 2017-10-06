<?php

namespace Spatie\StripeWebhooks\Tests;

use Exception;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\StripeWebhooks\StripeWebhookServiceProvider;

abstract class TestCase extends OrchestraTestCase
{

    public function setUp()
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
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        config(['stripe-webhooks.signing_secret' => 'test_signing_secret']);
    }

    protected function setUpDatabase()
    {
        include_once __DIR__.'/../database/migrations/create_stripe_webhooks_calls_table.php.stub';

        (new \CreateStripeWebhooksTable())->up();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            StripeWebhookServiceProvider::class,
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
    
    protected function determineStripeSignature(array $payload): string
    {
        $secret = config('stripe-webhooks.signing_secret');

        $timestamp = time();

        $timestampedPayload = $timestamp . '.'. json_encode($payload);

        $signature = hash_hmac("sha256", $timestampedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}

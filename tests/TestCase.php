<?php

namespace Spatie\StripeWebhooks\Test;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Exception;
use Spatie\StripeWebhooks\StripeWebhookServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
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
}

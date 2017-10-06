<?php

namespace Spatie\StripeWebhooks\Tests;

use Spatie\StripeWebhooks\StripeWebhookCall;

class DummyJob
{
    /** @var \Spatie\StripeWebhooks\StripeWebhookCall */
    public $stripeWebhookCall;

    public function __construct(StripeWebhookCall $stripeWebhookCall)
    {
        $this->stripeWebhookCall = $stripeWebhookCall;
    }

    public function handle()
    {

    }
}
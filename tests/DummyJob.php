<?php

namespace Spatie\StripeWebhooks\Tests;

use Spatie\WebhookClient\Models\WebhookCall;

class DummyJob
{
    public function __construct(
        public WebhookCall $webhookCall
    ) {
    }

    public function handle()
    {
        cache()->put('dummyjob', $this->webhookCall);
    }
}

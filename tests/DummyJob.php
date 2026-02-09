<?php

namespace Spatie\StripeWebhooks\Tests;

use Illuminate\Bus\Queueable;
use Spatie\WebhookClient\Models\WebhookCall;

class DummyJob
{
    use Queueable;

    public function __construct(
        public WebhookCall $webhookCall
    ) {
    }

    public function handle()
    {
        cache()->put('dummyjob', $this->webhookCall);
    }
}

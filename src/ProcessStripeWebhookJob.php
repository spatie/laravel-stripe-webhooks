<?php

namespace Spatie\StripeWebhooks;

use Spatie\WebhookClient\ProcessWebhookJob;
use Spatie\StripeWebhooks\Exceptions\WebhookFailed;

class ProcessStripeWebhookJob extends ProcessWebhookJob
{
    public function handle()
    {
        if (! isset($this->webhookCall->payload['type']) || $this->webhookCall->payload['type'] === '') {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        event("stripe-webhooks::{$this->webhookCall->payload['type']}", $this->webhookCall);

        $jobClass = $this->determineJobClass($this->webhookCall->payload['type']);

        if ($jobClass === '') {
            return;
        }

        if (! class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this->webhookCall);
        }

        dispatch(new $jobClass($this->webhookCall));
    }

    protected function determineJobClass(string $eventType): string
    {
        $jobConfigKey = str_replace('.', '_', $eventType);

        return config("stripe-webhooks.jobs.{$jobConfigKey}", '');
    }
}

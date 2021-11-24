<?php

namespace Spatie\StripeWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;

class StripeWebhooksController
{
    public function __invoke(Request $request, string $configKey = null)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'stripe',
            'signing_secret' => ($configKey) ?
                config('stripe-webhooks.signing_secret_'.$configKey) :
                config('stripe-webhooks.signing_secret'),
            'signature_header_name' => 'Stripe-Signature',
            'signature_validator' => StripeSignatureValidator::class,
            'webhook_profile' => config('stripe-webhooks.profile'),
            'webhook_model' => config('stripe-webhooks.model'),
            'process_webhook_job' => ProcessStripeWebhookJob::class,
        ]);

        return (new WebhookProcessor($request, $webhookConfig))->process();
    }
}

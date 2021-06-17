<?php

namespace Spatie\StripeWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
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
            'webhook_profile' => StripeWebhookProfile::class,
            'webhook_model' => WebhookCall::class,
            'process_webhook_job' => config('stripe-webhooks.model'),
        ]);

        (new WebhookProcessor($request, $webhookConfig))->process();

        return response()->json(['message' => 'ok']);
    }
}

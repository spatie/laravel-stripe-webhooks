<?php

namespace Spatie\StripeWebhooks;

use Exception;
use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
use Stripe\Webhook;

class StripeSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if (! config('stripe-webhooks.verify_signature')) {
            return true;
        }

        $signature = $request->header('Stripe-Signature');
        $secret = $config->signingSecret;

        try {
            Webhook::constructEvent($request->getContent(), $signature, $secret);
        } catch (Exception) {
            return false;
        }

        return true;
    }
}

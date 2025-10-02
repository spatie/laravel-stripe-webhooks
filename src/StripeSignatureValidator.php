<?php

namespace Spatie\StripeWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if (! config('stripe-webhooks.verify_signature')) {
            return true;
        }

        $signature = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        $potentialSecrets = is_array($config->signingSecret)
            ? $config->signingSecret
            : [$config->signingSecret];

        $secrets = array_filter($potentialSecrets);

        if (empty($secrets)) {
            return false;
        }

        foreach ($secrets as $secret) {
            try {
                Webhook::constructEvent($payload, $signature, $secret);

                return true;
            } catch (SignatureVerificationException $exception) {
                continue;
            }
        }

        return false;
    }
}

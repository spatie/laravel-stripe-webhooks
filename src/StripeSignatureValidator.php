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

        // Ensure secrets are in an array for iteration
        $potentialSecrets = is_array($config->signingSecret)
            ? $config->signingSecret
            : [$config->signingSecret];

        // Filter out any empty secrets, which can happen with `explode(',', '')`
        $secrets = array_filter($potentialSecrets);

        if (empty($secrets)) {
            // No secrets configured, so fail validation.
            return false;
        }

        foreach ($secrets as $secret) {
            try {
                Webhook::constructEvent($payload, $signature, $secret);

                // If we reach this point, the signature is valid for this secret.
                return true;
            } catch (SignatureVerificationException $exception) {
                // This secret was invalid, continue to the next one in the loop.
                continue;
            }
        }

        // If the loop completes without returning true, no valid secret was found.
        return false;
    }
}

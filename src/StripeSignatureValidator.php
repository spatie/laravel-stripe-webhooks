<?php

namespace Spatie\StripeWebhooks;

use Exception;
use Stripe\Webhook;
use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;

class StripeSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header('Stripe-Signature');
        $secret = $config->signingSecret;

        try {
            Webhook::constructEvent($request->getContent(), $signature, $secret);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }
}

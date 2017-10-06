<?php

namespace Spatie\StripeWebhooks\Middlewares;

use Closure;
use Stripe\Webhook;
use UnexpectedValueException;
use Stripe\Error\SignatureVerification;
use Spatie\StripeWebhooks\Exceptions\WebhookFailed;

class VerifySignature
{
    public function handle($request, Closure $next)
    {
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            throw WebhookFailed::signatureMissing();
        }

        if (! $this->isValid($signature, $request->getContent())) {
            throw WebhookFailed::invalidSignature($signature);
        }

        return $next($request);
    }

    protected function isValid(string $signature, string $payload): bool
    {
        $secret = config('stripe-webhooks.signing_secret');

        if (empty($secret)) {
            throw WebhookFailed::signingSecretNotSet();
        }

        try {
            Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException | SignatureVerification $exception) {
            return false;
        }

        return true;
    }
}

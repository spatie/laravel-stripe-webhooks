<?php

namespace Spatie\StripeWebhooks\Middlewares;

use Closure;
use Spatie\StripeWebhooks\Exceptions\WebhookFailed;
use Stripe\Webhook;
use UnexpectedValueException;
use Stripe\Error\SignatureVerification;

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
        $secret = config('services.stripe.webhook_signing_secret');

        if (empty($secret)) {
            throw WebhookFailed::signingSecretNotSet();
        };

        try {
            Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException | SignatureVerification $exception) {
            return false;
        }

        return true;
    }
}

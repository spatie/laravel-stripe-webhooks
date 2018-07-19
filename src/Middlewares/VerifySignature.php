<?php

namespace Spatie\StripeWebhooks\Middlewares;

use Closure;
use Exception;
use Stripe\Webhook;
use Spatie\StripeWebhooks\Exceptions\WebhookFailed;

class VerifySignature
{
    public function handle($request, Closure $next)
    {
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            throw WebhookFailed::missingSignature();
        }

        if (! $this->isValid($signature, $request->getContent(), $request->route('configKey'))) {
            throw WebhookFailed::invalidSignature($signature);
        }

        return $next($request);
    }

    protected function isValid(string $signature, string $payload, string $configKey = null): bool
    {
        $secret = ($configKey) ?
            config('stripe-webhooks.signing_secret_' . $configKey) :
            config('stripe-webhooks.signing_secret');

        if (empty($secret)) {
            throw WebhookFailed::signingSecretNotSet();
        }

        try {
            Webhook::constructEvent($payload, $signature, $secret);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }
}

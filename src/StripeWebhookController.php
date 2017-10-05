<?php

namespace Spatie\StripeWebhooks;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\StripeWebhooks\Middlewares\VerifySignature;

class StripeWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware(VerifySignature::class);
    }

    public function __invoke(Request $request)
    {
        $eventPayload = $request->input();

        $eventType = $eventPayload['type'];

        $modelClass = config('stripe-webhooks.model');

        $stripeWebhookCall = $modelClass::create([
            'type' => $eventType,
            'payload' => $eventPayload,
        ]);

        try {
            $stripeWebhookCall->process();
        } catch (Exception $exception) {
            $stripeWebhookCall->saveException($exception);

            throw $exception;
        }
    }
}

<?php

namespace Spatie\StripeWebhooks;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\StripeWebhooks\Middlewares\VerifySignature;

class StripeWebhooksController extends Controller
{
    public function __construct()
    {
        $this->middleware(VerifySignature::class);
    }

    public function __invoke(Request $request)
    {
        $eventPayload = $request->input();

        $modelClass = config('stripe-webhooks.model');

        $stripeWebhookCall = $modelClass::create([
            'type' =>  $eventPayload['type'] ?? '',
            'payload' => $eventPayload,
        ]);

        try {
            $stripeWebhookCall->process();
        } catch (Exception $exception) {
            $stripeWebhookCall->saveException($exception);

            throw $exception;
        }

        return response()->json(['message' => 'ok']);
    }
}

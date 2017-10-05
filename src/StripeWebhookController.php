<?php

namespace App\Http\Controllers\Front;

use Exception;
use Illuminate\Http\Request;
use App\Models\StripeWebhookCall;
use App\Http\Controllers\Controller;
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

        $stripeWebhookCall = StripeWebhookCall::create([
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

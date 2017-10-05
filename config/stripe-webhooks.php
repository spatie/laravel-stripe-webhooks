<?php

return [

    /*
     * Stripe will sign webhooks using a secret. You can find the secret used at the webhook
     * configuration settings: https://dashboard.stripe.com/account/webhooks
     */
    'signing_secret' => '',

    /*
     * Here you can define the job that should be run when a certain webhook hits your .
     * application. The key is name of stripe event type with the `.` replace by `.`
     *
     * You can find a list of stripe webhook type here:
     * https://stripe.com/docs/api#event_types
     */
    'jobs' => [
        // 'source_chargeable' => \App\Jobs\StripeWebhooks\HandleChargeableSource::class,
        // 'charge_failed' => \App\Jobs\StripeWebhooks\HandleFailedCharge::class,
    ],

    /*
     * The class name of the model to be used.
     */
    'model' => App\Models\StripeWebhookCall::class,
];

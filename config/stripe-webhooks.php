<?php

return [

    /*
     * Available job types : https://stripe.com/docs/api#event_types
     */
    'jobs' => [
        // 'source_chargeable' => \App\Jobs\StripeWebhooks\SourceChargeable::class,
        // 'charge_failed' => \App\Jobs\StripeWebhooks\ChargedFailed::class,
    ],

    'model' => \App\Models\StripeWebhookCall::class,
];

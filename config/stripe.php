<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Pro Plan Price ID
    |--------------------------------------------------------------------------
    |
    | The Stripe Price id for the Pro subscription. Find it in your Stripe
    | Dashboard under Products. The default Pro plan is $99/month per
    | docs/data-model.md §7.2 and PRD §9.
    |
    | If this is null, /dashboard/billing/checkout will return a 500 with a
    | clear error.
    |
    */

    'pro_price_id' => env('STRIPE_PRO_PRICE_ID'),
];

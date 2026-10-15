<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe publishable key
    |--------------------------------------------------------------------------
    |
    | The public Stripe key used by the client. Read by Laravel Cashier via
    | `config('cashier.key')`. The default value here is a placeholder so
    | the application boots in dev environments without real keys; replace
    | with `pk_test_…` from the Stripe dashboard when integrating for real.
    |
    */

    'key' => env('STRIPE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Stripe secret key
    |--------------------------------------------------------------------------
    |
    | Server-side secret. Used by Cashier to talk to the Stripe API. Replace
    | the placeholder with `sk_test_…` from the Stripe dashboard.
    |
    */

    'secret' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Pro Plan Price ID
    |--------------------------------------------------------------------------
    |
    | The Stripe Price id for the Pro subscription. Find it in your Stripe
    | Dashboard under Products. The default Pro plan is $99/month per
    | docs/data-model.md §7.2 and PRD §9.
    |
    | If this is null, /dashboard/billing/checkout will show a friendly
    | "billing is not configured" message instead of a 500.
    |
    */

    'pro_price_id' => env('STRIPE_PRO_PRICE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Used to verify the signature of incoming webhook requests. Configure
    | via the Stripe Dashboard or `stripe listen` for local development.
    |
    */

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
];

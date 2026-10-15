<?php

/*
 * English message catalog.
 *
 * Used by both the dashboard layout and the public collection form.
 * Critical English strings that appear in feature tests
 * (e.g. "Testimonial inbox", "Current plan", "Submit testimonial",
 * "You're officially awesome!", "Testimonials over time") are kept as
 * the English source here so they remain present in the default locale.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */
    'nav' => [
        'dashboard'     => 'Dashboard',
        'spaces'        => 'Spaces',
        'inbox'         => 'Inbox',
        'wall_of_love'  => 'Wall of Love',
        'billing'       => 'Billing',
        'logout'        => 'Log out',
    ],

    /*
    |--------------------------------------------------------------------------
    | Topbar / common UI
    |--------------------------------------------------------------------------
    */
    'common' => [
        'welcome_back' => 'Welcome back',
        'toggle_theme' => 'Toggle theme',
        'language'     => 'Language',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'title'                 => 'Dashboard',
        'total_reviews'         => 'Total Reviews',
        'submitters'            => 'Submitters',
        'active_spaces'         => 'Active Spaces',
        'current_plan'          => 'Current Plan',
        'recent_testimonials'   => 'Recent testimonials',
        'quick_links'           => 'Quick links',
        'chart_title'           => 'Testimonials over time',
        'chart_7d'              => '7 Days',
        'chart_30d'             => '30 Days',
        'chart_all'             => 'All Time',
        'chart_empty'           => 'No testimonials yet. Once submissions start arriving, you\'ll see growth here.',
        'recent_empty'          => 'No testimonials yet. Share a public collection URL to start collecting.',
        'free_banner'           => 'You\'re on the Free plan. Upgrade for more spaces and higher limits.',
        'upgrade'               => 'Upgrade to Pro',
        'create_space'          => 'Create a space',
        'open_inbox'            => 'Open inbox',
        'manage_spaces'         => 'Manage spaces',
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'current_plan'           => 'Current plan',
        'free'                   => 'Free',
        'pro'                    => 'Pro',
        'per_month'              => '/month',
        'spaces_label'           => 'Spaces',
        'testimonials_per_space' => 'Testimonials per space',
        'total_testimonials'     => 'Total testimonials',
        'up_to_spaces'           => 'Up to :n spaces',
        'testimonials_count'     => ':n testimonials per space',
        'testimonials_limit'     => 'Up to :n testimonials per space',
        'priority_support'       => 'Priority support',
        'public_form'            => 'Public collection form',
        'inbox_widget'           => 'Inbox & embed widget',
        'current_plan_marker'    => 'Current plan',
        'cancel_simulated'       => 'Cancel simulated subscription',
        'upgrade_button'         => 'Upgrade to Pro — $:price/month',
        'upgrade_button_sim'     => 'Upgrade to Pro — $:price/month (simulated)',
        'stripe_not_configured'  => 'Stripe billing is not yet configured for this environment.',
        'stripe_set_price_id'    => 'Set STRIPE_PRO_PRICE_ID in your .env and reload to enable checkout.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public collection form
    |--------------------------------------------------------------------------
    */
    'public' => [
        'rating_label'             => 'Your rating',
        'sharing_consent'          => 'Sharing consent',
        'sharing_consent_question' => 'May we share your testimonial publicly and on social networks?',
        'consent_yes'              => 'Yes, you may share it',
        'consent_no'               => 'No, do not share publicly',
        'submit_testimonial'       => 'Submit testimonial',
        'submit_emoji'             => 'Submit testimonial',
        'inactive_heading'         => 'This space is not accepting submissions right now.',
        'inactive_body'            => 'Please check back later or contact the owner.',
        'confirmation_heading'     => 'You\'re officially awesome!',
        'confirmation_body'        => 'Your words made our day. Thank you for sharing.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Wall of Love (embed)
    |--------------------------------------------------------------------------
    */
    'wall' => [
        'empty' => 'No testimonials yet — be the first to share!',
        'verified' => 'Verified',
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbox
    |--------------------------------------------------------------------------
    */
    'inbox' => [
        'title'        => 'Testimonial inbox',
        'filter_space' => 'Space',
        'filter_consent' => 'Consent',
        'filter_status' => 'Status',
        'filter_search' => 'Search',
        'filter_button' => 'Filter',
        'filter_reset'  => 'Reset',
        'empty'         => 'No testimonials match these filters.',
        'total_found'   => ':n testimonials found',
        'consent_granted'  => 'Granted',
        'consent_declined' => 'Declined',
        'consent_not_asked' => 'Not asked',
        'btn_favorite'    => 'Favorite',
        'btn_unfavorite'  => 'Unfavorite',
        'btn_wol'         => 'Wall of Love',
        'btn_unwol'       => 'Remove WoL',
        'btn_hide'        => 'Hide',
        'btn_unhide'      => 'Unhide',
        'btn_delete'      => 'Delete',
        'flag_favorite'   => 'Favorite',
        'flag_wall'       => 'On Wall of Love',
        'flag_hidden'     => 'Hidden',
    ],

    /*
    |--------------------------------------------------------------------------
    | Spaces
    |--------------------------------------------------------------------------
    */
    'spaces' => [
        'title'        => 'Spaces',
        'create'       => 'Create a space',
        'empty'        => 'You don\'t have any spaces yet.',
        'empty_create' => 'Create one to start collecting testimonials.',
        'header_title' => 'Title',
        'header_theme' => 'Theme',
        'header_count' => 'Testimonials',
        'header_url'   => 'Public URL',
        'header_actions' => 'Actions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status / messages
    |--------------------------------------------------------------------------
    */
    'status' => [
        'welcome_pro'         => 'Welcome to Pro! (simulated checkout)',
        'already_pro'         => 'You\'re already on Pro.',
        'subscription_cancelled' => 'Subscription cancelled. You are back on the Free plan.',
        'no_active_sub'       => 'No active subscription to cancel.',
    ],
];
<?php

/*
 * Bangla (bn) message catalog.
 *
 * Mirrors the keys in lang/en/messages.php. When a string is missing
 * here Laravel falls back to the English catalog automatically.
 */

return [
    'nav' => [
        'dashboard'     => 'ড্যাশবোর্ড',
        'spaces'        => 'স্পেসসমূহ',
        'inbox'         => 'ইনবক্স',
        'wall_of_love'  => 'ওয়াল অব লাভ',
        'billing'       => 'বিলিং',
        'logout'        => 'লগ আউট',
    ],

    'common' => [
        'welcome_back' => 'স্বাগতম',
        'toggle_theme' => 'থিম পরিবর্তন',
        'language'     => 'ভাষা',
    ],

    'dashboard' => [
        'title'                 => 'ড্যাশবোর্ড',
        'total_reviews'         => 'মোট রিভিউ',
        'submitters'            => 'জমা প্রদানকারী',
        'active_spaces'         => 'সক্রিয় স্পেস',
        'current_plan'          => 'বর্তমান প্ল্যান',
        'recent_testimonials'   => 'সাম্প্রতিক টেস্টিমোনিয়াল',
        'quick_links'           => 'দ্রুত লিঙ্ক',
        'chart_title'           => 'সময়ের সাথে টেস্টিমোনিয়াল',
        'chart_7d'              => '৭ দিন',
        'chart_30d'             => '৩০ দিন',
        'chart_all'             => 'সব সময়',
        'chart_empty'           => 'এখনও কোনো টেস্টিমোনিয়াল নেই। জমা শুরু হলে এখানে প্রবৃদ্ধি দেখতে পাবেন।',
        'recent_empty'          => 'এখনও কোনো টেস্টিমোনিয়াল নেই। সংগ্রহ শুরু করতে একটি পাবলিক URL শেয়ার করুন।',
        'free_banner'           => 'আপনি ফ্রি প্ল্যানে আছেন। আরও স্পেস এবং উচ্চ সীমার জন্য আপগ্রেড করুন।',
        'upgrade'               => 'প্রো তে আপগ্রেড করুন',
        'create_space'          => 'নতুন স্পেস তৈরি করুন',
        'open_inbox'            => 'ইনবক্স খুলুন',
        'manage_spaces'         => 'স্পেস পরিচালনা',
    ],

    'billing' => [
        'current_plan'           => 'বর্তমান প্ল্যান',
        'free'                   => 'ফ্রি',
        'pro'                    => 'প্রো',
        'per_month'              => '/মাস',
        'spaces_label'           => 'স্পেস',
        'testimonials_per_space' => 'প্রতি স্পেসে টেস্টিমোনিয়াল',
        'total_testimonials'     => 'মোট টেস্টিমোনিয়াল',
        'up_to_spaces'           => 'সর্বোচ্চ :n টি স্পেস',
        'testimonials_count'     => 'প্রতি স্পেসে :n টি টেস্টিমোনিয়াল',
        'testimonials_limit'     => 'প্রতি স্পেসে সর্বোচ্চ :n টি টেস্টিমোনিয়াল',
        'priority_support'       => 'অগ্রাধিকার সাপোর্ট',
        'public_form'            => 'পাবলিক সংগ্রহ ফর্ম',
        'inbox_widget'           => 'ইনবক্স ও এম্বেড উইজেট',
        'current_plan_marker'    => 'বর্তমান প্ল্যান',
        'cancel_simulated'       => 'সিমুলেটেড সাবস্ক্রিপশন বাতিল',
        'upgrade_button'         => 'প্রো তে আপগ্রেড — $:price/মাস',
        'upgrade_button_sim'     => 'প্রো তে আপগ্রেড — $:price/মাস (সিমুলেটেড)',
        'stripe_not_configured'  => 'এই এনভায়রনমেন্টে Stripe বিলিং এখনও কনফিগার করা হয়নি।',
        'stripe_set_price_id'    => 'চেকআউট চালু করতে আপনার .env ফাইলে STRIPE_PRO_PRICE_ID সেট করুন।',
    ],

    'public' => [
        'rating_label'             => 'আপনার রেটিং',
        'sharing_consent'          => 'শেয়ারিং সম্মতি',
        'sharing_consent_question' => 'আমরা কি আপনার টেস্টিমোনিয়াল পাবলিক এবং সোশ্যাল মিডিয়ায় শেয়ার করতে পারি?',
        'consent_yes'              => 'হ্যাঁ, আপনি শেয়ার করতে পারেন',
        'consent_no'               => 'না, পাবলিকভাবে শেয়ার করবেন না',
        'submit_testimonial'       => 'টেস্টিমোনিয়াল জমা দিন',
        'submit_emoji'             => 'টেস্টিমোনিয়াল জমা দিন',
        'inactive_heading'         => 'এই স্পেস এখন কোনো জমা নিচ্ছে না।',
        'inactive_body'            => 'অনুগ্রহ করে পরে আবার দেখুন অথবা মালিকের সাথে যোগাযোগ করুন।',
        'confirmation_heading'     => 'আপনি আনুষ্ঠানিকভাবে অসাধারণ!',
        'confirmation_body'        => 'আপনার কথা আমাদের দিনটি সুন্দর করেছে। শেয়ার করার জন্য ধন্যবাদ।',
    ],

    'wall' => [
        'empty' => 'এখনও কোনো টেস্টিমোনিয়াল নেই — প্রথম শেয়ারকারী হোন!',
        'verified' => 'যাচাইকৃত',
    ],

    'inbox' => [
        'title'        => 'টেস্টিমোনিয়াল ইনবক্স',
        'filter_space' => 'স্পেস',
        'filter_consent' => 'সম্মতি',
        'filter_status' => 'অবস্থা',
        'filter_search' => 'অনুসন্ধান',
        'filter_button' => 'ফিল্টার',
        'filter_reset'  => 'রিসেট',
        'empty'         => 'এই ফিল্টারগুলোর সাথে কোনো টেস্টিমোনিয়াল মেলে না।',
        'total_found'   => ':n টি টেস্টিমোনিয়াল পাওয়া গেছে',
        'consent_granted'  => 'অনুমোদিত',
        'consent_declined' => 'অস্বীকৃত',
        'consent_not_asked' => 'জিজ্ঞাসা করা হয়নি',
        'btn_favorite'    => 'প্রিয়',
        'btn_unfavorite'  => 'অপ্রিয়',
        'btn_wol'         => 'ওয়াল অব লাভ',
        'btn_unwol'       => 'ওয়াল অব লাভ থেকে সরান',
        'btn_hide'        => 'লুকান',
        'btn_unhide'      => 'প্রকাশ',
        'btn_delete'      => 'মুছে ফেলুন',
        'flag_favorite'   => 'প্রিয়',
        'flag_wall'       => 'ওয়াল অব লাভ এ',
        'flag_hidden'     => 'লুকানো',
    ],

    'spaces' => [
        'title'        => 'স্পেসসমূহ',
        'create'       => 'নতুন স্পেস তৈরি করুন',
        'empty'        => 'আপনার এখনও কোনো স্পেস নেই।',
        'empty_create' => 'টেস্টিমোনিয়াল সংগ্রহ শুরু করতে একটি তৈরি করুন।',
        'header_title' => 'শিরোনাম',
        'header_theme' => 'থিম',
        'header_count' => 'টেস্টিমোনিয়াল',
        'header_url'   => 'পাবলিক URL',
        'header_actions' => 'কর্ম',
    ],

    'status' => [
        'welcome_pro'         => 'প্রো তে স্বাগতম! (সিমুলেটেড চেকআউট)',
        'already_pro'         => 'আপনি ইতিমধ্যে প্রো তে আছেন।',
        'subscription_cancelled' => 'সাবস্ক্রিপশন বাতিল হয়েছে। আপনি আবার ফ্রি প্ল্যানে ফিরে এসেছেন।',
        'no_active_sub'       => 'বাতিল করার জন্য কোনো সক্রিয় সাবস্ক্রিপশন নেই।',
    ],
];
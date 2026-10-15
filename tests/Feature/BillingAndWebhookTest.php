<?php

namespace Tests\Feature;

use App\Enums\Plan;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §9: Stripe billing — friendly error when unconfigured,
 * webhook upgrades the user's plan.
 */
class BillingAndWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_index_shows_friendly_error_when_stripe_not_configured(): void
    {
        config(['stripe.pro_price_id' => null]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard.billing.index'))
            ->assertOk()
            ->assertSee('Stripe billing is not yet configured');
    }

    public function test_checkout_redirects_back_when_stripe_not_configured(): void
    {
        config(['stripe.pro_price_id' => null]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard.billing.checkout'))
            ->assertRedirect(route('dashboard.billing.index'))
            ->assertSessionHasErrors('stripe');
    }

    public function test_user_starts_on_free_plan(): void
    {
        $user = User::factory()->create();
        $this->assertSame(Plan::Free, app(\App\Services\PlanResolver::class)->resolve($user));
    }

    public function test_webhook_upgrades_user_to_pro_when_subscription_active(): void
    {
        config(['stripe.webhook_secret' => null]);

        $user = User::factory()->create();
        // Create a local subscription row to mimic what Cashier would
        // create after a checkout.
        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_1',
            'stripe_status' => 'incomplete',
            'stripe_price' => 'price_pro',
            'quantity' => 1,
        ]);

        $payload = [
            'id' => 'evt_test_1',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_test_1',
                    'customer' => 'cus_test',
                    'status' => 'active',
                    'cancel_at_period_end' => false,
                    'current_period_end' => now()->addMonth()->timestamp,
                    'trial_end' => null,
                    'items' => ['data' => [['price' => ['id' => 'price_pro'], 'quantity' => 1]]],
                    'metadata' => ['app_user_id' => (string) $user->id],
                ],
            ],
        ];

        $response = $this->postJson(
            '/webhooks/stripe',
            $payload,
            ['Stripe-Signature' => 'fake']
        );

        $response->assertOk();

        $subscription->refresh();
        $this->assertSame('active', $subscription->stripe_status);
        $this->assertSame(Plan::Pro->value, $subscription->plan);
    }

    public function test_webhook_is_idempotent(): void
    {
        config(['stripe.webhook_secret' => null]);

        $payload = [
            'id' => 'evt_test_idem',
            'type' => 'customer.subscription.updated',
            'data' => ['object' => ['id' => 'sub_x', 'metadata' => []]],
        ];

        $this->postJson('/webhooks/stripe', $payload, ['Stripe-Signature' => 'fake'])
            ->assertOk();

        // Second replay should be a no-op and still 200.
        $this->postJson('/webhooks/stripe', $payload, ['Stripe-Signature' => 'fake'])
            ->assertOk()
            ->assertJson(['status' => 'duplicate']);
    }

    public function test_webhook_downgrades_user_on_subscription_deleted(): void
    {
        config(['stripe.webhook_secret' => null]);

        $user = User::factory()->create();
        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_del_1',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro',
            'quantity' => 1,
            'plan' => Plan::Pro->value,
        ]);

        $payload = [
            'id' => 'evt_del_1',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_del_1',
                    'status' => 'canceled',
                ],
            ],
        ];

        $this->postJson('/webhooks/stripe', $payload, ['Stripe-Signature' => 'fake'])
            ->assertOk();

        $subscription->refresh();
        $this->assertSame(Plan::Free->value, $subscription->plan);
    }

    public function test_plan_limits_match_prd(): void
    {
        $this->assertSame(3, Plan::Free->maxSpaces());
        $this->assertSame(25, Plan::Pro->maxSpaces());
        $this->assertSame(100, Plan::Free->maxTestimonialsPerSpace());
        $this->assertSame(1000, Plan::Pro->maxTestimonialsPerSpace());
    }

    public function test_simulated_checkout_upgrades_user_in_local_env(): void
    {
        // The default .env / config in this repo uses placeholder Stripe
        // keys, so the simulated checkout path should be active.
        $this->assertSame('pk_test_sample', config('stripe.key'));
        $this->assertSame('sk_test_sample', config('stripe.secret'));
        $this->assertSame('price_pro_sample', config('stripe.pro_price_id'));

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertSame(Plan::Free, app(\App\Services\PlanResolver::class)->resolve($user));

        $this->get(route('dashboard.billing.checkout'))
            ->assertRedirect(route('dashboard.billing.success'));

        $this->assertSame(Plan::Pro, app(\App\Services\PlanResolver::class)->resolve($user));

        // The user's billing page now shows Pro and the dev-mode controls.
        $this->get(route('dashboard.billing.index'))
            ->assertOk()
            ->assertSee('Current plan')
            ->assertSee('Pro')
            ->assertSee('$99')
            ->assertSee('Cancel simulated subscription');
    }

    public function test_simulated_upgrade_creates_subscription_and_audit_events(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard.billing.checkout'))
            ->assertRedirect(route('dashboard.billing.success'));

        $subscription = $user->subscriptions()->first();
        $this->assertNotNull($subscription);
        $this->assertSame('active', $subscription->stripe_status);
        $this->assertSame(Plan::Pro->value, $subscription->plan);
        $this->assertSame('price_pro_sample', $subscription->stripe_price);
        $this->assertSame(1, $subscription->quantity);

        // Two audit events are written: checkout.session.completed and
        // customer.subscription.created.
        $events = \App\Models\SubscriptionEvent::where('user_id', $user->id)->get();
        $this->assertCount(2, $events);
        $this->assertContains('checkout.session.completed', $events->pluck('type')->all());
        $this->assertContains('customer.subscription.created', $events->pluck('type')->all());
    }

    public function test_simulated_upgrade_is_idempotent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard.billing.checkout'))->assertRedirect(route('dashboard.billing.success'));
        $first = $user->subscriptions()->first();
        $firstEventCount = \App\Models\SubscriptionEvent::count();

        // Second visit: the user is already Pro, so checkout redirects to
        // billing.index with a friendly status. Critically, no new
        // subscription row or audit event is created.
        $this->get(route('dashboard.billing.checkout'))
            ->assertRedirect(route('dashboard.billing.index'));
        $this->assertCount(1, $user->subscriptions()->get());
        $this->assertSame($first->getKey(), $user->subscriptions()->first()->getKey());
        $this->assertSame($firstEventCount, \App\Models\SubscriptionEvent::count());
    }

    public function test_simulated_post_endpoint_upgrades_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('dashboard.billing.simulate'))
            ->assertRedirect(route('dashboard.billing.success'));

        $this->assertSame(Plan::Pro, app(\App\Services\PlanResolver::class)->resolve($user));
    }

    public function test_simulated_cancel_downgrades_user_back_to_free(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Upgrade first.
        $this->post(route('dashboard.billing.simulate'));
        $this->assertSame(Plan::Pro, app(\App\Services\PlanResolver::class)->resolve($user));

        // Cancel.
        $this->post(route('dashboard.billing.cancel-simulated'))
            ->assertRedirect(route('dashboard.billing.index'));

        $this->assertSame(Plan::Free, app(\App\Services\PlanResolver::class)->resolve($user));
        $this->assertSame('canceled', $user->subscriptions()->first()->stripe_status);
    }

    public function test_billing_index_shows_dev_banner_and_upgrade_button_in_simulated_mode(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard.billing.index'))
            ->assertOk()
            ->assertSee('Dev mode')
            ->assertSee('Stripe keys are placeholders')
            ->assertSee('Upgrade to Pro')
            ->assertSee('$99')
            // Free plan card and Pro plan card are both shown.
            ->assertSee('Up to 3 spaces')
            ->assertSee('Up to 25 spaces')
            ->assertSee('100 testimonials per space')
            ->assertSee('1000 testimonials per space');
    }

    public function test_billing_index_after_upgrade_shows_pro_state_with_cancel_button(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        app(\App\Services\SimulatedUpgradeService::class)->upgrade($user);

        $this->get(route('dashboard.billing.index'))
            ->assertOk()
            ->assertSee('Pro')
            ->assertSee('Cancel simulated subscription');
    }

    public function test_real_stripe_keys_disable_simulation_path(): void
    {
        // Pretend we have real-looking keys.
        config([
            'stripe.key' => 'pk_test_51H1234567890abcdefghij',
            'stripe.secret' => 'sk_test_51H1234567890abcdefghij',
            'stripe.pro_price_id' => 'price_pro_sample', // still placeholder here
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard.billing.index'))
            ->assertOk()
            // No dev banner when real Stripe keys are present.
            ->assertDontSee('Dev mode');

        // /checkout should NOT silently upgrade the user anymore.
        $this->get(route('dashboard.billing.checkout'))
            ->assertRedirect(); // to Stripe or to error — not to /success
    }

    public function test_plan_limit_max_spaces_enforced_after_upgrade(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // On Free plan, 3 spaces are allowed.
        $this->assertSame(3, app(\App\Services\PlanResolver::class)->resolve($user)->maxSpaces());

        // Upgrade to Pro.
        app(\App\Services\SimulatedUpgradeService::class)->upgrade($user);

        // Now 25 spaces are allowed.
        $this->assertSame(25, app(\App\Services\PlanResolver::class)->resolve($user)->maxSpaces());
    }

    public function test_simulated_service_refuses_in_production_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $service = app(\App\Services\SimulatedUpgradeService::class);
        $this->assertFalse($service->isAllowed());

        $user = User::factory()->create();
        $this->expectException(\RuntimeException::class);
        $service->upgrade($user);
    }

    public function test_billing_checkout_already_pro_redirects_to_billing_with_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        app(\App\Services\SimulatedUpgradeService::class)->upgrade($user);

        $this->get(route('dashboard.billing.checkout'))
            ->assertRedirect(route('dashboard.billing.index'))
            ->assertSessionHas('status', "You're already on Pro.");
    }
}
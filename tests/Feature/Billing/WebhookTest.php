<?php

namespace Tests\Feature\Billing;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Stripe\Subscription as StripeSubscription;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cashier.webhook.secret' => null]);
    }

    public function test_handles_checkout_session_completed(): void
    {
        $team = Team::factory()->withStripeCustomer('cus_checkout_test')->create();

        $payload = $this->webhookPayload('checkout.session.completed', [
            'id' => 'cs_test_completed',
            'object' => 'checkout.session',
            'mode' => 'subscription',
            'customer' => $team->stripe_id,
            'subscription' => 'sub_checkout_test',
            'status' => 'complete',
        ]);

        $this->postJson('/webhook/stripe', $payload)
            ->assertOk()
            ->assertSee('Webhook Handled');
    }

    public function test_handles_customer_subscription_updated(): void
    {
        $team = Team::factory()->withStripeCustomer('cus_update_test')->create();

        Subscription::factory()->create([
            'team_id' => $team->id,
            'stripe_id' => 'sub_update_test',
            'stripe_status' => StripeSubscription::STATUS_ACTIVE,
            'stripe_price' => 'price_test_pro',
        ]);

        $payload = $this->webhookPayload('customer.subscription.updated', [
            'id' => 'sub_update_test',
            'object' => 'subscription',
            'customer' => $team->stripe_id,
            'status' => StripeSubscription::STATUS_PAST_DUE,
            'cancel_at_period_end' => false,
            'items' => [
                'data' => [
                    [
                        'id' => 'si_update_test',
                        'price' => [
                            'id' => 'price_test_pro',
                            'product' => 'prod_test_pro',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ]);

        $this->postJson('/webhook/stripe', $payload)->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'team_id' => $team->id,
            'stripe_id' => 'sub_update_test',
            'stripe_status' => StripeSubscription::STATUS_PAST_DUE,
        ]);
    }

    public function test_handles_customer_subscription_deleted(): void
    {
        $team = Team::factory()->withStripeCustomer('cus_delete_test')->create();

        $subscription = Subscription::factory()->create([
            'team_id' => $team->id,
            'stripe_id' => 'sub_delete_test',
            'stripe_status' => StripeSubscription::STATUS_ACTIVE,
        ]);

        $payload = $this->webhookPayload('customer.subscription.deleted', [
            'id' => 'sub_delete_test',
            'object' => 'subscription',
            'customer' => $team->stripe_id,
            'status' => StripeSubscription::STATUS_CANCELED,
        ]);

        $this->postJson('/webhook/stripe', $payload)->assertOk();

        $subscription->refresh();

        $this->assertTrue($subscription->canceled());
        $this->assertNotNull($subscription->ends_at);
    }

    /**
     * @param  array<string, mixed>  $object
     * @return array<string, mixed>
     */
    protected function webhookPayload(string $type, array $object): array
    {
        return [
            'id' => 'evt_test_'.uniqid(),
            'object' => 'event',
            'type' => $type,
            'data' => [
                'object' => $object,
            ],
        ];
    }
}

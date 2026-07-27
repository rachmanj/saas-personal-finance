<?php

namespace Tests\Feature\Billing;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Http\Controllers\Billing\CheckoutController;
use App\Http\Controllers\Billing\PortalController;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Mockery;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function createUserWithTeam(): User
    {
        $user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($user);

        return $user->fresh();
    }

    public function test_guest_cannot_access_billing_settings(): void
    {
        $this->get('/settings/billing')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_billing_settings(): void
    {
        $user = $this->createUserWithTeam();

        $this->actingAs($user)
            ->get('/settings/billing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Billing')
                ->has('subscription')
                ->where('subscription.tier', 'free')
            );
    }

    public function test_authenticated_user_on_pro_plan_sees_pro_tier(): void
    {
        $user = $this->createUserWithTeam();
        $team = $user->currentTeam;

        Subscription::factory()->create([
            'team_id' => $team->id,
            'stripe_status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/settings/billing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('subscription.tier', 'pro')
                ->where('subscription.subscribed', true)
            );
    }

    public function test_guest_cannot_start_checkout(): void
    {
        $this->post('/billing/checkout')->assertRedirect('/login');
    }

    public function test_authenticated_user_is_redirected_to_stripe_checkout(): void
    {
        $user = $this->createUserWithTeam();

        $mock = Mockery::mock(CheckoutController::class)->makePartial();
        $mock->shouldReceive('createCheckout')
            ->once()
            ->with(Mockery::type(Team::class))
            ->andReturn(redirect('https://checkout.stripe.com/c/pay/cs_test_123'));

        $this->instance(CheckoutController::class, $mock);

        $this->actingAs($user)
            ->post('/billing/checkout')
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');
    }

    public function test_guest_cannot_access_customer_portal(): void
    {
        $this->get('/billing/portal')->assertRedirect('/login');
    }

    public function test_subscribed_user_is_redirected_to_stripe_customer_portal(): void
    {
        $user = $this->createUserWithTeam();
        $team = $user->currentTeam;
        $team->forceFill(['stripe_id' => 'cus_test_123'])->save();

        Subscription::factory()->create([
            'team_id' => $team->id,
            'stripe_status' => 'active',
        ]);

        $mock = Mockery::mock(PortalController::class)->makePartial();
        $mock->shouldReceive('createPortalRedirect')
            ->once()
            ->with(Mockery::type(Team::class))
            ->andReturn(redirect('https://billing.stripe.com/session/test_portal'));

        $this->instance(PortalController::class, $mock);

        $this->actingAs($user)
            ->get('/billing/portal')
            ->assertRedirect('https://billing.stripe.com/session/test_portal');
    }

    public function test_user_without_subscription_cannot_access_customer_portal(): void
    {
        $user = $this->createUserWithTeam();

        $this->actingAs($user)
            ->get('/billing/portal')
            ->assertRedirect(route('settings.billing'))
            ->assertSessionHas('error');
    }
}

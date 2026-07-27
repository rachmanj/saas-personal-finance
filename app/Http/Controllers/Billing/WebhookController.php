<?php

namespace App\Http\Controllers\Billing;

use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends CashierWebhookController
{
    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        if ($team = $this->getUserByStripeId($payload['data']['object']['customer'] ?? null)) {
            if (($payload['data']['object']['mode'] ?? null) === 'subscription'
                && ! empty($payload['data']['object']['subscription'])) {
                $team->subscriptions()->firstOrCreate(
                    ['stripe_id' => $payload['data']['object']['subscription']],
                    [
                        'type' => 'default',
                        'stripe_status' => 'active',
                        'stripe_price' => config('billing.stripe_price_pro'),
                    ]
                );
            }
        }

        return $this->successMethod();
    }
}

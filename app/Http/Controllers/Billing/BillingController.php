<?php

namespace App\Http\Controllers\Billing;

use App\Enums\SubscriptionTier;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $subscription = $team->subscription('default');
        $subscribed = $team->subscribed('default');

        return Inertia::render('Settings/Billing', [
            'subscription' => [
                'tier' => $subscribed ? SubscriptionTier::Pro->value : SubscriptionTier::Free->value,
                'subscribed' => $subscribed,
                'onGracePeriod' => $subscription?->onGracePeriod() ?? false,
                'endsAt' => $subscription?->ends_at?->toIso8601String(),
                'hasStripeCustomer' => $team->hasStripeId(),
            ],
            'plans' => config('billing.plans'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $this->createCheckout($request->user()->currentTeam);
    }

    public function createCheckout(Team $team): RedirectResponse
    {
        $price = config('billing.stripe_price_pro');

        return $team
            ->newSubscription('default', $price)
            ->checkout([
                'success_url' => route('settings.billing').'?checkout=success',
                'cancel_url' => route('settings.billing').'?checkout=cancelled',
            ]);
    }
}

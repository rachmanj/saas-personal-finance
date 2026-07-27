<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        if (! $team->subscribed('default')) {
            return redirect()
                ->route('settings.billing')
                ->with('error', 'You need an active subscription to manage billing.');
        }

        return $this->createPortalRedirect($team);
    }

    public function createPortalRedirect(Team $team): RedirectResponse
    {
        return $team->redirectToBillingPortal(route('settings.billing'));
    }
}

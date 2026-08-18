<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function store(StoreRegisteredUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        app(CreatePersonalTeamAction::class)->execute($user);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended('/dashboard');
    }
}

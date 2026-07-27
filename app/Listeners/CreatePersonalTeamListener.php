<?php

namespace App\Listeners;

use App\Actions\Teams\CreatePersonalTeamAction;
use Illuminate\Auth\Events\Registered;

class CreatePersonalTeamListener
{
    public function __construct(private CreatePersonalTeamAction $action) {}

    public function handle(Registered $event): void
    {
        $this->action->execute($event->user);
    }
}

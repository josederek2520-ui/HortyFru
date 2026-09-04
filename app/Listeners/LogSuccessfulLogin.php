<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        activity(ActivityLogName::Authentication->value)
            ->event(ActivityEvent::LoginSucceeded->value)
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->withProperties([
                'guard' => $event->guard,
                'remember' => $event->remember,
            ])
            ->log('Inicio de sesión exitoso');
    }
}

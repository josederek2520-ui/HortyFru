<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;

class LogPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        activity(ActivityLogName::Authentication->value)
            ->event(ActivityEvent::PasswordReset->value)
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->log('Contraseña restablecida');
    }
}

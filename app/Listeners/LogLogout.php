<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\User;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        activity(ActivityLogName::Authentication->value)
            ->event(ActivityEvent::Logout->value)
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->withProperty('guard', $event->guard)
            ->log('Cierre de sesión');
    }
}

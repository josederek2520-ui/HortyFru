<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $submittedEmail = $event->credentials[Fortify::username()] ?? null;
        $email = is_string($submittedEmail) ? Str::lower(trim($submittedEmail)) : null;
        $user = $email === null
            ? null
            : User::query()->select(['id', 'email', 'activo_usuario'])->where('email', $email)->first();

        $eventName = $user !== null && ! $user->activo_usuario
            ? ActivityEvent::LoginBlocked
            : ActivityEvent::LoginFailed;
        $description = $eventName === ActivityEvent::LoginBlocked
            ? 'Intento de acceso con una cuenta inactiva'
            : 'Intento de inicio de sesión fallido';

        $logger = activity(ActivityLogName::Authentication->value)
            ->event($eventName->value)
            ->causedByAnonymous()
            ->withProperties(array_filter([
                'email' => $email,
                'guard' => $event->guard,
            ], fn (mixed $value): bool => $value !== null));

        if ($user !== null) {
            $logger->performedOn($user);
        }

        $logger->log($description);
    }
}

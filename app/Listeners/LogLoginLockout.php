<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class LogLoginLockout
{
    public function handle(Lockout $event): void
    {
        $submittedEmail = $event->request->input(Fortify::username());
        $email = is_string($submittedEmail) ? Str::lower(trim($submittedEmail)) : null;
        $user = $email === null
            ? null
            : User::query()->select(['id', 'email'])->where('email', $email)->first();

        $logger = activity(ActivityLogName::Authentication->value)
            ->event(ActivityEvent::LoginBlocked->value)
            ->causedByAnonymous()
            ->withProperties(array_filter([
                'email' => $email,
                'reason' => 'rate_limit',
            ], fn (mixed $value): bool => $value !== null));

        if ($user !== null) {
            $logger->performedOn($user);
        }

        $logger->log('Inicio de sesión bloqueado por demasiados intentos');
    }
}

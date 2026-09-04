<?php

namespace App\Actions\Users;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\User;

class ChangeUserStatusAction
{
    public function __invoke(User $user): User
    {
        $previousStatus = $user->activo_usuario;
        $newStatus = ! $previousStatus;

        $user->disableLogging();
        $user->update(['activo_usuario' => $newStatus]);
        $user->enableLogging();

        activity(ActivityLogName::Users->value)
            ->event(ActivityEvent::StatusChanged->value)
            ->performedOn($user)
            ->withProperties([
                'old' => ['activo_usuario' => $previousStatus],
                'attributes' => ['activo_usuario' => $newStatus],
            ])
            ->log($newStatus ? 'Cuenta de usuario activada' : 'Cuenta de usuario desactivada');

        return $user->refresh();
    }
}

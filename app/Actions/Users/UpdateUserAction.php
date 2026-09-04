<?php

namespace App\Actions\Users;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UpdateUserAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(User $user, array $data): User
    {
        $roleId = (int) $data['roleId'];
        $employeeId = (int) $data['employeeId'];
        unset($data['roleId']);
        unset($data['employeeId']);
        $passwordWasChanged = array_key_exists('password', $data);

        return DB::transaction(function () use ($user, $data, $roleId, $employeeId, $passwordWasChanged): User {
            $currentEmployee = $user->empleado()->lockForUpdate()->first();
            $previousRoleNames = $user->roles()->pluck('name')->sort()->values()->all();

            if ($currentEmployee !== null && $currentEmployee->id !== $employeeId) {
                throw ValidationException::withMessages([
                    'form.employeeId' => 'La cuenta ya pertenece a otro empleado.',
                ]);
            }

            if ($currentEmployee === null) {
                $empleado = Empleado::query()->lockForUpdate()->findOrFail($employeeId);

                if ($empleado->user_id !== null) {
                    throw ValidationException::withMessages([
                        'form.employeeId' => 'El empleado seleccionado ya tiene una cuenta asociada.',
                    ]);
                }

                $empleado->update(['user_id' => $user->id]);
            }

            $user->update($data);
            $role = Role::findById($roleId, 'web');
            $user->syncRoles([$role]);

            if ($previousRoleNames !== [$role->name]) {
                activity(ActivityLogName::Users->value)
                    ->event(ActivityEvent::RoleAssigned->value)
                    ->performedOn($user)
                    ->withProperties([
                        'old' => ['roles' => $previousRoleNames],
                        'attributes' => ['roles' => [$role->name]],
                    ])
                    ->log('Rol de la cuenta actualizado');
            }

            if ($passwordWasChanged) {
                activity(ActivityLogName::Users->value)
                    ->event(ActivityEvent::PasswordChanged->value)
                    ->performedOn($user)
                    ->log('Contraseña de la cuenta actualizada por un administrador');
            }

            return $user->refresh()->load([
                'roles:id,name',
                'empleado:id,user_id,nombre_empleado,apellido_empleado',
            ]);
        });
    }
}

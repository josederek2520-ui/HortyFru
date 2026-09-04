<?php

namespace App\Actions\Users;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreateUserAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): User
    {
        unset($data['password_confirmation']);
        $roleId = (int) $data['roleId'];
        $employeeId = (int) $data['employeeId'];
        unset($data['roleId']);
        unset($data['employeeId']);

        return DB::transaction(function () use ($data, $roleId, $employeeId): User {
            $empleado = Empleado::query()->lockForUpdate()->findOrFail($employeeId);

            if ($empleado->user_id !== null) {
                throw ValidationException::withMessages([
                    'form.employeeId' => 'El empleado seleccionado ya tiene una cuenta asociada.',
                ]);
            }

            if (! $empleado->activo_empleado) {
                throw ValidationException::withMessages([
                    'form.employeeId' => 'No puedes crear una cuenta para un empleado retirado.',
                ]);
            }

            $user = User::query()->create($data);
            $role = Role::findById($roleId, 'web');
            $user->syncRoles([$role]);
            $empleado->update(['user_id' => $user->id]);

            activity(ActivityLogName::Users->value)
                ->event(ActivityEvent::RoleAssigned->value)
                ->performedOn($user)
                ->withProperties([
                    'attributes' => ['role' => $role->name],
                ])
                ->log('Rol asignado a la cuenta');

            return $user->load([
                'roles:id,name',
                'empleado:id,user_id,nombre_empleado,apellido_empleado',
            ]);
        });
    }
}

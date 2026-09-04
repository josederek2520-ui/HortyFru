<?php

namespace App\Livewire\Forms;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;
use Spatie\Permission\Models\Role;

class UserForm extends Form
{
    public ?int $employeeId = null;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $activo_usuario = true;

    public ?int $roleId = null;

    /**
     * @return array<string, mixed>
     */
    public function validateForCreate(): array
    {
        $this->normalize();

        return $this->validate($this->rules());
    }

    /**
     * @return array<string, mixed>
     */
    public function validateForUpdate(User $user): array
    {
        $this->normalize();

        $validated = $this->validate($this->rules($user));

        if ($validated['password'] === '') {
            unset($validated['password']);
        }

        unset($validated['password_confirmation']);

        return $validated;
    }

    public function fillFrom(User $user): void
    {
        $this->employeeId = $user->empleado?->id;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->activo_usuario = $user->activo_usuario;
        $this->roleId = $user->roles->first()?->id;
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?User $user = null): array
    {
        return [
            'employeeId' => [
                'required',
                'integer',
                Rule::exists(Empleado::class, 'id')->where(function ($query) use ($user): void {
                    $query->where(function ($query) use ($user): void {
                        $query->where(function ($query): void {
                            $query->whereNull('user_id')->where('activo_empleado', true);
                        })
                            ->when($user !== null, fn ($query) => $query->orWhere('user_id', $user->id));
                    });
                }),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc',
                'max:255',
                Rule::unique(User::class)->ignore($user),
            ],
            'password' => [
                $user === null ? 'required' : 'nullable',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'password_confirmation' => [$user === null ? 'required' : 'nullable', 'string'],
            'activo_usuario' => ['required', 'boolean'],
            'roleId' => [
                'required',
                'integer',
                Rule::exists(Role::class, 'id')->where('guard_name', 'web'),
            ],
        ];
    }

    private function normalize(): void
    {
        $this->email = Str::lower(trim($this->email));
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'employeeId.required' => 'Selecciona el empleado que tendrá la cuenta.',
            'employeeId.exists' => 'El empleado seleccionado ya tiene otra cuenta o no está disponible.',
            'email.required' => 'Ingresa el correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'Ingresa una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password_confirmation.required' => 'Confirma la contraseña.',
            'roleId.required' => 'Selecciona un rol para el usuario.',
            'roleId.exists' => 'El rol seleccionado ya no está disponible.',
        ];
    }
}

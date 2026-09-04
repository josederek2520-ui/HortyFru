<?php

namespace Tests\Feature;

use App\Actions\Roles\CreateRoleAction;
use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\PermissionName;
use App\Livewire\Panel\ActivityLogs\Index;
use App\Models\Activity;
use App\Models\Empleado;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ActivityLogManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_activity_log(): void
    {
        $this->get(route('panel.activity.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_activity_log(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('panel.activity.index'))->assertForbidden();
        Livewire::actingAs($user)->test(Index::class)->assertForbidden();
    }

    public function test_user_with_view_permission_sees_history_but_cannot_open_details(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionName::ActivityView->value);
        $activity = activity(ActivityLogName::System->value)
            ->event(ActivityEvent::Updated->value)
            ->log('Configuración actualizada');

        $this->actingAs($user)
            ->get(route('panel.activity.index'))
            ->assertOk()
            ->assertSee('Configuración actualizada')
            ->assertDontSee('Ver detalle');

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openDetail', $activity->id)
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('export')
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('exportPdf')
            ->assertForbidden();
    }

    public function test_employee_changes_are_logged_with_actor_and_without_unrelated_fields(): void
    {
        $administrator = $this->createSuperAdministrator();
        $this->actingAs($administrator);

        $employee = Empleado::factory()->create([
            'nombre_empleado' => 'Ana',
            'apellido_empleado' => 'Pérez',
            'ci_empleado' => '8456321',
            'cargo_empleado' => 'Vendedora',
        ]);

        $activity = Activity::query()
            ->where('log_name', ActivityLogName::Employees->value)
            ->where('event', ActivityEvent::Created->value)
            ->where('subject_id', $employee->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($administrator->id, $activity->causer_id);
        $this->assertSame('Ana', $activity->getExtraProperty('attributes.nombre_empleado'));
        $this->assertSame('Vendedora', $activity->getExtraProperty('attributes.cargo_empleado'));
        $this->assertNull($activity->getExtraProperty('attributes.created_at'));
    }

    public function test_user_activity_never_stores_passwords_or_tokens(): void
    {
        $administrator = $this->createSuperAdministrator();
        $this->actingAs($administrator);

        $user = User::factory()->create([
            'email' => 'seguro@example.com',
            'password' => 'ClaveSegura123',
            'remember_token' => 'token-privado',
        ]);

        $activity = Activity::query()
            ->where('log_name', ActivityLogName::Users->value)
            ->where('event', ActivityEvent::Created->value)
            ->where('subject_id', $user->id)
            ->latest('id')
            ->firstOrFail();
        $serializedProperties = $activity->properties->toJson();

        $this->assertStringNotContainsString('password', $serializedProperties);
        $this->assertStringNotContainsString('ClaveSegura123', $serializedProperties);
        $this->assertStringNotContainsString('remember_token', $serializedProperties);
        $this->assertStringNotContainsString('token-privado', $serializedProperties);
    }

    public function test_successful_login_is_logged_with_user_and_request_context(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);

        $this->withHeader('User-Agent', 'Navegador de prueba')
            ->withServerVariables(['REMOTE_ADDR' => '192.168.10.25'])
            ->post(route('login.store'), [
                'email' => 'ana@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('panel.inicio'));

        $activity = Activity::query()
            ->where('event', ActivityEvent::LoginSucceeded->value)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($user->id, $activity->causer_id);
        $this->assertSame($user->id, $activity->subject_id);
        $this->assertSame('192.168.10.25', $activity->properties->get('ip_address'));
        $this->assertSame('Navegador de prueba', $activity->properties->get('user_agent'));
    }

    public function test_failed_login_is_logged_without_storing_submitted_password(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->post(route('login.store'), [
            'email' => 'ana@example.com',
            'password' => 'NoGuardarEstaClave123',
        ])->assertSessionHasErrors('email');

        $activity = Activity::query()
            ->where('event', ActivityEvent::LoginFailed->value)
            ->latest('id')
            ->firstOrFail();
        $serializedProperties = $activity->properties->toJson();

        $this->assertSame('ana@example.com', $activity->properties->get('email'));
        $this->assertStringNotContainsString('password', $serializedProperties);
        $this->assertStringNotContainsString('NoGuardarEstaClave123', $serializedProperties);
    }

    public function test_inactive_account_login_is_recorded_as_blocked(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@example.com',
            'activo_usuario' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => 'inactivo@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $activity = Activity::query()
            ->where('event', ActivityEvent::LoginBlocked->value)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($user->id, $activity->subject_id);
        $this->assertSame('Intento de acceso con una cuenta inactiva', $activity->description);
    }

    public function test_rate_limited_login_is_recorded_without_credentials(): void
    {
        User::factory()->create(['email' => 'limitado@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => 'limitado@example.com',
                'password' => 'ClaveIncorrecta123',
            ]);
        }

        $this->post(route('login.store'), [
            'email' => 'limitado@example.com',
            'password' => 'ClaveIncorrecta123',
        ])->assertTooManyRequests();

        $activity = Activity::query()
            ->where('event', ActivityEvent::LoginBlocked->value)
            ->where('description', 'Inicio de sesión bloqueado por demasiados intentos')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('rate_limit', $activity->properties->get('reason'));
        $this->assertStringNotContainsString('ClaveIncorrecta123', $activity->properties->toJson());
    }

    public function test_logout_is_logged_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('inicio'));

        $activity = Activity::query()
            ->where('event', ActivityEvent::Logout->value)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($user->id, $activity->causer_id);
        $this->assertSame($user->id, $activity->subject_id);
    }

    public function test_role_creation_logs_selected_permissions(): void
    {
        $administrator = $this->createSuperAdministrator();
        $this->actingAs($administrator);
        $permission = Permission::findByName(PermissionName::UsersView->value, 'web');

        $role = app(CreateRoleAction::class)([
            'name' => 'Auditor',
            'permissions' => [$permission->id],
        ]);

        $activity = Activity::query()
            ->where('log_name', ActivityLogName::Roles->value)
            ->where('event', ActivityEvent::Created->value)
            ->where('subject_id', $role->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($administrator->id, $activity->causer_id);
        $this->assertSame(['usuarios.ver'], $activity->getExtraProperty('attributes.permissions'));
    }

    public function test_super_administrator_can_open_details_and_export_history(): void
    {
        $administrator = $this->createSuperAdministrator();
        $this->travelTo('2026-08-28 10:15:30');
        $activity = activity(ActivityLogName::System->value)
            ->event(ActivityEvent::Updated->value)
            ->log('Parámetro actualizado');

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openDetail', $activity->id)
            ->assertSet('showDetailModal', true)
            ->assertSee('Parámetro actualizado')
            ->call('export')
            ->assertFileDownloaded('registro-actividad-28-08-2026-061530.csv')
            ->call('exportPdf')
            ->assertFileDownloaded('registro-actividad-28-08-2026-061530.pdf');
    }

    public function test_activity_time_is_rendered_in_bolivia_timezone(): void
    {
        $administrator = $this->createSuperAdministrator();
        $this->travelTo('2026-08-28 10:19:32');
        activity(ActivityLogName::System->value)
            ->event(ActivityEvent::Updated->value)
            ->log('Hora local comprobada');

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->assertSee('28/08/2026')
            ->assertSee('06:19:32')
            ->assertDontSee('10:19:32');
    }

    public function test_activity_history_presents_technical_information_in_spanish(): void
    {
        $administrator = $this->createSuperAdministrator();
        $employee = Empleado::factory()->create();
        request()->setMethod('POST');
        request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/153.0.0.0');
        $employeeActivity = activity(ActivityLogName::Employees->value)
            ->event(ActivityEvent::Created->value)
            ->performedOn($employee)
            ->withProperties([
                'attributes' => [
                    'user_id' => null,
                    'ci_empleado' => '12345634',
                    'activo_empleado' => true,
                    'fecha_ingreso_empleado' => '2026-08-12T00:00:00.000000Z',
                ],
                'route' => 'default-livewire.update',
                'method' => 'POST',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/153.0.0.0',
            ])
            ->log('Empleado registrado');
        $userActivity = activity(ActivityLogName::Authentication->value)
            ->event(ActivityEvent::LoginSucceeded->value)
            ->performedOn($administrator)
            ->log('Inicio de sesión exitoso');

        $component = Livewire::actingAs($administrator)
            ->test(Index::class)
            ->assertSee('Todos los módulos')
            ->assertDontSee('Las fechas se muestran en hora de Bolivia')
            ->call('openDetail', $employeeActivity->id)
            ->assertSee('Usuario asociado')
            ->assertSee('Carnet de identidad')
            ->assertSee('Estado laboral')
            ->assertSee('Activo')
            ->assertSee('Fecha de ingreso')
            ->assertSee('12/08/2026')
            ->assertSee('Actualización de la interfaz')
            ->assertSee('Envío de información')
            ->assertSee('Google Chrome en computadora con Windows')
            ->assertDontSee('User Id')
            ->assertDontSee('default-livewire.update')
            ->assertDontSee('2026-08-12T00:00:00.000000Z');

        $component
            ->call('closeDetail')
            ->call('openDetail', $userActivity->id)
            ->assertSee('Usuario #'.$administrator->id)
            ->assertDontSee('User #'.$administrator->id);
    }

    public function test_date_filter_uses_bolivia_day_boundaries(): void
    {
        $administrator = $this->createSuperAdministrator();
        $this->travelTo('2026-08-28 03:30:00');
        activity(ActivityLogName::System->value)
            ->event(ActivityEvent::Updated->value)
            ->log('Actividad del día anterior');
        $this->travelTo('2026-08-28 04:30:00');
        activity(ActivityLogName::System->value)
            ->event(ActivityEvent::Updated->value)
            ->log('Actividad del día seleccionado');

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->set('dateFrom', '2026-08-28')
            ->set('dateTo', '2026-08-28')
            ->assertSee('Actividad del día seleccionado')
            ->assertDontSee('Actividad del día anterior');
    }

    public function test_activity_detail_escapes_stored_content(): void
    {
        $administrator = $this->createSuperAdministrator();
        $activity = activity(ActivityLogName::System->value)
            ->event(ActivityEvent::Updated->value)
            ->withProperties([
                'attributes' => ['detalle' => '<script>alert("xss")</script>'],
            ])
            ->log('Dato de auditoría');

        Livewire::actingAs($administrator)
            ->test(Index::class)
            ->call('openDetail', $activity->id)
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee('<script>alert("xss")</script>', escape: false);
    }

    private function createSuperAdministrator(): User
    {
        $administrator = User::factory()->create();
        $this->seed(RoleAndPermissionSeeder::class);

        return $administrator->refresh();
    }
}

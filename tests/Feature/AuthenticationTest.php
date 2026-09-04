<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_renders(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertViewIs('panel.auth.signin')
            ->assertSee('Iniciar sesión')
            ->assertSee('Correo electrónico')
            ->assertSee('Contraseña')
            ->assertSee('Mantener mi sesión iniciada')
            ->assertSee('HortyFru')
            ->assertDontSee('Sign In')
            ->assertDontSee('TailAdmin');
    }

    public function test_registration_page_is_not_available(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_registration_request_is_not_available(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nuevo usuario',
            'email' => 'nuevo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'nuevo@example.com']);
    }

    public function test_user_authenticates_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('panel.inicio'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->ultimo_acceso_usuario);
    }

    public function test_invalid_password_does_not_authenticate_user(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
        ]);
        $this->assertGuest();
    }

    public function test_login_validation_messages_are_displayed_in_spanish(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'El campo correo electrónico es obligatorio.',
                'password' => 'El campo contraseña es obligatorio.',
            ]);
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create(['activo_usuario' => false]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull($user->fresh()->ultimo_acceso_usuario);
    }

    public function test_inactive_authenticated_user_is_logged_out_of_panel(): void
    {
        $user = User::factory()->create(['activo_usuario' => false]);

        $response = $this
            ->actingAs($user)
            ->get(route('panel.inicio'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_non_string_email_is_rejected_without_authenticating(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => ['unexpected'],
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_redirects_to_intended_panel_page(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->withSession(['url.intended' => route('panel.profile')])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertRedirect(route('panel.profile'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_logs_out(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(route('inicio'));
        $this->assertGuest();
    }

    public function test_unauthenticated_user_is_redirected_from_panel_to_login(): void
    {
        $response = $this->get(route('panel.inicio'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authenticated_user_can_open_panel(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('panel.inicio'));

        $response->assertOk();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ]);
        }

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response->assertTooManyRequests();
        $response->assertSee('Demasiados intentos. Espera un minuto antes de volver a intentarlo.');
        $this->assertGuest();
    }
}

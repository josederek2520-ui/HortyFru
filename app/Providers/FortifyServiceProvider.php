<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::loginView(fn (): View => view('panel.auth.signin'));

        Fortify::authenticateUsing(function (Request $request): ?User {
            $email = $request->input(Fortify::username());
            $password = $request->input('password');

            if (! is_string($email) || ! is_string($password)) {
                return null;
            }

            $user = User::query()->where('email', Str::lower($email))->first();

            if ($user === null || ! $user->activo_usuario || ! Hash::check($password, $user->password)) {
                return null;
            }

            $user->forceFill(['ultimo_acceso_usuario' => now()])->save();

            return $user;
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $username = $request->input(Fortify::username());
            $normalizedUsername = is_string($username) ? Str::lower($username) : '';
            $throttleKey = Str::transliterate($normalizedUsername.'|'.$request->ip());

            return Limit::perMinute(5)
                ->by($throttleKey)
                ->response(function (Request $request, array $headers): Response {
                    event(new Lockout($request));

                    return response('Demasiados intentos. Espera un minuto antes de volver a intentarlo.', Response::HTTP_TOO_MANY_REQUESTS, $headers);
                });
        });
    }
}

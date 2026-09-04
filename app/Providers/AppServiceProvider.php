<?php

namespace App\Providers;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as BladeView;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
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
        Gate::policy(Role::class, RolePolicy::class);

        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole(RoleName::SuperAdministrator->value)
                && PermissionName::tryFrom($ability) !== null
                    ? true
                    : null;
        });

        View::composer('components.panel.header.user-dropdown', function (BladeView $view): void {
            $view->with('currentUser', auth()->user()?->loadMissing(
                'empleado:id,user_id,nombre_empleado,apellido_empleado',
            ));
        });
    }
}

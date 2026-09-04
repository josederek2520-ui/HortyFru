---
paths:
  - 'app/**,database/seeders/**,resources/views/**'
---

# Views

## Roles and permissions are code-managed with Spatie
Use spatie/laravel-permission pivots; never add rol_id to users. Permission names live in App\Enums\PermissionName and are created idempotently by RoleAndPermissionSeeder. Users receive one role through syncRoles. Superadministrador is protected from UI update/delete and is synchronized with every declared permission.

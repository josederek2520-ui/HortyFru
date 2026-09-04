---
paths:
  - 'app/{Models,Actions,Livewire,Policies}/**/*.php'
---

# Models Actions Livewire Policies

## Empleado es la fuente de identidad del personal
Los datos personales y laborales viven en empleados; la cuenta users es opcional y empleados.user_id es UNIQUE nullable. Para cuentas vinculadas use User::display_name, que prioriza Empleado::nombre_completo; users.name solo es respaldo temporal para cuentas antiguas sin empleado. Retirar un empleado bloquea su cuenta asociada, pero reincorporarlo no reactiva automáticamente el acceso.

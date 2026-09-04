---
paths:
  - 'app/{Models,Actions,Listeners,Livewire,Policies}/**/*.php'
---

# Models Actions Listeners Livewire Policies

## Auditoría central con Spatie Activitylog
Toda auditoría vive en activity_log. Los modelos auditables usan LogsActivity con una lista explícita de campos, logOnlyDirty y dontSubmitEmptyLogs; nunca registrar password, password_confirmation, remember_token, tokens ni credenciales completas. Use eventos manuales para autenticación, cambios de estado y pivotes de roles/permisos, evitando duplicar el evento automático. Cada módulo futuro añade su log_name/evento a los enums y protege consulta/detalle/exportación con permisos.

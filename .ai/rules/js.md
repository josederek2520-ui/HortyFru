---
paths:
  - 'app/Livewire/Panel/**,resources/views/{layouts/panel,components/panel/ui,livewire/panel}/**,resources/js/panel.js'
---

# Js

## Toasts globales para resultados de acciones
Las confirmaciones y errores breves del panel se muestran mediante el toast global del layout durante 3 segundos. Los componentes Livewire usan InteractsWithToasts y emiten toast con título, mensaje y tipo (success, info, warning o error); no agregue franjas de alerta locales. Los controladores con redirección pueden usar session('toast') o las claves success/error.

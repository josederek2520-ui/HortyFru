---
paths:
  - 'app/Livewire/Panel/Branches/**,resources/views/livewire/panel/branches/**'
---

# Branches

## Clientes disponibles fuera del formulario de sucursales
La propiedad clients alimenta el filtro de clientes, la disponibilidad del botón Nueva sucursal y el aviso de ausencia de clientes, además del formulario. Debe consultar los clientes aunque showFormModal sea false; no devolver una colección vacía al cerrar el modal. Antes de diferir catálogos, revisar todos sus consumidores en la pantalla.

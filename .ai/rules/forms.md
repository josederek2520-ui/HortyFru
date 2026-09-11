---
paths:
  - 'app/{Actions/Compras,Livewire/Panel/Compras,Livewire/Forms}/**'
---

# Forms

## Asignar comprador desde la sesión
Al crear una compra, empleado_id se obtiene exclusivamente del empleado activo vinculado al usuario autenticado; nunca se acepta desde el formulario. Al editar una compra se conserva el comprador original. registrado_por continúa identificando la cuenta que creó el registro.

## Registrar el total pagado por producto
En el detalle de compra, la persona ingresa directamente el total pagado de la línea. subtotal_detalle_compra conserva ese importe exacto; precio_unitario_detalle_compra y precio_por_detalle_compra se derivan internamente por compatibilidad. La cantidad real es obligatoria en compra directa, opcional en presentaciones variables y automática en presentaciones fijas.

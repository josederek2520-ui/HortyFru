---
paths:
  - 'app/{Models,Actions,Livewire,Policies}/**/*.php,database/migrations/*recepcion*.php,resources/views/livewire/panel/recepciones/**'
---

# Recepciones

## Recepción separada de sus detalles
La cabecera de recepción nace en BORRADOR, puede vincular opcionalmente una compra REGISTRADA y toma empleado_id del empleado activo de la sesión. No confirmar ni crear lotes o movimientos desde la cabecera: esa transición se hará al confirmar detalle_recepcion. Una compra con recepción no cancelada no puede cancelarse.

## Confirmación de recepción genera stock
detalle_recepcion registra toda la cantidad física descargada; daños detectados después se procesan en merma o aprovechamiento. Al confirmar una recepción se crea un lote por detalle y un único movimiento ENTRADA_RECEPCION dentro de la misma transacción. La cantidad del inventario siempre usa cantidad_base_detalle_recepcion y la recepción queda de solo lectura.

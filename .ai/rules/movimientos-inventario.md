---
paths:
  - 'app/{Models,Actions,Livewire}/**/*.php,database/migrations/*movimiento*inventario*.php,resources/views/livewire/panel/movimientos-inventario/**'
---

# Movimientos Inventario

## Inventario como libro de movimientos
El stock nunca se guarda en stock_actual: se calcula sumando entradas y restando salidas REGISTRADAS en detalle_movimiento_inventario. Toda cantidad está en la unidad base del artículo, es positiva y pertenece a un lote; el tipo del movimiento define el signo. Los movimientos operativos se crean mediante RegistrarMovimientoInventarioAction desde recepción, pedido, producción, merma, devolución o ajuste autorizado; la pantalla es de consulta. Antes de una salida se bloquea el lote y se valida saldo por almacén para evitar inventario negativo.

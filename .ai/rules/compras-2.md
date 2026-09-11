---
paths:
  - '**/compras/**'
---

# Compras 2

## Necesidad de compra calculada desde pedidos
En el detalle de una compra, la necesidad se calcula desde detalles de pedidos PENDIENTES o PREPARADOS cuya fecha requerida coincide con la fecha local de la compra. Se agrupa por producto y presentación solicitada, muestra las cantidades por sucursal y solo precarga el formulario existente; no crea ni vincula automáticamente detalles de compra.

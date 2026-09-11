---
paths:
  - 'app/{Models,Actions,Livewire}/**/*.php,database/migrations/*lote*.php,resources/views/livewire/panel/lotes/**'
---

# Lotes

## Los lotes nacen en la recepción
Los lotes se generan automáticamente al confirmar una recepción o una producción; no existe alta manual desde el módulo de lotes. codigo_lote, articulo_id, tipo_origen_lote y fecha_ingreso_lote son identidad inmutable. No guardar stock_actual en lotes: el saldo se calculará desde movimientos de inventario. FEFO usa lotes DISPONIBLES no vencidos, ordenados por fecha_vencimiento_lote y luego fecha_ingreso_lote.

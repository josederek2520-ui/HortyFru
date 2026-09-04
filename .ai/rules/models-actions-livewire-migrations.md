---
paths:
  - 'app/{Models,Actions,Livewire}/**/*.php,database/migrations/*presentacion*.php'
---

# Models Actions Livewire Migrations

## Presentaciones y equivalencias dependen del artículo
Cada presentación pertenece a un artículo y su equivalencia se interpreta contra la unidad base de ese artículo. FIJA y APROXIMADA requieren equivalencia; VARIABLE conserva equivalencia nula. Solo puede existir una predeterminada por artículo y contexto; al desactivar una presentación se retiran sus marcas predeterminadas. Los detalles transaccionales futuros deben guardar la cantidad base real y la equivalencia aplicada para preservar el historial.

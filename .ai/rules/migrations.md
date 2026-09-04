---
paths:
  - 'app/{Models,Livewire}/**/*.php,database/migrations/*.php'
---

# Migrations

## Sufijos en atributos de dominio
Los atributos descriptivos propios de una entidad usan su sufijo en español (por ejemplo, nombre_sucursal, direccion_sucursal y activo_sucursal). Mantenga sin sufijo las convenciones estructurales de Laravel: id, claves foráneas como cliente_id, created_at y updated_at.

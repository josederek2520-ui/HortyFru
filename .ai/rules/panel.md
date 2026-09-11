---
paths:
  - 'resources/js/**,resources/views/layouts/panel/**,resources/views/components/panel/**'
---

# Panel

## Ciclo de vida del panel con wire:navigate
El panel usa wire:navigate y Livewire.navigate para los enlaces internos. Inicialice widgets por livewire:navigated, destruya instancias y eventos al salir y descarte imports pendientes de páginas anteriores. ApexCharts se importa solo en los módulos de gráficos. Ambos layouts incluyen @livewireScriptConfig porque panel.js inicia Livewire manualmente; no añadir otro inicio ni un preloader con demora fija.

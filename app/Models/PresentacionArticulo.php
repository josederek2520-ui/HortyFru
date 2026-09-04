<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use App\Enums\TipoEquivalenciaPresentacionArticulo;
use App\Enums\UsoPresentacionArticulo;
use Database\Factories\PresentacionArticuloFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PresentacionArticulo extends Model
{
    /** @use HasFactory<PresentacionArticuloFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'presentaciones_articulos';

    /** @var list<string> */
    protected $fillable = [
        'articulo_id',
        'nombre_presentacion_articulo',
        'uso_presentacion_articulo',
        'tipo_equivalencia_presentacion_articulo',
        'equivalencia_base_presentacion_articulo',
        'permite_fraccion_presentacion_articulo',
        'predeterminada_pedido_presentacion_articulo',
        'predeterminada_compra_presentacion_articulo',
        'estado_presentacion_articulo',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'uso_presentacion_articulo' => UsoPresentacionArticulo::Ambos->value,
        'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::Variable->value,
        'permite_fraccion_presentacion_articulo' => false,
        'predeterminada_pedido_presentacion_articulo' => false,
        'predeterminada_compra_presentacion_articulo' => false,
        'estado_presentacion_articulo' => true,
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::ArticlePresentations->value)
            ->logOnly([
                'articulo_id',
                'nombre_presentacion_articulo',
                'uso_presentacion_articulo',
                'tipo_equivalencia_presentacion_articulo',
                'equivalencia_base_presentacion_articulo',
                'permite_fraccion_presentacion_articulo',
                'predeterminada_pedido_presentacion_articulo',
                'predeterminada_compra_presentacion_articulo',
                'estado_presentacion_articulo',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Presentación de artículo registrada',
                'updated' => 'Presentación de artículo actualizada',
                default => 'Actividad de presentación de artículo',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'uso_presentacion_articulo' => UsoPresentacionArticulo::class,
            'tipo_equivalencia_presentacion_articulo' => TipoEquivalenciaPresentacionArticulo::class,
            'equivalencia_base_presentacion_articulo' => 'decimal:3',
            'permite_fraccion_presentacion_articulo' => 'boolean',
            'predeterminada_pedido_presentacion_articulo' => 'boolean',
            'predeterminada_compra_presentacion_articulo' => 'boolean',
            'estado_presentacion_articulo' => 'boolean',
        ];
    }
}

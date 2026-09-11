<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\ArticuloFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Articulo extends Model
{
    /** @use HasFactory<ArticuloFactory> */
    use HasFactory, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'nombre_articulo',
        'categoria_articulo_id',
        'unidad_medida_id',
        'imagen_articulo',
        'estado_articulo',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_articulo' => true,
    ];

    public function categoriaArticulo(): BelongsTo
    {
        return $this->belongsTo(CategoriaArticulo::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function presentaciones(): HasMany
    {
        return $this->hasMany(PresentacionArticulo::class);
    }

    public function detallesPedido(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function detallesCompra(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function detallesRecepcion(): HasMany
    {
        return $this->hasMany(DetalleRecepcion::class);
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function detallesMovimientoInventario(): HasMany
    {
        return $this->hasMany(DetalleMovimientoInventario::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::Articles->value)
            ->logOnly([
                'nombre_articulo',
                'categoria_articulo_id',
                'unidad_medida_id',
                'imagen_articulo',
                'estado_articulo',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Producto registrado',
                'updated' => 'Producto actualizado',
                default => 'Actividad de producto',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estado_articulo' => 'boolean',
        ];
    }

    protected function imagenUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->imagen_articulo === null
                ? null
                : Storage::disk('public')->url($this->imagen_articulo),
        );
    }
}

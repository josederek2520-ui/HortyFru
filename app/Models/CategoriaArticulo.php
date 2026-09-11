<?php

namespace App\Models;

use App\Enums\ActivityLogName;
use Database\Factories\CategoriaArticuloFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CategoriaArticulo extends Model
{
    /** @use HasFactory<CategoriaArticuloFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'categorias_articulos';

    /** @var list<string> */
    protected $fillable = [
        'nombre_categoria_articulo',
        'estado_categoria_articulo',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estado_categoria_articulo' => true,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(ActivityLogName::ArticleCategories->value)
            ->logOnly([
                'nombre_categoria_articulo',
                'estado_categoria_articulo',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Categoría de producto registrada',
                'updated' => 'Categoría de producto actualizada',
                default => 'Actividad de categoría de producto',
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estado_categoria_articulo' => 'boolean',
        ];
    }
}

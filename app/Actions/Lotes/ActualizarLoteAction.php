<?php

namespace App\Actions\Lotes;

use App\Models\Lote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarLoteAction
{
    /** @param array{fecha_vencimiento_lote: ?string, calidad_lote: ?string, observaciones_lote: ?string} $datos */
    public function __invoke(Lote $lote, array $datos): Lote
    {
        return DB::transaction(function () use ($lote, $datos): Lote {
            $lote = Lote::query()->lockForUpdate()->findOrFail($lote->id);
            $fechaIngreso = $lote->fecha_ingreso_local->startOfDay();
            $fechaVencimiento = $datos['fecha_vencimiento_lote'] === null
                ? null
                : CarbonImmutable::createFromFormat('Y-m-d', $datos['fecha_vencimiento_lote']);

            if ($fechaVencimiento !== null
                && $fechaVencimiento->toDateString() < $fechaIngreso->toDateString()) {
                throw ValidationException::withMessages([
                    'form.fecha_vencimiento_lote' => 'La fecha de vencimiento no puede ser anterior al ingreso del lote.',
                ]);
            }

            $lote->update($datos);

            return $lote->refresh()->load('articulo.unidadMedida');
        });
    }
}

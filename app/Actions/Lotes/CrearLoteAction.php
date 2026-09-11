<?php

namespace App\Actions\Lotes;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoLote;
use App\Enums\TipoOrigenLote;
use App\Models\Articulo;
use App\Models\Lote;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrearLoteAction
{
    public function __invoke(
        Articulo $articulo,
        TipoOrigenLote $tipoOrigen,
        CarbonInterface $fechaIngreso,
        ?CarbonInterface $fechaVencimiento,
        ?string $calidad,
        ?string $observaciones,
        User $usuario,
    ): Lote {
        return DB::transaction(function () use ($articulo, $tipoOrigen, $fechaIngreso, $fechaVencimiento, $calidad, $observaciones, $usuario): Lote {
            $fechaIngreso = $fechaIngreso->toImmutable()->utc();
            $fechaVencimiento = $fechaVencimiento?->toImmutable()->startOfDay();
            $fechaIngresoLocal = $fechaIngreso->setTimezone(
                (string) config('app.display_timezone', 'America/La_Paz'),
            )->startOfDay();

            if ($fechaVencimiento !== null
                && $fechaVencimiento->toDateString() < $fechaIngresoLocal->toDateString()) {
                throw ValidationException::withMessages([
                    'fecha_vencimiento_lote' => 'La fecha de vencimiento no puede ser anterior al ingreso del lote.',
                ]);
            }

            $lote = new Lote;
            $lote->disableLogging();
            $lote->fill([
                'codigo_lote' => 'TEMP-'.Str::uuid(),
                'articulo_id' => $articulo->id,
                'tipo_origen_lote' => $tipoOrigen,
                'fecha_ingreso_lote' => $fechaIngreso,
                'fecha_vencimiento_lote' => $fechaVencimiento?->toDateString(),
                'calidad_lote' => $this->normalizarOpcional($calidad),
                'estado_lote' => EstadoLote::Disponible,
                'motivo_bloqueo_lote' => null,
                'observaciones_lote' => $this->normalizarOpcional($observaciones),
            ]);
            $lote->save();
            $lote->update([
                'codigo_lote' => sprintf(
                    'LOT-%s-%03d',
                    $fechaIngresoLocal->format('dmY'),
                    $lote->id,
                ),
            ]);
            $lote->enableLogging();

            activity(ActivityLogName::Lots->value)
                ->event(ActivityEvent::Created->value)
                ->performedOn($lote)
                ->causedBy($usuario)
                ->withProperties(['attributes' => $this->datosAuditoria($lote)])
                ->log('Lote generado desde '.$tipoOrigen->label());

            return $lote->load('articulo.unidadMedida');
        });
    }

    private function normalizarOpcional(?string $valor): ?string
    {
        $valor = Str::squish($valor ?? '');

        return $valor === '' ? null : $valor;
    }

    /** @return array<string, mixed> */
    private function datosAuditoria(Lote $lote): array
    {
        return [
            'codigo_lote' => $lote->codigo_lote,
            'articulo_id' => $lote->articulo_id,
            'tipo_origen_lote' => $lote->tipo_origen_lote->value,
            'fecha_ingreso_lote' => $lote->fecha_ingreso_lote->toDateTimeString(),
            'fecha_vencimiento_lote' => $lote->fecha_vencimiento_lote?->toDateString(),
            'calidad_lote' => $lote->calidad_lote,
            'estado_lote' => $lote->estado_lote->value,
            'observaciones_lote' => $lote->observaciones_lote,
        ];
    }
}

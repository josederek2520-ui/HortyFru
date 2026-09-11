<?php

namespace App\Actions\Recepciones;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use App\Models\Almacen;
use App\Models\Compra;
use App\Models\Recepcion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarRecepcionAction
{
    /** @param array{compra_id: ?int, almacen_id: int, fecha_recepcion: string, observaciones_recepcion: ?string} $datos */
    public function __invoke(Recepcion $recepcion, array $datos, User $usuario): Recepcion
    {
        return DB::transaction(function () use ($recepcion, $datos, $usuario): Recepcion {
            $recepcion = Recepcion::query()->lockForUpdate()->findOrFail($recepcion->id);

            if (! $recepcion->estaEnBorrador()) {
                throw ValidationException::withMessages([
                    'form.recepcion' => 'Solo se pueden editar recepciones en borrador.',
                ]);
            }

            $almacen = Almacen::query()->lockForUpdate()->findOrFail($datos['almacen_id']);
            if (! $almacen->estado_almacen && $recepcion->almacen_id !== $almacen->id) {
                throw ValidationException::withMessages(['form.almacen_id' => 'Selecciona un almacén activo.']);
            }

            $fechaRecepcion = $this->fechaUtc($datos['fecha_recepcion']);
            $this->verificarCompra($datos['compra_id'], $fechaRecepcion);
            $this->verificarCompatibilidadConDetalles($recepcion, $datos['compra_id'], $fechaRecepcion);
            $anteriores = $this->datosAuditoria($recepcion);

            $recepcion->disableLogging();
            $recepcion->update([
                'compra_id' => $datos['compra_id'],
                'almacen_id' => $almacen->id,
                'fecha_recepcion' => $fechaRecepcion,
                'observaciones_recepcion' => $datos['observaciones_recepcion'],
            ]);
            $recepcion->enableLogging();

            activity(ActivityLogName::Receptions->value)
                ->event(ActivityEvent::Updated->value)
                ->performedOn($recepcion)
                ->causedBy($usuario)
                ->withProperties(['old' => $anteriores, 'attributes' => $this->datosAuditoria($recepcion)])
                ->log('Recepción actualizada');

            return $recepcion->load(['compra.proveedor', 'almacen', 'empleado', 'registradoPor']);
        });
    }

    private function verificarCompra(?int $compraId, CarbonImmutable $fechaRecepcion): void
    {
        if ($compraId === null) {
            return;
        }

        $compra = Compra::query()->lockForUpdate()->findOrFail($compraId);

        if ($compra->estado_compra !== EstadoCompra::Registrada) {
            throw ValidationException::withMessages([
                'form.compra_id' => 'Solo se pueden recibir compras registradas.',
            ]);
        }

        if ($fechaRecepcion->isBefore($compra->fecha_compra)) {
            throw ValidationException::withMessages([
                'form.fecha_recepcion' => 'La recepción no puede ser anterior a la fecha de la compra.',
            ]);
        }
    }

    private function fechaUtc(string $fecha): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $fecha,
            (string) config('app.display_timezone', 'America/La_Paz'),
        )->utc();
    }

    private function verificarCompatibilidadConDetalles(
        Recepcion $recepcion,
        ?int $compraId,
        CarbonImmutable $fechaRecepcion,
    ): void {
        if (! $recepcion->detalles()->exists()) {
            return;
        }

        if ($recepcion->compra_id !== $compraId) {
            throw ValidationException::withMessages([
                'form.compra_id' => 'Quita los productos recibidos antes de cambiar la compra relacionada.',
            ]);
        }

        $fechaLocal = $fechaRecepcion->setTimezone(
            (string) config('app.display_timezone', 'America/La_Paz'),
        )->toDateString();

        if ($recepcion->detalles()
            ->whereNotNull('fecha_vencimiento_detalle_recepcion')
            ->whereDate('fecha_vencimiento_detalle_recepcion', '<', $fechaLocal)
            ->exists()) {
            throw ValidationException::withMessages([
                'form.fecha_recepcion' => 'La fecha dejaría un producto con vencimiento anterior a su recepción.',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function datosAuditoria(Recepcion $recepcion): array
    {
        return [
            'compra_id' => $recepcion->compra_id,
            'almacen_id' => $recepcion->almacen_id,
            'empleado_id' => $recepcion->empleado_id,
            'fecha_recepcion' => $recepcion->fecha_recepcion->toDateTimeString(),
            'observaciones_recepcion' => $recepcion->observaciones_recepcion,
        ];
    }
}

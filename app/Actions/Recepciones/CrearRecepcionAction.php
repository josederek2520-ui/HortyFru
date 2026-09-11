<?php

namespace App\Actions\Recepciones;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use App\Enums\EstadoRecepcion;
use App\Models\Almacen;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\Recepcion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrearRecepcionAction
{
    /** @param array{compra_id: ?int, almacen_id: int, fecha_recepcion: string, observaciones_recepcion: ?string} $datos */
    public function __invoke(array $datos, User $usuario): Recepcion
    {
        return DB::transaction(function () use ($datos, $usuario): Recepcion {
            $empleado = $this->obtenerEmpleadoReceptor($usuario);
            $almacen = Almacen::query()->lockForUpdate()->findOrFail($datos['almacen_id']);

            if (! $almacen->estado_almacen) {
                throw ValidationException::withMessages(['form.almacen_id' => 'Selecciona un almacén activo.']);
            }

            $fechaRecepcion = $this->fechaUtc($datos['fecha_recepcion']);
            $this->verificarCompra($datos['compra_id'], $fechaRecepcion);

            $recepcion = new Recepcion;
            $recepcion->disableLogging();
            $recepcion->fill([
                'compra_id' => $datos['compra_id'],
                'almacen_id' => $almacen->id,
                'empleado_id' => $empleado->id,
                'fecha_recepcion' => $fechaRecepcion,
                'estado_recepcion' => EstadoRecepcion::Borrador,
                'observaciones_recepcion' => $datos['observaciones_recepcion'],
                'registrado_por' => $usuario->id,
            ]);
            $recepcion->save();
            $recepcion->update(['codigo_recepcion' => sprintf('REC-%06d', $recepcion->id)]);
            $recepcion->enableLogging();

            activity(ActivityLogName::Receptions->value)
                ->event(ActivityEvent::Created->value)
                ->performedOn($recepcion)
                ->causedBy($usuario)
                ->withProperties(['attributes' => $this->datosAuditoria($recepcion)])
                ->log('Recepción registrada como borrador');

            return $recepcion->load(['compra.proveedor', 'almacen', 'empleado', 'registradoPor']);
        });
    }

    private function obtenerEmpleadoReceptor(User $usuario): Empleado
    {
        $empleado = Empleado::query()
            ->where('user_id', $usuario->id)
            ->where('activo_empleado', true)
            ->lockForUpdate()
            ->first();

        if ($empleado === null) {
            throw ValidationException::withMessages([
                'form.empleado_id' => 'Tu cuenta debe estar vinculada a un empleado activo para registrar recepciones.',
            ]);
        }

        return $empleado;
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

    /** @return array<string, mixed> */
    private function datosAuditoria(Recepcion $recepcion): array
    {
        return [
            'codigo_recepcion' => $recepcion->codigo_recepcion,
            'compra_id' => $recepcion->compra_id,
            'almacen_id' => $recepcion->almacen_id,
            'empleado_id' => $recepcion->empleado_id,
            'fecha_recepcion' => $recepcion->fecha_recepcion->toDateTimeString(),
            'estado_recepcion' => $recepcion->estado_recepcion->value,
            'observaciones_recepcion' => $recepcion->observaciones_recepcion,
            'registrado_por' => $recepcion->registrado_por,
        ];
    }
}

<?php

namespace App\Actions\Compras;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\EstadoCompra;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrearCompraAction
{
    /** @param array{proveedor_id: int, fecha_compra: string, observaciones_compra: ?string} $datos */
    public function __invoke(array $datos, User $usuario): Compra
    {
        return DB::transaction(function () use ($datos, $usuario): Compra {
            $empleado = $this->obtenerEmpleadoComprador($usuario);
            $this->verificarProveedorActivo($datos['proveedor_id']);

            $compra = new Compra;
            $compra->disableLogging();
            $compra->fill([
                'proveedor_id' => $datos['proveedor_id'],
                'empleado_id' => $empleado->id,
                'fecha_compra' => CarbonImmutable::createFromFormat(
                    'Y-m-d\TH:i',
                    $datos['fecha_compra'],
                    (string) config('app.display_timezone', 'America/La_Paz'),
                )->utc(),
                'estado_compra' => EstadoCompra::Borrador,
                'total_compra' => 0,
                'observaciones_compra' => $datos['observaciones_compra'],
                'registrado_por' => $usuario->id,
            ]);
            $compra->save();
            $compra->update(['codigo_compra' => sprintf('COM-%06d', $compra->id)]);
            $compra->enableLogging();

            activity(ActivityLogName::Purchases->value)
                ->event(ActivityEvent::Created->value)
                ->performedOn($compra)
                ->causedBy($usuario)
                ->withProperties(['attributes' => $this->datosAuditoria($compra)])
                ->log('Compra registrada como borrador');

            return $compra->load(['proveedor', 'empleado', 'registradoPor']);
        });
    }

    private function obtenerEmpleadoComprador(User $usuario): Empleado
    {
        $empleado = Empleado::query()
            ->where('user_id', $usuario->id)
            ->where('activo_empleado', true)
            ->lockForUpdate()
            ->first();

        if ($empleado === null) {
            throw ValidationException::withMessages([
                'form.empleado_id' => 'Tu cuenta debe estar vinculada a un empleado activo para registrar compras.',
            ]);
        }

        return $empleado;
    }

    private function verificarProveedorActivo(int $proveedorId): void
    {
        if (! Proveedor::query()->whereKey($proveedorId)->where('estado_proveedor', true)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['form.proveedor_id' => 'Selecciona un proveedor activo.']);
        }
    }

    /** @return array<string, mixed> */
    private function datosAuditoria(Compra $compra): array
    {
        return [
            'codigo_compra' => $compra->codigo_compra,
            'proveedor_id' => $compra->proveedor_id,
            'empleado_id' => $compra->empleado_id,
            'fecha_compra' => $compra->fecha_compra->toDateTimeString(),
            'estado_compra' => $compra->estado_compra->value,
            'total_compra' => $compra->total_compra,
            'observaciones_compra' => $compra->observaciones_compra,
            'registrado_por' => $compra->registrado_por,
        ];
    }
}

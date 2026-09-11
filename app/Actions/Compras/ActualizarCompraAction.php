<?php

namespace App\Actions\Compras;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarCompraAction
{
    /** @param array{proveedor_id: int, fecha_compra: string, observaciones_compra: ?string} $datos */
    public function __invoke(Compra $compra, array $datos, User $usuario): Compra
    {
        return DB::transaction(function () use ($compra, $datos, $usuario): Compra {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);

            if (! $compra->estaEnBorrador()) {
                throw ValidationException::withMessages(['form.compra' => 'Solo se pueden editar compras en borrador.']);
            }

            $this->verificarProveedorDisponible($compra, $datos['proveedor_id']);
            $anteriores = $this->datosAuditoria($compra);
            $compra->disableLogging();
            $compra->update([
                'proveedor_id' => $datos['proveedor_id'],
                'fecha_compra' => CarbonImmutable::createFromFormat(
                    'Y-m-d\TH:i',
                    $datos['fecha_compra'],
                    (string) config('app.display_timezone', 'America/La_Paz'),
                )->utc(),
                'observaciones_compra' => $datos['observaciones_compra'],
            ]);
            $compra->enableLogging();

            activity(ActivityLogName::Purchases->value)
                ->event(ActivityEvent::Updated->value)
                ->performedOn($compra)
                ->causedBy($usuario)
                ->withProperties(['old' => $anteriores, 'attributes' => $this->datosAuditoria($compra)])
                ->log('Compra actualizada');

            return $compra->load(['proveedor', 'empleado', 'registradoPor']);
        });
    }

    private function verificarProveedorDisponible(Compra $compra, int $proveedorId): void
    {
        $proveedorDisponible = $compra->proveedor_id === $proveedorId
            || Proveedor::query()->whereKey($proveedorId)->where('estado_proveedor', true)->lockForUpdate()->exists();

        if (! $proveedorDisponible) {
            throw ValidationException::withMessages(['form.proveedor_id' => 'Selecciona un proveedor activo.']);
        }

    }

    /** @return array<string, mixed> */
    private function datosAuditoria(Compra $compra): array
    {
        return [
            'proveedor_id' => $compra->proveedor_id,
            'empleado_id' => $compra->empleado_id,
            'fecha_compra' => $compra->fecha_compra->toDateTimeString(),
            'observaciones_compra' => $compra->observaciones_compra,
        ];
    }
}

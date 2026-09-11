<?php

namespace App\Actions\Articulos;

use App\Models\Articulo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

class ActualizarArticuloAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(Articulo $articulo, array $datos): Articulo
    {
        /** @var TemporaryUploadedFile|null $imagenNueva */
        $imagenNueva = Arr::pull($datos, 'imagen_articulo');
        $eliminarImagen = (bool) Arr::pull($datos, 'eliminar_imagen', false);
        $rutaAnterior = $articulo->imagen_articulo;
        $rutaNueva = $imagenNueva?->store('articulos', 'public');

        if ($rutaNueva === false) {
            throw new RuntimeException('No fue posible guardar la nueva imagen del producto.');
        }

        $datos['imagen_articulo'] = match (true) {
            $rutaNueva !== null => $rutaNueva,
            $eliminarImagen => null,
            default => $rutaAnterior,
        };

        try {
            $articulo->update($datos);
        } catch (Throwable $exception) {
            if ($rutaNueva !== null) {
                Storage::disk('public')->delete($rutaNueva);
            }

            throw $exception;
        }

        if ($rutaAnterior !== null && $rutaAnterior !== $datos['imagen_articulo']) {
            Storage::disk('public')->delete($rutaAnterior);
        }

        return $articulo->refresh();
    }
}

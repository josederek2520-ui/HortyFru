<?php

namespace App\Actions\Articulos;

use App\Models\Articulo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

class CrearArticuloAction
{
    /** @param array<string, mixed> $datos */
    public function __invoke(array $datos): Articulo
    {
        /** @var TemporaryUploadedFile|null $imagen */
        $imagen = Arr::pull($datos, 'imagen_articulo');
        Arr::forget($datos, 'eliminar_imagen');

        $rutaImagen = $imagen?->store('articulos', 'public');

        if ($rutaImagen === false) {
            throw new RuntimeException('No fue posible guardar la imagen del producto.');
        }

        $datos['imagen_articulo'] = $rutaImagen;

        try {
            return Articulo::query()->create($datos);
        } catch (Throwable $exception) {
            if ($rutaImagen !== null) {
                Storage::disk('public')->delete($rutaImagen);
            }

            throw $exception;
        }
    }
}

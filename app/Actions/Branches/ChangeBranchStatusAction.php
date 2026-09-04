<?php

namespace App\Actions\Branches;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;

class ChangeBranchStatusAction
{
    public function __invoke(Sucursal $sucursal): Sucursal
    {
        return DB::transaction(function () use ($sucursal): Sucursal {
            $previousStatus = $sucursal->activo_sucursal;
            $newStatus = ! $previousStatus;

            $sucursal->disableLogging();
            $sucursal->update(['activo_sucursal' => $newStatus]);
            $sucursal->enableLogging();

            activity(ActivityLogName::Branches->value)
                ->event(ActivityEvent::StatusChanged->value)
                ->performedOn($sucursal)
                ->withProperties([
                    'old' => ['activo_sucursal' => $previousStatus],
                    'attributes' => ['activo_sucursal' => $newStatus],
                ])
                ->log($newStatus ? 'Sucursal activada' : 'Sucursal desactivada');

            return $sucursal->refresh();
        });
    }
}

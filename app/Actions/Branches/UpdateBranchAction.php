<?php

namespace App\Actions\Branches;

use App\Models\Sucursal;

class UpdateBranchAction
{
    /** @param array<string, mixed> $data */
    public function __invoke(Sucursal $sucursal, array $data): Sucursal
    {
        $sucursal->update($data);

        return $sucursal->refresh();
    }
}

<?php

namespace App\Actions\Branches;

use App\Models\Sucursal;

class CreateBranchAction
{
    /** @param array<string, mixed> $data */
    public function __invoke(array $data): Sucursal
    {
        return Sucursal::query()->create($data);
    }
}

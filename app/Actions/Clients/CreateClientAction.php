<?php

namespace App\Actions\Clients;

use App\Models\Cliente;

class CreateClientAction
{
    /** @param array<string, mixed> $data */
    public function __invoke(array $data): Cliente
    {
        return Cliente::query()->create($data);
    }
}

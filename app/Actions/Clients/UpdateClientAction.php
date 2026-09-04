<?php

namespace App\Actions\Clients;

use App\Models\Cliente;

class UpdateClientAction
{
    /** @param array<string, mixed> $data */
    public function __invoke(Cliente $cliente, array $data): Cliente
    {
        $cliente->update($data);

        return $cliente->refresh();
    }
}

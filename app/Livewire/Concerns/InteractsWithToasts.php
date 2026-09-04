<?php

namespace App\Livewire\Concerns;

trait InteractsWithToasts
{
    protected function toast(string $title, string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', title: $title, message: $message, type: $type);
    }
}

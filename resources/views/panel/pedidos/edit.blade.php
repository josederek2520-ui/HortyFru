@extends('layouts.panel.app', ['title' => 'Editar pedido'])

@section('content')
    <livewire:panel.pedidos.edit :pedido-id="$pedido->id" />
@endsection

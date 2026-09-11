@extends('layouts.panel.app', ['title' => 'Detalle de compra'])

@section('content')
    <livewire:panel.compras.details :compra="$compra" />
@endsection

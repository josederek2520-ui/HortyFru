@extends('layouts.panel.app')

@section('content')
    <x-panel.common.page-breadcrumb pageTitle="From Elements" />
    <div class="space-y-6">
        <x-panel.common.component-card title="Basic Table 1">
            <x-panel.tables.basic-tables.basic-tables-one />
        </x-panel.common.component-card>
        <x-panel.common.component-card title="Basic Table 2">
            <x-panel.tables.basic-tables.basic-tables-two />
        </x-panel.common.component-card>
        <x-panel.common.component-card title="Basic Table 3">
            <x-panel.tables.basic-tables.basic-tables-three />
        </x-panel.common.component-card>
        <x-panel.common.component-card title="Basic Table 4">
            <x-panel.tables.basic-tables.basic-tables-four />
        </x-panel.common.component-card>
        <x-panel.common.component-card title="Basic Table 5">
            <x-panel.tables.basic-tables.basic-tables-five />
        </x-panel.common.component-card>
    </div>
@endsection

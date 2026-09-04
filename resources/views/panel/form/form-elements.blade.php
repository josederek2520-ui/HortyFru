@extends('layouts.panel.app')

@section('content')
    <x-panel.common.page-breadcrumb pageTitle="From Elements" />
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="space-y-6">
            <x-panel.form.form-elements.default-inputs />
            <x-panel.form.form-elements.select-inputs />
            <x-panel.form.form-elements.text-area-inputs />
            <x-panel.form.form-elements.input-states />
        </div>
        <div class="space-y-6">
            <x-panel.form.form-elements.input-group />
            <x-panel.form.form-elements.file-input-example />
            <x-panel.form.form-elements.checkbox-component />
            <x-panel.form.form-elements.radio-buttons />
            <x-panel.form.form-elements.toggle-switch />
            <x-panel.form.form-elements.dropzone />
        </div>
    </div>
@endsection

@extends('layouts.panel.app')

@section('content')
  <div class="grid grid-cols-12 gap-4 md:gap-6">
    <div class="col-span-12 space-y-6 xl:col-span-7">
      <x-panel.ecommerce.ecommerce-metrics />
      <x-panel.ecommerce.monthly-sale />
    </div>
    <div class="col-span-12 xl:col-span-5">
        <x-panel.ecommerce.monthly-target />
    </div>

    <div class="col-span-12">
      <x-panel.ecommerce.statistics-chart />
    </div>

    <div class="col-span-12 xl:col-span-5">
      <x-panel.ecommerce.customer-demographic />
    </div>

    <div class="col-span-12 xl:col-span-7">
      <x-panel.ecommerce.recent-orders />
    </div>
  </div>
@endsection

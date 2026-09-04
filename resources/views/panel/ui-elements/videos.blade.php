
@extends('layouts.panel.app')

@section('content')
    {{-- Page Breadcrumb --}}
    <x-panel.common.page-breadcrumb pageTitle="Videos" />

    <div class="grid grid-cols-1 gap-5 sm:gap-6 xl:grid-cols-2">

        <div class="space-y-5 sm:space-y-6">
            <x-panel.common.component-card title="Video Ratio 16:9">
                <x-panel.ui.youtube-embed videoId="dQw4w9WgXcQ" />
            </x-panel.common.component-card>

            <x-panel.common.component-card title="Video Ratio 4:3">
                <x-panel.ui.youtube-embed videoId="dQw4w9WgXcQ" aspectRatio="4:3" />
            </x-panel.common.component-card>
        </div>

        <div class="space-y-5 sm:space-y-6">
            <x-panel.common.component-card title="Video Ratio 21:9">
                <x-panel.ui.youtube-embed videoId="dQw4w9WgXcQ" aspectRatio="21:9" />
            </x-panel.common.component-card>
            <x-panel.common.component-card title="Video Ratio 1:1">
                <x-panel.ui.youtube-embed videoId="dQw4w9WgXcQ" aspectRatio="1:1" />
            </x-panel.common.component-card>
        </div>

    </div>
@endsection

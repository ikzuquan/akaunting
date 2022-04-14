@extends('layouts.admin')

@include($class->views['header'])

@section('content')
    <div class="card">
        @include($class->views['filter'])
    </div>

    @include($class->views['content'])
@endsection

@push('scripts_start')
    <script src="{{ asset('public/js/common/reports.js?v=' . version('short')) }}"></script>
@endpush

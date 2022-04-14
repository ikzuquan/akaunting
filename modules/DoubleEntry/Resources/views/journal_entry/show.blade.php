@extends('layouts.admin')

@section('title', trans('double-entry::general.journal_entry'))

@section('new_button')
    <x-transactions.show.top-buttons
        type="journal"
        :transaction="$journal_entry"
        hide-button-group-divider1
        hide-button-email
        hide-button-print
        hide-button-share
        hide-button-pdf
        text-delete-modal="double-entry::general.journal_entry"
    />
@endsection

@section('content')
    <x-transactions.show.content
        type="journal"
        :transaction="$journal_entry"
        hide-header-account
        hide-header-category
        hide-header-contact
        hide-account
        hide-category
        hide-contact
        hide-payment-methods
        hide-footer-histories
        text-header-account=""
        text-description="{{ trans('general.description') }}"
        text-content-title="{{ trans('double-entry::general.journal_entry') }}"
    />
@endsection

@push('scripts_start')
    <link rel="stylesheet" href="{{ asset('public/css/print.css?v=' . version('short')) }}" type="text/css">

    <x-transactions.script type="journal" />
@endpush

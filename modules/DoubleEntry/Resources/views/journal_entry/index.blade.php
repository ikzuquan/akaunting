@extends('layouts.admin')

@section('title', trans('double-entry::general.journal_entry'))

@section('new_button')
    @can('create-double-entry-journal-entry')
        <a href="{{ route('double-entry.journal-entry.create') }}" class="btn btn-sm btn-success header-button-top">{{ trans('general.add_new') }}</a>
        <a href="{{ route('import.create', ['double-entry', 'journal-entry']) }}" class="btn btn-white btn-sm">{{ trans('import.import') }}</a>
    @endcan
    <a href="{{ route('double-entry.journal-entry.export', request()->input()) }}" class="btn btn-white btn-sm">{{ trans('general.export') }}</a>
@endsection

@section('content')
    @if ($journals->count() || request()->get('search', false))
        <div class="card">
            <div class="card-header border-bottom-0" :class="[{'bg-gradient-primary': bulk_action.show}]">
                {!! Form::open([
                    'method' => 'GET',
                    'route' => 'double-entry.journal-entry.index',
                    'role' => 'form',
                    'class' => 'mb-0'
                ]) !!}
                    <div class="align-items-center" v-if="!bulk_action.show">
                        <x-search-string model="Modules\DoubleEntry\Models\Journal" />
                    </div>

                    {{ Form::bulkActionRowGroup('double-entry::general.journal_entry', $bulk_actions, ['group' => 'double-entry', 'type' => 'journal-entry']) }}
                {!! Form::close() !!}
            </div>

            <div class="table-responsive">
                <table class="table table-flush table-hover" id="tbl-taxes">
                        <thead class="thead-light">
                            <tr class="row table-head-line">
                                <th class="col-sm-2 col-md-1 col-lg-1 col-xl-1 d-none d-sm-block">{{ Form::bulkActionAllGroup() }}</th>
                                <th class="col-md-2 col-lg-2 col-xl-2 d-none d-md-block">{{ trans_choice('general.numbers', 1) }}</th>
                                <th class="col-xs-3 col-sm-3 col-md-2 col-lg-2 col-xl-2">@sortablelink('paid_at', trans('general.date'))</th>
                                <th class="col-xs-3 col-sm-2 col-md-2 text-right amount-space">@sortablelink('amount', trans('general.amount'))</th>
                                <th class="col-xs-4 col-sm-3 col-md-2 col-lg-2 col-xl-2">{{ trans('general.description') }}</th>
                                <th class="col-md-2 d-none d-md-block">{{ trans('general.reference') }}</th>
                                <th class="col-xs-2 col-sm-2 col-md-1 col-lg-1 col-xl-1 text-center">{{ trans('general.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($journals as $item)
                                <tr class="row align-items-center border-top-1">
                                    <td class="col-sm-2 col-md-1 col-lg-1 col-xl-1 d-none d-sm-block">{{ Form::bulkActionGroup($item->id, 'journal_' . $item->id ) }}</td>
                                    <td class="col-md-2 col-lg-2 col-xl-2 d-none d-md-block"><a href="{{ route('double-entry.journal-entry.show', $item->id) }}">{{ $item->journal_number }}</a></td>
                                    <td class="col-xs-3 col-sm-3 col-md-2 col-lg-2 col-xl-2 border-0">@date($item->paid_at)</td>
                                    <td class="col-xs-3 col-sm-2 col-md-2 text-right amount-space">@money($item->amount, $item->currency_code ?? setting('default.currency'), true)</td>
                                    <td class="col-xs-4 col-sm-3 col-md-2 col-lg-2 col-xl-2 border-0 long-texts">{{ $item->description }}</td>
                                    <td class="col-md-2 d-none d-md-block border-0 long-texts">{{ $item->reference }}</td>
                                    <td class="col-xs-2 col-sm-2 col-md-1 col-lg-1 col-xl-1 text-center">
                                        <div class="dropdown">
                                            <a class="btn btn-neutral btn-sm text-light items-align-center p-2" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-ellipsis-h text-muted"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                                <a class="dropdown-item" href="{{ route('double-entry.journal-entry.show', $item->id) }}">{{ trans('general.show') }}</a>
                                                @can('update-double-entry-journal-entry')
                                                    <div class="dropdown-divider"></div>    
                                                    <a class="dropdown-item" href="{{ route('double-entry.journal-entry.edit', $item->id) }}">{{ trans('general.edit') }}</a>
                                                @endcan('update-double-entry-journal-entry')
                                                @can('create-double-entry-journal-entry')
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="{{ route('double-entry.journal-entry.duplicate', $item->id) }}">{{ trans('general.duplicate') }}</a>
                                                @endcan
                                                @can('delete-double-entry-journal-entry')
                                                    <div class="dropdown-divider"></div>
                                                    {!! Form::deleteLink($item, 'double-entry.journal-entry.destroy', 'double-entry::general.journal_entry') !!}
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                </table>
            </div>

            <div class="card-footer table-action">
                <div class="row">
                    @include('partials.admin.pagination', ['items' => $journals])
                </div>
            </div>
        </div>
    @else
        <x-empty-page 
            page="manual-journal" 
            image-empty-page="{{ asset('modules/DoubleEntry/Resources/assets/img/manual-journal.png') }}"
            text-empty-page="double-entry::general.empty.manual_journal"
            text-page="double-entry::general.journal_entry"
            url-docs-path="https://akaunting.com/docs/app-manual/accounting/double-entry"
            permission-create="create-double-entry-journal-entry"
            route-create="double-entry.journal-entry.create"
        />
    @endif
@endsection

@push('scripts_start')
    <script src="{{ asset('modules/DoubleEntry/Resources/assets/js/journal-entries.min.js?v=' . module_version('double-entry')) }}"></script>
@endpush

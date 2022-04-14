@extends('layouts.admin')

@section('title', trans_choice('double-entry::general.chart_of_accounts', 2))

@section('new_button')
    @can('create-double-entry-chart-of-accounts')
        <a href="{{ route('double-entry.chart-of-accounts.create') }}" class="btn btn-sm btn-success header-button-top">{{ trans('general.add_new') }}</a>
        <a href="{{ route('import.create', ['double-entry', 'chart-of-accounts']) }}" class="btn btn-white btn-sm header-button-top">{{ trans('import.import') }}</a>
    @endcan
    <a href="{{ route('double-entry.chart-of-accounts.export', request()->input()) }}&limit=250" class="btn btn-white btn-sm header-button-top">{{ trans('general.export') }}</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom-0" :class="[{'bg-gradient-primary': bulk_action.show}]">
            {!! Form::open([
                'method' => 'GET',
                'route' => 'double-entry.chart-of-accounts.index',
                'role' => 'form',
                'class' => 'mb-0'
            ]) !!}
                <div class="align-items-center" v-if="!bulk_action.show">
                    <x-search-string model="Modules\DoubleEntry\Models\Account" />
                </div>

                {{ Form::bulkActionRowGroup('double-entry::general.chart_of_accounts', $bulk_actions, ['group' => 'double-entry', 'type' => 'chart-of-accounts']) }}
            {!! Form::close() !!}
        </div>
    </div>
    @foreach($accounts as $account)
        @if ($account->is_first_item_of_class)
            <div class="card">
                <div class="card-header border-bottom-0">
                    <h3 class="box-title mb-0">{{ trans($account->declass->name) }}</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-flush table-hover" id="tbl-taxes">
                        <thead class="thead-light">
                            <tr class="row table-head-line">
                                <th class="col-sm-2 col-md-1 col-lg-1 col-xl-1 d-none d-sm-block">
                                    {{ Form::doubleEntryBulkActionAllGroup(['v-model' => 'bulk_action.select_all[' . $account->declass->id . ']', 'group' => $account->declass->id]) }}
                                </th>
                                <th class="col-md-1 col-lg-1 col-xl-1 d-none d-md-block">{{ trans('general.code') }}</th>
                                <th class="col-xs-4 col-sm-4 col-md-4 col-lg-4 col-xl-4">{{ trans('general.name') }}</th>
                                <th class="col-md-2 col-lg-2 col-xl-2 d-none d-md-block">{{ trans_choice('general.types', 1) }}</th>
                                <th class="col-lg-2 col-xl-2 d-none d-lg-block">{{ trans('general.balance') }}</th>
                                <th class="col-xs-4 col-sm-3 col-md-2 col-lg-1 col-xl-1 text-center">{{ trans('general.enabled') }}</th>
                                <th class="col-xs-4 col-sm-3 col-md-2 col-lg-1 col-xl-1 text-center">{{ trans('general.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
        @endif
                            <tr class="row align-items-center border-top-1">
                                <td class="col-sm-2 col-md-1 col-lg-1 col-xl-1 d-none d-sm-block">
                                    {{ Form::doubleEntryBulkActionGroup($account->id, $account->name, ['v-model' => 'bulk_action.selected_grouped[' . $account->declass->id . ']', 'group' => $account->declass->id]) }}
                                </td>
                                <td class="col-md-1 col-lg-1 col-xl-1 d-none d-md-block">
                                    @if($account->sub_accounts->count() > 0)
                                        <a data-toggle="collapse" href="#collapse-{{ $account->id }}" role="button" aria-expanded="false" aria-controls="collapse-{{ $account->id }}" onclick="collapseSubAccounts('collapse-{{ $account->id }}')">
                                            {{ $account->code }}
                                            <i class="fas fa-angle-right"></i>
                                            <i class="fas fa-angle-down"></i>
                                        </a>
                                    @else
                                        {{ $account->code }}
                                    @endif
                                </td>
                                <td class="col-xs-4 col-sm-4 col-md-4 col-lg-4 col-xl-4 long-texts">
                                    {!! $account->name_linked_general_ledger !!}
                                </td>
                                <td class="col-md-2 col-lg-2 col-xl-2 d-none d-md-block">{{ trans($account->type->name) }}</td>
                                <td class="col-lg-2 col-xl-2 d-none d-lg-block">
                                    {!! $account->balance_colorized !!}
                                </td>
                                <td class="col-xs-4 col-sm-3 col-md-2 col-lg-1 col-xl-1 text-center">
                                    @if (user()->can('update-double-entry-chart-of-accounts'))
                                        {{ Form::enabledGroup($account->id, $account->name, $account->enabled) }}
                                    @else
                                        @if ($account->enabled)
                                            <badge rounded type="success">{{ trans('general.enabled') }}</badge>
                                        @else
                                            <badge rounded type="danger">{{ trans('general.disabled') }}</badge>
                                        @endif
                                    @endif
                                </td>
                                <td class="col-xs-4 col-sm-3 col-md-2 col-lg-1 col-xl-1 text-center">
                                    <div class="dropdown">
                                        <a class="btn btn-neutral btn-sm text-light items-align-center p-2" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-ellipsis-h text-muted"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                            <a class="dropdown-item" href="{{ route('double-entry.chart-of-accounts.edit', $account->id) }}">{{ trans('general.edit') }}</a>
                                            @can('create-double-entry-chart-of-accounts')
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="{{ route('double-entry.chart-of-accounts.duplicate', $account->id) }}">{{ trans('general.duplicate') }}</a>
                                            @endcan
                                            @can('delete-double-entry-chart-of-accounts')
                                                <div class="dropdown-divider"></div>
                                                {!! Form::deleteLink($account, 'double-entry.chart-of-accounts.destroy', 'double-entry::general.chart_of_accounts', 'trans_name') !!}
                                            @endcan
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @foreach($account->sub_accounts as $sub_account)
                                @php
                                    $sub_account->load(['type.declass', 'sub_accounts']);
                                @endphp
                                @include('double-entry::chart_of_accounts.sub_account', ['parent_account' => $account, 'sub_account' => $sub_account, 'tree_level' => 1])
                            @endforeach
        @if ($account->is_last_item_of_class)
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endforeach
@endsection

@push('css')
    <style type="text/css">
        a[aria-expanded=true] .fa-angle-down {
            display: none;
        }
        a[aria-expanded=false] .fa-angle-right {
            display: none;
        }
    </style>
@endpush

@push('scripts_start')
    <script src="{{ asset('modules/DoubleEntry/Resources/assets/js/chart-of-accounts.min.js?v=' . module_version('double-entry')) }}"></script>
@endpush

@push('scripts_end')
    <script type="text/javascript">
        function collapseSubAccounts($collapse_id) {
            if ($("a[href='#" + $collapse_id + "']").attr("aria-expanded") == "false") {
                return;
            }

            toggleSubAccounts($collapse_id);
        }

        function toggleSubAccounts($collapse_id) {
            $("tr[id='" + $collapse_id + "']").each(function () {
                $(this).collapse('hide');
            });

            $("tr[id='" + $collapse_id + "'] a[data-toggle='collapse'][aria-expanded='true']").each(function () {
                toggleSubAccounts($(this).attr("aria-controls"));
            });
        }

        $(document).ready(function(){
            $("tr a[data-toggle='collapse']").each(function () {
                var tag = $(this).attr("aria-controls");

                $(this).parent().parent().hover(function() {
                    $("tr[id='" + tag + "']").css("background-color", "#e5e5e5");
                }, function(){
                    $("tr[id='" + tag + "']").removeAttr("style");
                });
            });
        });
    </script>
@endpush
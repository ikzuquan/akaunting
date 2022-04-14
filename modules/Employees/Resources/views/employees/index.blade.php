@extends('layouts.admin')

@section('title', trans_choice('employees::general.employees', 2))

@section('new_button')
    @can('create-employees-employees')
        <a href="{{ route('employees.employees.create') }}" class="btn btn-success btn-sm">{{ trans('general.add_new') }}</a>
        <a href="{{ route('import.create', ['group' => 'employees', 'type' => 'employees']) }}" class="btn btn-white btn-sm">{{ trans('import.import') }}</a>
    @endcan
    <a href="{{ route('employees.employees.export', request()->input()) }}" class="btn btn-white btn-sm">{{ trans('general.export') }}</a>
@endsection

@section('content')
    @if ($employees->count() || request()->get('search', false))
        <div class="card">
            <div class="card-header border-bottom-0" :class="[{'bg-gradient-primary': bulk_action.show}]">
                {!! Form::open([
                    'method' => 'GET',
                    'route' => 'employees.employees.index',
                    'role' => 'form',
                    'class' => 'mb-0'
                ]) !!}
                    <div class="align-items-center" v-if="!bulk_action.show">
                        <x-search-string model="Modules\Employees\Models\Employee" />
                    </div>

                    {{ Form::bulkActionRowGroup('employees::general.employees', $bulk_actions, ['group' => 'employees', 'type' => 'employees']) }}
                {!! Form::close() !!}
            </div>

            <div class="table-responsive">
                <table class="table table-flush table-hover">
                    <thead class="thead-light">
                        <tr class="row table-head-line">
                            <th class="col-sm-2 col-md-1 col-lg-1 col-xl-1 hidden-sm">{{ Form::bulkActionAllGroup() }}</th>
                            <th class="col-md-4">@sortablelink('contact.name', trans('general.name'))</th>
                            <th class="col-md-3 hidden-xs">@sortablelink('contact.email',trans('general.email'))</th>
                            <th class="col-md-2 hidden-xs">@sortablelink('hired_at', trans('employees::employees.hired_at'))</th>
                            <th class="col-md-1 hidden-xs">@sortablelink('contact.enabled', trans_choice('general.statuses', 1))</th>
                            <th class="col-md-1 text-center">{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($employees as $item)
                            <tr class="row align-items-center border-top-1">
                                <td class="col-sm-2 col-md-1 col-lg-1 col-xl-1 hidden-sm border-0">
                                    {{ Form::bulkActionGroup($item->id, $item->name) }}
                                </td>

                                <td class="col-md-4 border-0">
                                    <a href="{{ route('employees.employees.show', $item->id) }}">{{ $item->name }}</a>
                                </td>

                                <td class="col-md-3 border-0 hidden-xs">
                                    {{ $item->email }}
                                </td>

                                <td class="col-md-2 border-0 hidden-xs">
                                    @date($item->hired_at)
                                </td>

                                <td class="col-md-1 border-0 hidden-xs">
                                    @if (user()->can('update-employees-employees'))
                                        {{ Form::enabledGroup($item->id, $item->name, !empty($item->contact) ? $item->contact->enabled : false) }}
                                    @else
                                        @if (!empty($item->contact) && $item->contact->enabled)
                                            <badge rounded type="success" class="mw-60 d-inline-block">{{ trans('general.yes') }}</badge>
                                        @else
                                            <badge rounded type="danger" class="mw-60 d-inline-block">{{ trans('general.no') }}</badge>
                                        @endif
                                    @endif
                                </td>

                                <td class="col-md-1 border-0 text-center">
                                    <div class="dropdown">
                                        <a class="btn btn-neutral btn-sm text-light items-align-center p-2" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-ellipsis-h text-muted"></i>
                                        </a>

                                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                            <a class="dropdown-item" href="{{ route('employees.employees.show', $item->id) }}">
                                                {{ trans('general.show') }}
                                            </a>

                                            @can('update-employees-employees')
                                                <a class="dropdown-item" href="{{ route('employees.employees.edit', $item->id) }}">
                                                    {{ trans('general.edit') }}
                                                </a>
                                            @endcan

                                            <div class="dropdown-divider"></div>

                                            @can('create-employees-employees')
                                                <a class="dropdown-item" href="{{ route('employees.employees.duplicate', $item->id) }}">
                                                    {{ trans('general.duplicate') }}
                                                </a>

                                                <div class="dropdown-divider"></div>
                                            @endcan

                                            @can('delete-employees-employees')
                                                {!! Form::deleteLink($item, 'employees.employees.destroy', 'employees::general.employees') !!}
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
                <div class="row align-items-center">
                    @include('partials.admin.pagination', ['items' => $employees])
                </div>
            </div>
        </div>
    @else
        <x-empty-page
            group="employees"
            page="employees"
            route-create="{{ 'employees.employees.create' }}"
            image-empty-page="modules/Employees/Resources/assets/img/empty_pages/employees.png"
            text-empty-page="employees::general.empty"
            text-page="employees::general.name"
            url-docs-path="https://akaunting.com/docs/app-manual/hr/employees"
        />
    @endif
@endsection

@push('scripts_start')
    <script src="{{ asset('modules/Employees/Resources/assets/js/employees.min.js?v=' . module_version('employees')) }}"></script>
@endpush

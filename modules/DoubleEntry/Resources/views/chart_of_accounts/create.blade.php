@extends('layouts.admin')

@section('title', trans('general.title.new', ['type' => trans_choice('general.accounts', 1)]))

@section('content')
    <div class="card">
        {!! Form::open([
            'method' => 'POST',
            'route' => 'double-entry.chart-of-accounts.store',
            'id' => 'chart-of-account',
            '@submit.prevent' => 'onSubmit',
            '@keydown' => 'form.errors.clear($event.target.name)',
            'files' => true,
            'role' => 'form',
            'class' => 'form-loading-button',
            'novalidate' => true
         ]) !!}

        <div class="card-body">
                <div class="row">
                    {{ Form::textGroup('name', trans('general.name'), 'id-card-o') }}

                    {{ Form::numberGroup('code', trans('general.code'), 'code') }}
                </div>

                <div class="row">
                    {{ Form::selectGroupGroup('type_id', trans_choice('general.types', 1), 'bars', $types, null, ['required' => 'required', 'change' => 'updateParentAccounts']) }}
                </div>

                <div class="row">
                    <div id="customer-create-user" class="form-group col-md-6 margin-top">
                        <div class="custom-control custom-checkbox">
                            {{ Form::checkbox('is_sub_account', '1', null, [
                                'v-model' => 'form.is_sub_account',
                                'id' => 'is_sub_account',
                                'class' => 'custom-control-input'
                            ]) }}

                            <label class="custom-control-label" for="is_sub_account">
                                <strong>{{ ucwords(trans('general.is')) . ' ' . trans('double-entry::general.sub') . '-' . trans_choice('general.accounts', 1) }}?</strong>
                            </label>
                        </div>
                    </div>

                    {{ Form::selectGroupGroup('account_id', trans_choice('double-entry::general.parents', 1) . ' ' . trans_choice('general.accounts', 1), 'university', [], null, ['disabled' => '!isSubAccount', 'dynamicOptions' => 'accountsBasedTypes']) }}
                </div>

                <div class="row">
                    {{ Form::textareaGroup('description', trans('general.description')) }}

                    {{ Form::radioGroup('enabled', trans('general.enabled')) }}
                </div>

                {{ Form::hidden('accounts', json_encode($accounts)) }}
        </div>

        <div class="card-footer">
            <div class="row float-right">
                {{ Form::saveButtons('double-entry.chart-of-accounts.index') }}
            </div>
        </div>
        {!! Form::close() !!}
    </div>
@endsection

@push('scripts_start')
    <script src="{{ asset('modules/DoubleEntry/Resources/assets/js/chart-of-accounts.min.js?v=' . module_version('double-entry')) }}"></script>
@endpush

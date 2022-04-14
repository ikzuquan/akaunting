@if ($sub_account->sub_accounts)
    @if ($loop->first)
        <tr class="row align-items-center border-top-1 collapse"  id="collapse-{{ $parent_account->id }}" data-parent="#collapse-{{ $parent_account->id }}">
            <td class="col-sm-2 col-md-1 col-lg-1 col-xl-1 d-none d-sm-block">
            </td>
            <td class="col-md-1 col-lg-1 col-xl-1 d-none d-md-block" style="margin-left: {{ $tree_level * 25 }}px;">
                <i class="fas fa-level-up-alt fa-rotate-90 mr-1"></i>{{ $parent_account->code }}
            </td>
            <td class="col-xs-4 col-sm-4 col-md-6 col-lg-6 col-xl-6 long-texts" style="margin-right:-{{ $tree_level * 25 }}px;">
                {!! $parent_account->name_linked_general_ledger !!}
            </td>
            <td class="col-lg-2 col-xl-2 d-none d-lg-block">
                {!! $parent_account->balance_without_subaccounts_colorized !!}
            </td>
            <td class="col-xs-8 col-sm-6 col-md-4 col-lg-2 col-xl-2">
            </td>
        </tr>
    @endif
    <tr class="row align-items-center border-top-1 collapse"  id="collapse-{{ $parent_account->id }}" data-parent="#collapse-{{ $parent_account->id }}">
        <td class="col-sm-2 col-md-1 col-lg-1 col-xl-1 d-none d-sm-block">
            {{ Form::doubleEntryBulkActionGroup($sub_account->id, $sub_account->name, ['v-model' => 'bulk_action.selected_grouped[' . $sub_account->type->declass->id . ']', 'group' => $sub_account->type->declass->id]) }}
        </td>
        <td class="col-md-1 col-lg-1 col-xl-1 d-none d-md-block" style="margin-left: {{ $tree_level * 25 }}px;">
            @if($sub_account->sub_accounts->count() > 0)
                <a data-toggle="collapse" href="#collapse-{{ $sub_account->id }}" role="button" aria-expanded="false" aria-controls="collapse-{{ $sub_account->id }}" onclick="collapseSubAccounts('collapse-{{ $sub_account->id }}')">
                    <i class="fas fa-level-up-alt fa-rotate-90 mr-1"></i>
                    {{ $sub_account->code }}
                    <i class="fas fa-angle-right"></i>
                    <i class="fas fa-angle-down"></i>
                </a>
            @else
                <i class="fas fa-level-up-alt fa-rotate-90 mr-1"></i>
                {{ $sub_account->code }}
            @endif
        </td>
        <td class="col-xs-4 col-sm-4 col-md-6 col-lg-6 col-xl-6 long-texts" style="margin-right:-{{ $tree_level * 25 }}px;">
            {!! $sub_account->name_linked_general_ledger !!}
        </td>
        <td class="col-lg-2 col-xl-2 d-none d-lg-block">
            {!! $sub_account->balance_colorized !!}
        </td>
        <td class="col-xs-4 col-sm-3 col-md-2 col-lg-1 col-xl-1 text-center">
            @if (user()->can('update-double-entry-chart-of-accounts'))
                {{ Form::enabledGroup($sub_account->id, $sub_account->name, $sub_account->enabled) }}
            @else
                @if ($sub_account->enabled)
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
                    <a class="dropdown-item" href="{{ route('double-entry.chart-of-accounts.edit', $sub_account->id) }}">{{ trans('general.edit') }}</a>
                    @can('create-double-entry-chart-of-accounts')
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('double-entry.chart-of-accounts.duplicate', $sub_account->id) }}">{{ trans('general.duplicate') }}</a>
                    @endcan
                    @can('delete-double-entry-chart-of-accounts')
                        <div class="dropdown-divider"></div>
                        {!! Form::deleteLink($sub_account, 'double-entry.chart-of-accounts.destroy', 'double-entry::general.chart_of_accounts') !!}
                    @endcan
                </div>
            </div>
        </td>
    </tr>
    @php
        $parent_account = $sub_account;
        $tree_level++;
    @endphp
    @foreach($sub_account->sub_accounts as $sub_account)
        @php
            $sub_account->load(['type.declass', 'sub_accounts']);
        @endphp
        @include('double-entry::chart_of_accounts.sub_account', ['parent_account' => $parent_account, 'sub_account' => $sub_account, 'tree_level' => $tree_level])
    @endforeach
@endif
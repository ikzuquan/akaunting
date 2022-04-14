@foreach($class->de_classes as $de_class)
    <div class="card mb-0 shadow-none">
        <div class="card-header">
            <div class="row">
                <div class="col-9">
                    <h4 class="mb-0 text-uppercase">{{ trans($de_class->name) }}
                        <a data-toggle="collapse" href="#collapse-class-{{ $de_class->id }}" role="button" aria-expanded="true" aria-controls="collapse-class-{{ $de_class->id }}">
                            <i class="fas fa-angle-right"></i>
                            <i class="fas fa-angle-down"></i>
                        </a>
                    </h4>
                </div>
                <div class="col-3 text-right">
                    <h4 class="mb-0 {{ $de_class->total < 0 ? 'text-danger' : 'text-info' }}">@money($de_class->total, setting('default.currency'), true)</h4>
                </div>
            </div>
        </div>
        <div class="card-body pt-0 collapse show" id="collapse-class-{{ $de_class->id }}">
            @foreach($de_class->types as $type)
                @if (!empty($type->total))
                    <div class="row my-1 pt-4">
                        <div class="col-9">
                            <h5 class="pl-4 mb-0 font-weight-bolder text-capitalize">
                                {{ trans($type->name) }}
                                <a data-toggle="collapse" href="#collapse-type-{{ $type->id }}" role="button" aria-expanded="true" aria-controls="collapse-type-{{ $type->id }}">
                                    <i class="fas fa-angle-right"></i>
                                    <i class="fas fa-angle-down"></i>
                                </a>
                            </h5>
                        </div>
                        <div class="col-3 text-right small font-weight-bolder {{ $type->total < 0 ? 'text-danger' : 'text-info' }}">@money($type->total, setting('default.currency'), true)</div>
                    </div>
                    <div class="collapse show" id="collapse-type-{{ $type->id }}">
                        @foreach($class->de_accounts[$type->id] as $account)
                            @if (!empty($account->de_balance))
                                <div class="row py-2 small">
                                    <div class="col-9 pl-6">{!! $account->name_linked_general_ledger !!}</div>
                                    <div class="col-3 text-right {{ $account->de_balance < 0 ? 'text-danger' : 'text-info' }}">@money($account->de_balance, setting('default.currency'), true)</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endforeach

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

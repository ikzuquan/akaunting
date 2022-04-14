@foreach($class->reference_documents as $reference_document)
<div class="card">
    <div class="card-header font-weight-600">
        <div class="row">
            <div class="col-8">{{ $reference_document->date }} - <a href="{{ $reference_document->link }}">{{ $reference_document->transaction }}</a></div>
            <div class="col-2 text-uppercase text-right">{{ trans('double-entry::general.debit') }}</div>
            <div class="col-2 text-uppercase text-right">{{ trans('double-entry::general.credit') }}</div>
        </div>
    </div>
    <div class="card-body pt-0 font-medium">
        @foreach($reference_document->ledgers as $ledger)
            <div class="row pt-4">
                <div class="col-8">{!! $ledger->account->name_linked_general_ledger !!}</div>
                <div class="col-2 text-right">@money((double) $ledger->debit, setting('default.currency'), true)</div>
                <div class="col-2 text-right">@money((double) $ledger->credit, setting('default.currency'), true)</div>
            </div>
        @endforeach
    </div>
    <div class="card-footer font-weight-600 font-medium">
        <div class="row">
            <div class="col-8"></div>
            <div class="col-2 text-right">{{ $reference_document->debit_total }}</div>
            <div class="col-2 text-right">{{ $reference_document->credit_total }}</div>
        </div>
    </div>
</div>
@endforeach

@push('css')
    <style type="text/css">
        .font-medium {
            font-size: 0.8125rem;
        }
    </style>
@endpush
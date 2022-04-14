<div class="col-6">
    <div class="accordion">
        <div class="card">
            <div class="card-header" id="accordion-journals-header" data-toggle="collapse" data-target="#accordion-journals-body" aria-expanded="false" aria-controls="accordion-journals-body">
                <h4 class="mb-0">{{ trans_choice('double-entry::general.journals', 1) }}</h4>
            </div>

            <div id="accordion-journals-body" class="collapse" aria-labelledby="accordion-journals-header">
                <div class="table-responsive">
                    <table class="table table-flush table-hover">
                        <thead class="thead-light">
                            <tr class="row table-head-line">
                                <th class="col-8">{{ trans_choice('general.accounts', 1) }}</th>
                                <th class="col-2">{{ trans('double-entry::general.debit') }}</th>
                                <th class="col-2">{{ trans('double-entry::general.credit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($referenceDocument->ledgers as $ledger)
                                <tr class="row align-items-center border-top-1 tr-py">
                                    <td class="col-8 long-texts">{{ $ledger->account->trans_name }}</td>
                                    <td class="col-2">@money($ledger->debit ?? 0, $referenceDocument->currency_code, true)</td>
                                    <td class="col-2">@money($ledger->credit ?? 0, $referenceDocument->currency_code, true)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
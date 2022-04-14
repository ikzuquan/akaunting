<?php

namespace Modules\DoubleEntry\Traits;

use Illuminate\Support\Str;
use App\Models\Banking\Transaction;
use Modules\DoubleEntry\Models\AccountBank;
use Modules\DoubleEntry\Models\Journal as Model;

trait Journal
{
    protected function isTransfer($journal)
    {
        return Str::contains($journal->reference, 'transfer:');
    }

    protected function isOpeningBalance($journal)
    {
        return Str::contains($journal->reference, 'opening-balance:');
    }

    protected function getConstants($ledger)
    {
        if (!empty($ledger->credit)) {
            $field = 'credit';
            $type = 'expense';
        } else {
            $field = 'debit';
            $type = 'income';
        }

        return [$field, $type];
    }

    protected function getTransaction($ledger)
    {
        return Transaction::where('reference', 'journal-entry-ledger:' . $ledger->id)->first();
    }

    protected function getBank($ledger)
    {
        return AccountBank::where('account_id', $ledger->account_id)->first();
    }

    public function getBasis()
    {
        $basis = [];

        foreach (Model::BASIS as $key => $value) {
            $basis[$key] = trans($value);
        }

        return $basis;
    }
}
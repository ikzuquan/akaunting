<?php

namespace Modules\DoubleEntry\Observers\Banking;

use App\Abstracts\Observer;
use App\Models\Banking\Transfer as Model;
use Modules\DoubleEntry\Models\AccountBank;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\Ledger;

class Transfer extends Observer
{
    /**
     * Listen to the saved event.
     *
     * @param  Model  $transfer
     * @return void
     */
    public function saved(Model $transfer)
    {
        $payment = $transfer->expense_transaction;
        $revenue = $transfer->income_transaction;

        $payment_account_id = AccountBank::where('bank_id', $payment->account_id)->pluck('account_id')->first();
        $revenue_account_id = AccountBank::where('bank_id', $revenue->account_id)->pluck('account_id')->first();

        if (empty($payment_account_id) || empty($revenue_account_id)) {
            return;
        }

        $journal = Journal::updateOrCreate([
            'company_id' => $transfer->company_id,
            'reference' => 'transfer:' . $transfer->id,
        ], [
            'amount' => $revenue->amount,
            'paid_at' => $payment->paid_at,
            'description' => $payment->description ?: '...',
        ]);

        $l1 = $journal->ledger()->updateOrCreate([
            'company_id' => $transfer->company_id,
            'ledgerable_type' => Journal::class,
            'ledgerable_id' => $journal->id,
            'account_id' => $payment_account_id,
        ], [
            'issued_at' => $journal->paid_at,
            'entry_type' => 'item',
            'credit' => $journal->amount,
        ]);

        $payment->reference = 'journal-entry-ledger:' . $l1->id;
        $payment->save();

        $l2 = $journal->ledger()->updateOrCreate([
            'company_id' => $transfer->company_id,
            'ledgerable_type' => Journal::class,
            'ledgerable_id' => $journal->id,
            'account_id' => $revenue_account_id,
        ], [
            'issued_at' => $journal->paid_at,
            'entry_type' => 'item',
            'debit' => $journal->amount,
        ]);

        $revenue->reference = 'journal-entry-ledger:' . $l2->id;
        $revenue->save();
    }

    /**
     * Listen to the deleted event.
     *
     * @param  Model  $transfer
     * @return void
     */
    public function deleted(Model $transfer)
    {
        $journal = Journal::where('reference', 'transfer:' . $transfer->id)->first();

        if (empty($journal)) {
            return;
        }

        Ledger::record($journal->id, get_class($journal))->delete();

        $journal->delete();
    }
}

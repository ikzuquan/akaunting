<?php

namespace Modules\DoubleEntry\Listeners;

use Modules\DoubleEntry\Models\Ledger;

class TransactionCloned
{
    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle($clone, $original)
    {
        foreach ($original->ledgers as $ledger) {
            if ($ledger->entry_type == 'item') {
                Ledger::record($clone->id, 'App\Models\Banking\Transaction')
                    ->where('entry_type', 'item')
                    ->update([
                        'account_id' => $ledger->account_id,
                    ]);
            }
        }
    }
}

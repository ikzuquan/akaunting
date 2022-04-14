<?php

namespace Modules\DoubleEntry\Listeners;

use App\Models\Document\Document;
use Modules\DoubleEntry\Models\Account as Coa;
use Modules\DoubleEntry\Models\Ledger;

class DocumentCloned
{
    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle($clone, $original)
    {
        $original_items = $original->items;
        $clone_items = $clone->items;

        for ($i = 0; $i < count($original_items); $i++) {
            $account_id = Ledger::record($original_items[$i]->id, 'App\Models\Document\DocumentItem')->value('account_id');

            if (is_null($account_id) && $original->type == Document::INVOICE_TYPE) {
                $account_id = Coa::code(setting('double-entry.accounts_sales', 400))->pluck('id')->first();
            }

            if (is_null($account_id) && $original->type == Document::BILL_TYPE) {
                $account_id = Coa::code(setting('double-entry.accounts_expenses', 628))->pluck('id')->first();
            }

            Ledger::record($clone_items[$i]->id, 'App\Models\Document\DocumentItem')->update([
                'account_id' => $account_id,
            ]);
        }
    }
}

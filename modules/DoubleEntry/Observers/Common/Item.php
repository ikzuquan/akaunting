<?php

namespace Modules\DoubleEntry\Observers\Common;

use App\Abstracts\Observer;
use App\Models\Common\Item as Model;
use App\Traits\Modules;
use Modules\DoubleEntry\Models\AccountItem;
use Modules\DoubleEntry\Traits\Accounts;

class Item extends Observer
{
    use Accounts, Modules;

    /**
     * Listen to the saved event.
     *
     * @param  Model  $item
     * @return void
     */
    public function saved(Model $item)
    {
        if (!$this->moduleIsEnabled('double-entry')) {
            return;
        }

        if (isset($item->allAttributes['income_account'])) {
            $item->allAttributes['de_income_account_id'] = $this->findImportedAccountId($item->allAttributes['income_account']);
        }

        if (isset($item->allAttributes['expense_account'])) {
            $item->allAttributes['de_expense_account_id'] = $this->findImportedAccountId($item->allAttributes['expense_account']);
        }

        if (isset($item->allAttributes['de_income_account_id'])) {
            AccountItem::updateOrCreate(
                ['company_id' => company_id(), 'item_id' => $item->id, 'type' => 'income'],
                ['account_id' => $item->allAttributes['de_income_account_id']]
            );
        }

        if (isset($item->allAttributes['de_expense_account_id'])) {
            AccountItem::updateOrCreate(
                ['company_id' => company_id(), 'item_id' => $item->id, 'type' => 'expense'],
                ['account_id' => $item->allAttributes['de_expense_account_id']]
            );
        }
    }

    /**
     * Listen to the deleted event.
     *
     * @param  Model  $item
     * @return void
     */
    public function deleted(Model $item)
    {
        if (!$this->moduleIsEnabled('double-entry')) {
            return;
        }

        AccountItem::where([
            'item_id' => $item->id,
        ])->delete();
    }
}

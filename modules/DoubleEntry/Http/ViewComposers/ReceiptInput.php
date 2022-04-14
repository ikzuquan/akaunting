<?php

namespace Modules\DoubleEntry\Http\ViewComposers;

use App\Models\Banking\Transaction;
use App\Traits\Modules;
use Illuminate\View\View;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Type;

class ReceiptInput
{
    use Modules;

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        if (!$this->moduleIsEnabled('double-entry') || !$this->moduleIsEnabled('receipt')) {
            return;
        }

        $request = request();
        $values = [];
        $selected = null;

        $types = Type::whereHas('declass', function ($query) {
            $query->where('name', 'double-entry::classes.expenses');
        })->pluck('id')->toArray();

        Account::inType($types)
            ->with('type')
            ->enabled()
            ->orderBy('code')
            ->get()
            ->each(function ($account) use (&$values) {
                $values[trans($account->type->name)][$account->id] = $account->code . ' - ' . $account->trans_name;
            });

        if (!$request->routeIs('receipt.edit')) {
            return;
        }

        $receipt = $request->route('receipt');

        $transaction = Transaction::find($receipt->payment_id);

        if (!is_null($transaction)) {
            $ledger = $transaction->de_ledger()->where('entry_type', 'item')->first();

            if (!is_null($ledger)) {
                $selected = $ledger->account->id;
            }
        }

        $name = 'de_account_id';
        $text = trans_choice('general.accounts', 1);
        $attributes = ['required' => null];
        $col = 'col-md-6';

        $view->getFactory()->startPush('tax_amount_input_end', view('double-entry::partials.input_account_group', compact('name', 'text', 'values', 'selected', 'attributes', 'col')));
    }
}

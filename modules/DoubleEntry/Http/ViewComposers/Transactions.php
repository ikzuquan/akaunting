<?php

namespace Modules\DoubleEntry\Http\ViewComposers;

use App\Traits\Modules;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\DoubleEntry\Models\Account;

class Transactions
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
        if (!$this->moduleIsEnabled('double-entry')) {
            return;
        }

        $request = request();
        $values = [];
        $selected = null;

        Account::with('type')
            ->enabled()
            ->orderBy('code')
            ->get()
            ->each(function ($account) use (&$values) {
                $values[trans($account->type->name)][$account->id] = $account->code . ' - ' . $account->trans_name;
            });

        if ($request->routeIs('revenues.edit') || $request->routeIs('payments.edit')) {
            $transaction = $request->route(Str::singular((string) $request->segment(3)));

            $ledger = $transaction->de_ledger()->where('entry_type', 'item')->first();

            if (is_null($ledger)) {
                return;
            }

            if (!is_null($ledger)) {
                $selected = $ledger->account->id;
            }
        }

        $name = 'de_account_id';
        $text = trans_choice('double-entry::general.chart_of_accounts', 1);
        $attributes = ['required' => null];
        $col = 'col-md-6';

        $view->getFactory()->startPush('account_id_input_end', view('double-entry::partials.input_account_group', compact('name', 'text', 'values', 'selected', 'attributes', 'col')));
    }
}

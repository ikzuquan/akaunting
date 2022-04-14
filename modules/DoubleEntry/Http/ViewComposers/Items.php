<?php

namespace Modules\DoubleEntry\Http\ViewComposers;

use App\Traits\Modules;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\AccountItem;
use Modules\DoubleEntry\Models\Type;

class Items
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
        $attributes = ['required' => null];

        // arrangement for income account
        $name = 'de_income_account_id';
        $text = trans_choice('general.incomes', 1) . ' ' . trans_choice('general.accounts', 1);
        $selected = null;

        if ($request->routeIs('*items.edit')) {
            $item = $request->route(Str::singular((string) $request->segment(3)));

            $account = AccountItem::where([
                'item_id' => $item->id,
                'type' => 'income',
            ])->first();

            if (!is_null($account)) {
                $selected = $account->account_id;
            }
        }

        $values = Account::inType(Type::whereHas('declass', function ($query) {
            $query->where('name', 'double-entry::classes.income');
        })->pluck('id')->toArray())->enabled()->orderBy('code')->get()->transform(function ($item) {
            $item->name = $item->code . ' - ' . $item->trans_name;

            return $item;
        })->pluck('name', 'id');

        $view->getFactory()->startPush('purchase_price_input_end', view('double-entry::partials.input_account', compact('name', 'text', 'values', 'selected', 'attributes')));

        // arrangement for expense account
        $name = 'de_expense_account_id';
        $text = trans_choice('general.expenses', 1) . ' ' . trans_choice('general.accounts', 1);
        $selected = null;

        if ($request->routeIs('*items.edit')) {
            $item = $request->route(Str::singular((string) $request->segment(3)));

            $account = AccountItem::where([
                'item_id' => $item->id,
                'type' => 'expense',
            ])->first();

            if (!is_null($account)) {
                $selected = $account->account_id;
            }
        }

        $values = Account::inType(Type::whereHas('declass', function ($query) {
            $query->where('name', 'double-entry::classes.expenses');
        })->pluck('id')->toArray())->enabled()->orderBy('code')->get()->transform(function ($item) {
            $item->name = $item->code . ' - ' . $item->trans_name;

            return $item;
        })->pluck('name', 'id');

        $view->getFactory()->startPush('purchase_price_input_end', view('double-entry::partials.input_account', compact('name', 'text', 'values', 'selected', 'attributes')));
    }
}

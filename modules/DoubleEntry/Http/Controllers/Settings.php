<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Modules\DoubleEntry\Http\Requests\Setting as Request;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\DEClass;
use Modules\DoubleEntry\Models\Type;

class Settings extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        $account_options = $type_options = [];

        $accounts = Account::select(['type_id', 'code', 'name'])->enabled()->get();
        $types = Type::all(['id', 'class_id', 'name']);
        $classes = DEClass::all(['id', 'name']);

        $classes_plucked = $classes->pluck('name', 'id')->map(function ($name) {
            return trans($name);
        })->toArray();

        $types_plucked = $types->pluck('name', 'id')->map(function ($name) {
            return trans($name);
        })->toArray();

        foreach ($accounts as $account) {
            if (!isset($types_plucked[$account->type_id])) {
                continue;
            }

            $account_options[$types_plucked[$account->type_id]][$account->code] = $account->code . ' - ' . $account->trans_name;
        }

        foreach ($types as $type) {
            if (!isset($classes_plucked[$type->class_id])) {
                continue;
            }

            $type_options[$classes_plucked[$type->class_id]][$type->id] = trans($type->name);
        }

        return view('double-entry::settings.edit', compact('account_options', 'type_options'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function update(Request $request)
    {
        $names = [
            'accounts_receivable',
            'accounts_payable',
            'accounts_sales',
            'accounts_expenses',
            'accounts_sales_discount',
            'accounts_purchase_discount',
            'accounts_owners_contribution',
            'types_bank',
            'types_tax',
            'journal.number_prefix',
            'journal.number_digit',
            'journal.number_next',
        ];

        foreach ($names as $name) {
            setting()->set('double-entry.' . $name, $request[str_replace('.', '_', $name)]);
        }

        setting()->save();

        flash(trans('messages.success.updated', ['type' => trans_choice('general.settings', 2)]))->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'redirect' => route('settings.index'),
        ]);
    }
}

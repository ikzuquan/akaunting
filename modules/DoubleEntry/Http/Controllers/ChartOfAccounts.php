<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Http\Requests\Common\Import as ImportRequest;
use App\Traits\Uploads;
use Modules\DoubleEntry\Exports\COA as Export;
use Modules\DoubleEntry\Http\Requests\Account as Request;
use Modules\DoubleEntry\Imports\COA as Import;
use Modules\DoubleEntry\Jobs\Account\CreateAccount;
use Modules\DoubleEntry\Jobs\Account\DeleteAccount;
use Modules\DoubleEntry\Jobs\Account\UpdateAccount;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\DEClass;
use Modules\DoubleEntry\Models\Type;
use Modules\DoubleEntry\Traits\Accounts;

class ChartOfAccounts extends Controller
{
    use Accounts, Uploads;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $accounts = Account::isNotSubAccount()
            ->with(['type', 'declass', 'sub_accounts'])
            ->collect(['type.class_id']);

        $accounts = $this->markStartingEndingFlags($accounts);

        return view('double-entry::chart_of_accounts.index', compact('accounts'));
    }

    /**
     * Show the form for viewing the specified resource.
     *
     * @return Response
     */
    public function show()
    {
        return redirect()->route('double-entry.chart-of-accounts.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $types = [];

        $classes = DEClass::pluck('name', 'id')->map(function ($name) {
            return trans($name);
        })->toArray();

        $all_types = Type::all()->reject(function ($t) {
            return ($t->id == setting('double-entry.types_tax', 17));
        });

        foreach ($all_types as $type) {
            if (!isset($classes[$type->class_id])) {
                continue;
            }

            $types[$classes[$type->class_id]][$type->id] = trans($type->name);
        }

        ksort($types);

        $accounts = [];

        Account::with('type')
            ->collect(['type.class_id'])
            ->each(function ($account) use (&$accounts) {
                $accounts[$account->type->id][trans($account->type->name)][$account->id] = $account->code . ' - ' . $account->trans_name;
            });

        return view('double-entry::chart_of_accounts.create', compact('types', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $response = $this->ajaxDispatch(new CreateAccount($request));

        if ($response['success']) {
            $response['redirect'] = route('double-entry.chart-of-accounts.index');

            $message = trans('messages.success.added', ['type' => trans_choice('general.accounts', 1)]);

            flash($message)->success();
        }

        if ($response['error']) {
            $response['redirect'] = route('double-entry.chart-of-accounts.create');

            $message = $response['message'];

            flash($message)->error()->important();
        }

        return response()->json($response);
    }

    /**
     * Duplicate the specified resource.
     *
     * @param  Account  $chart_of_account
     *
     * @return Response
     */
    public function duplicate(Account $chart_of_account)
    {
        $clone = $chart_of_account->duplicate();

        $message = trans('messages.success.duplicated', ['type' => trans_choice('general.accounts', 1)]);

        flash($message)->success();

        return redirect()->route('double-entry.chart-of-accounts.edit', $clone->id);
    }

    /**
     * Import the specified resource.
     *
     * @param  ImportRequest  $request
     *
     * @return Response
     */
    public function import(ImportRequest $request)
    {
        $response = $this->importExcel(new Import, $request, trans_choice('general.accounts', 2));

        if ($response['success']) {
            $response['redirect'] = route('double-entry.chart-of-accounts.index');

            flash($response['message'])->success();
        } else {
            $response['redirect'] = route('import.create', ['double-entry', 'chart-of-accounts']);

            flash($response['message'])->error()->important();
        }

        return response()->json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Account  $chart_of_account
     *
     * @return Response
     */
    public function edit(Account $chart_of_account)
    {
        $account = $chart_of_account;

        $account->name = trans($account->name);

        $types = [];

        $classes = DEClass::pluck('name', 'id')->map(function ($name) {
            return trans($name);
        })->toArray();

        if ($chart_of_account->type_id == setting('double-entry.types_tax', 17)) {
            $all_types = Type::all();
        } else {
            $all_types = Type::all()->reject(function ($t) {
                return ($t->id == setting('double-entry.types_tax', 17));
            });
        }

        foreach ($all_types as $type) {
            if (!isset($classes[$type->class_id])) {
                continue;
            }

            $types[$classes[$type->class_id]][$type->id] = trans($type->name);
        }

        ksort($types);

        $accounts[$account->type_id] = [];

        Account::with('type')
            ->whereKeyNot($chart_of_account->id)
            ->collect(['type.class_id'])
            ->each(function ($account) use (&$accounts) {
                $accounts[$account->type->id][trans($account->type->name)][$account->id] = $account->code . ' - ' . $account->trans_name;
            });

        return view('double-entry::chart_of_accounts.edit', compact('account', 'types', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Account  $chart_of_account
     * @param  Request  $request
     *
     * @return Response
     */
    public function update(Account $chart_of_account, Request $request)
    {
        $response = $this->ajaxDispatch(new UpdateAccount($chart_of_account, $request));

        if ($response['success']) {
            $response['redirect'] = route('double-entry.chart-of-accounts.index');

            $message = trans('messages.success.updated', ['type' => trans_choice('general.accounts', 1)]);

            flash($message)->success();
        }

        if ($response['error']) {
            $response['redirect'] = route('double-entry.chart-of-accounts.edit', $chart_of_account->id);

            flash($response['message'])->error()->important();
        }

        return response()->json($response);
    }

    /**
     * Enable the specified resource.
     *
     * @param  Account  $chart_of_account
     *
     * @return Response
     */
    public function enable(Account $chart_of_account)
    {
        $response = $this->ajaxDispatch(new UpdateAccount($chart_of_account, request()->merge(['enabled' => 1])));

        if ($response['success']) {
            $response['message'] = trans('messages.success.enabled', ['type' => trans($chart_of_account->name)]);
        }

        return response()->json($response);
    }

    /**
     * Disable the specified resource.
     *
     * @param  Account  $chart_of_account
     *
     * @return Response
     */
    public function disable(Account $chart_of_account)
    {
        $response = $this->ajaxDispatch(new UpdateAccount($chart_of_account, request()->merge(['enabled' => 0])));

        if ($response['success']) {
            $response['message'] = trans('messages.success.disabled', ['type' => trans($chart_of_account->name)]);
        }

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Account  $chart_of_account
     *
     * @return Response
     */
    public function destroy(Account $chart_of_account)
    {
        $response = $this->ajaxDispatch(new DeleteAccount($chart_of_account));

        $response['redirect'] = route('double-entry.chart-of-accounts.index');

        if ($response['success']) {
            $message = trans('messages.success.deleted', ['type' => $chart_of_account->name]);

            flash($message)->success();
        }

        if ($response['error']) {
            $message = $response['message'];

            flash($message)->error()->important();
        }

        return response()->json($response);
    }

    /**
     * Export the specified resource.
     *
     * @return Response
     */
    public function export()
    {
        return $this->exportExcel(new Export, trans_choice('general.accounts', 2));
    }
}

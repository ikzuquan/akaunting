<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Http\Requests\Common\Import as ImportRequest;
use App\Models\Setting\Currency;
use App\Traits\DateTime;
use App\Traits\Documents;
use Modules\DoubleEntry\Exports\JournalEntry\Journals as Export;
use Modules\DoubleEntry\Http\Requests\Journal as Request;
use Modules\DoubleEntry\Imports\JournalEntry\Journals as Import;
use Modules\DoubleEntry\Jobs\Journal\CreateJournalEntry;
use Modules\DoubleEntry\Jobs\Journal\DeleteJournalEntry;
use Modules\DoubleEntry\Jobs\Journal\UpdateJournalEntry;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\Type;
use Modules\DoubleEntry\Traits\Journal as Traits;

class JournalEntry extends Controller
{
    use DateTime, Documents, Traits;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $journals = Journal::collect(['paid_at' => 'desc']);

        return view('double-entry::journal_entry.index', compact('journals'));
    }

    /**
     * Show the form for viewing the specified resource.
     *
     * @return Response
     */
    public function show(Journal $journal_entry)
    {
        return view('double-entry::journal_entry.show', compact('journal_entry'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $accounts = [];

        Account::with('type')
            ->enabled()
            ->orderBy('code')
            ->get()
            ->each(function ($account) use (&$accounts) {
                $accounts[trans($account->type->name)][$account->id] = $account->code . ' - ' . $account->trans_name;
            });

        ksort($accounts);

        $currencies = Currency::enabled()->pluck('name', 'code');

        $currency = Currency::code(setting('default.currency'))->first();

        $journal_number = $this->getNextDocumentNumber('double-entry.journal');

        $basis = $this->getBasis();

        $file_type_mimes = explode(',', config('filesystems.mimes'));

        $file_types = [];

        foreach ($file_type_mimes as $mime) {
            $file_types[] = '.' . $mime;
        }

        $file_types = implode(',', $file_types);

        return view('double-entry::journal_entry.create', compact('accounts', 'currency', 'journal_number', 'basis', 'currencies', 'file_types'));
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
        $this->dispatch(new CreateJournalEntry($request));

        $message = trans('messages.success.added', ['type' => trans('double-entry::general.journal_entry')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'data' => [],
            'redirect' => route('double-entry.journal-entry.index'),
            'message' => $message,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Journal  $journal_entry
     *
     * @return Response
     */
    public function edit(Journal $journal_entry)
    {
        $journal = $journal_entry;

        $accounts = [];

        Account::with('type')
            ->enabled()
            ->orderBy('code')
            ->get()
            ->each(function ($account) use (&$accounts) {
                $accounts[trans($account->type->name)][$account->id] = $account->code . ' - ' . $account->trans_name;
            });

        ksort($accounts);

        foreach ($journal->ledgers as $ledger) {
            if (!empty($ledger->debit)) {
                $journal->debit_account_id = $ledger->account_id;
                $journal->debit_amount = $ledger->debit;
            } else {
                $journal->credit_account_id = $ledger->account_id;
                $journal->credit_amount = $ledger->credit;
            }
        }

        $currencies = Currency::enabled()->pluck('name', 'code');

        $currency = Currency::code($journal->currency_code)->first();

        if (is_null($journal->currency_code)) {
            $currency = Currency::code(setting('default.currency'))->first();

            $journal->currency_code = $currency->code;
            $journal->currency_rate = $currency->rate;
        }

        $basis = $this->getBasis();

        $journal_items = $journal->ledgers()
            ->get()
            ->each(function ($ledger) {
                $ledger->castCredit();
                $ledger->castDebit();
            })
            ->toJson();

        $file_type_mimes = explode(',', config('filesystems.mimes'));

        $file_types = [];

        foreach ($file_type_mimes as $mime) {
            $file_types[] = '.' . $mime;
        }

        $file_types = implode(',', $file_types);

        return view('double-entry::journal_entry.edit', compact('journal', 'accounts', 'currency', 'basis', 'currencies', 'journal_items', 'file_types'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Journal  $journal_entry
     * @param  Request  $request
     *
     * @return Response
     */
    public function update(Journal $journal_entry, Request $request)
    {
        $this->dispatch(new UpdateJournalEntry($journal_entry, $request));

        $message = trans('messages.success.updated', ['type' => trans('double-entry::general.journal_entry')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'data' => [],
            'redirect' => route('double-entry.journal-entry.index'),
            'message' => $message,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Journal  $journal_entry
     *
     * @return Response
     */
    public function destroy(Journal $journal_entry)
    {
        $this->dispatch(new DeleteJournalEntry($journal_entry));

        $message = trans('messages.success.deleted', ['type' => trans('double-entry::general.journal_entry')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'data' => $message,
            'message' => '',
            'redirect' => route('double-entry.journal-entry.index'),
        ]);
    }

    /**
     * Duplicate the specified resource.
     *
     * @param  Journal  $journal_entry
     *
     * @return Response
     */
    public function duplicate(Journal $journal_entry)
    {
        $clone = $journal_entry->duplicate();

        $message = trans('messages.success.duplicated', ['type' => trans('double-entry::general.journal_entry')]);

        flash($message)->success();

        return redirect()->route('double-entry.journal-entry.edit', $clone->id);
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
        $response = $this->importExcel(new Import, $request, trans('double-entry::general.journal_entry'));

        if ($response['success']) {
            $response['redirect'] = route('double-entry.journal-entry.index');

            flash($response['message'])->success();
        } else {
            $response['redirect'] = route('import.create', ['double-entry', 'journal-entry']);

            flash($response['message'])->error()->important();
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
        return $this->exportExcel(new Export, trans('double-entry::general.journal_entry'));
    }
}

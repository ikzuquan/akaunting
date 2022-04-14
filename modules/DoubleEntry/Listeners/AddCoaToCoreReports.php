<?php

namespace Modules\DoubleEntry\Listeners;

use App\Abstracts\Listeners\Report as Listener;
use App\Events\Report\FilterApplying;
use App\Events\Report\FilterShowing;
use App\Events\Report\GroupApplying;
use App\Events\Report\GroupShowing;
use App\Events\Report\RowsShowing;
use App\Models\Document\DocumentTotal;
use App\Reports\ExpenseSummary;
use App\Reports\IncomeExpenseSummary;
use App\Reports\ProfitLoss;
use App\Traits\Currencies;
use Date;
use Modules\DoubleEntry\Models\Account as Coa;

class AddCoaToCoreReports extends Listener
{
    use Currencies;

    /**
     * Handle filter showing event.
     *
     * @param  $event
     * @return void
     */
    public function handleFilterShowing(FilterShowing $event)
    {
        if (
            empty($event->class)
            || empty($event->class->model->settings->group)
            || ($event->class->model->settings->group != 'de_account')
        ) {
            return;
        }

        $reports = [
            'App\Reports\IncomeSummary',
            'App\Reports\ExpenseSummary',
            'App\Reports\IncomeExpenseSummary',
        ];

        if ($this->isEventApplicableToReports($event, $reports)) {
            return;
        }

        if ($event->class->model->settings->group != 'category') {
            unset($event->class->filters['categories']);
        }

        switch (get_class($event->class)) {
            case 'App\Reports\IncomeSummary':
                $types = [13, 14, 15];

                break;
            case 'App\Reports\ExpenseSummary':
                $types = [11, 12];

                break;
            case 'App\Reports\IncomeExpenseSummary':
                $types = [11, 12, 13, 14, 15];

                break;
        }

        $de_accounts = Coa::inType($types)->pluck('name', 'id')->transform(function ($name) {
            return trans($name);
        })->sort()->all();

        $event->class->filters['de_accounts'] = $de_accounts;
        $event->class->filters['names']['de_accounts'] = trans_choice('double-entry::general.chart_of_accounts', 1);
    }

    /**
     * Handle filter applying event.
     *
     * @param  $event
     * @return void
     */
    public function handleFilterApplying(FilterApplying $event)
    {
        $reports = [
            'App\Reports\IncomeSummary',
            'App\Reports\ExpenseSummary',
            'App\Reports\IncomeExpenseSummary',
        ];

        if ($this->isEventApplicableToReports($event, $reports)) {
            return;
        }

        $de_account_id = $this->getSearchStringValue('de_account_id');

        if (empty($de_account_id)) {
            return;
        }

        try {
            $event->model->where(function ($query) use ($de_account_id) {
                return $query->whereHas('de_ledger', function ($query) use ($de_account_id) {
                    $query->where('account_id', $de_account_id);
                })->orWhereHas('items.de_ledger', function ($query) use ($de_account_id) {
                    $query->where('account_id', $de_account_id);
                })->orWhereHas('item_taxes.de_ledger', function ($query) use ($de_account_id) {
                    $query->where('account_id', $de_account_id);
                })->orWhereHas('totals.de_ledger', function ($query) use ($de_account_id) {
                    $query->where('account_id', $de_account_id);
                });
            });
        } catch (\Throwable $th) {
            return;
        }
    }

    /**
     * Handle group showing event.
     *
     * @param  $event
     * @return void
     */
    public function handleGroupShowing(GroupShowing $event)
    {
        $reports = [
            'App\Reports\IncomeSummary',
            'App\Reports\ExpenseSummary',
            'App\Reports\IncomeExpenseSummary',
            'App\Reports\ProfitLoss',
        ];

        if ($this->isEventApplicableToReports($event, $reports)) {
            return;
        }

        $event->class->groups['de_account'] = trans_choice('double-entry::general.chart_of_accounts', 1);
    }

    /**
     * Handle group applying event.
     *
     * @param  $event
     * @return void
     */
    public function handleGroupApplying(GroupApplying $event)
    {
        if (
            empty($event->class)
            || empty($event->class->model->settings->group)
            || ($event->class->model->settings->group != 'de_account')
        ) {
            return;
        }

        $reports = [
            'App\Reports\IncomeSummary',
            'App\Reports\ExpenseSummary',
            'App\Reports\IncomeExpenseSummary',
            'App\Reports\ProfitLoss',
        ];

        if ($this->isEventApplicableToReports($event, $reports)) {
            return;
        }

        switch ($event->model->getTable()) {
            case 'documents':
                $items = $event->model->items()->get()->merge($event->model->totals()->code('discount')->get());
                $event->model->type = $event->model->type == 'bill' ? 'expense' : 'income';

                break;
            case 'transactions' && !is_null($event->model->document_id):
                $items = $event->model->document->items()->get()->merge($event->model->document->totals()->code('discount')->get());

                break;
            case 'transactions' && is_null($event->model->document_id):
            case 'double_entry_journals':
                $items = collect([$event->model]);

                break;
            default:
                $items = collect([]);

                break;
        }

        if ($items->isEmpty()) {
            return;
        }

        $items->each(function ($item) use (&$event) {
            $item->report_table = 'default';

            if (get_class($event->class) == 'App\Reports\ProfitLoss') {
                $item->type = $event->model->type;

                if ($item instanceof DocumentTotal) {
                    $item->type = $item->type == 'bill' ? 'expense' : 'income';
                }

                $item->report_table = ($item->type == 'income') ? trans_choice('general.incomes', 1) : trans_choice('general.expenses', 2);
            }
        });

        $filter = $this->getSearchStringValue('de_account_id');

        foreach ($items as $item) {
            $model = $item->de_ledger();

            if (!empty($filter)) {
                $model->where('account_id', $filter);
            }

            $ledgers = $model->with('account.type.declass')->get();

            if ($ledgers->isEmpty()) {
                continue;
            }

            foreach ($ledgers as $ledger) {
                if (!empty($event->model->parent_id) && isset($event->model->issued_at)) {
                    $ledger->issued_at = $event->model->issued_at->toDateTimeString();
                }
                
                if (!empty($event->model->parent_id) && isset($event->model->paid_at)) {
                    $ledger->issued_at = $event->model->paid_at->toDateTimeString();
                }

                $this->setTotals($event, $ledger, $item->type, $item->report_table);
            }
        }
    }

    public function setTotals($event, $ledger, $type, $table)
    {
        $date = $this->getFormattedDate($event, Date::parse($ledger->issued_at));

        if (
            !isset($event->class->row_values[$table][$ledger->account_id])
            || !isset($event->class->row_values[$table][$ledger->account_id][$date])
            || !isset($event->class->footer_totals[$table][$date])
        ) {
            return;
        }

        $amount = !empty($ledger->debit) ? $ledger->debit : $ledger->credit;

        if (empty($amount)) {
            return;
        }

        if ($event->class instanceof IncomeExpenseSummary && $ledger->account->type->declass->name == 'double-entry::classes.expenses' && $ledger->debit) {
            $amount = $amount * -1;
        }

        if (($event->class instanceof ExpenseSummary || $event->class instanceof ProfitLoss) && $ledger->account->type->declass->name == 'double-entry::classes.expenses' && $ledger->credit) {
            $amount = $amount * -1;
        }

        if ($ledger->account->type->declass->name == 'double-entry::classes.income' && $ledger->debit) {
            $amount = $amount * -1;
        }

        if (($table == 'default' || $type == 'income')) {
            $event->class->row_values[$table][$ledger->account_id][$date] += $amount;

            $event->class->footer_totals[$table][$date] += $amount;
        } else {
            $event->class->row_values[$table][$ledger->account_id][$date] -= $amount;

            $event->class->footer_totals[$table][$date] -= $amount;
        }
    }

    /**
     * Handle records showing event.
     *
     * @param  $event
     * @return void
     */
    public function handleRowsShowing(RowsShowing $event)
    {
        if (
            empty($event->class)
            || empty($event->class->model->settings->group)
            || ($event->class->model->settings->group != 'de_account')
        ) {
            return;
        }

        switch (get_class($event->class)) {
            case 'App\Reports\ProfitLoss':
                $types = [11, 12, 13, 14, 15];

                $de_accounts = Coa::inType($types)->get()->transform(function ($account, $key) {
                    $account->name = trans($account->name);

                    return $account;
                });

                $rows = $de_accounts->sortBy('name')->pluck('name', 'id')->toArray();

                $this->setRowNamesAndValuesForProfitLoss($event, $rows, $de_accounts);

                break;
            case 'App\Reports\IncomeSummary':
            case 'App\Reports\ExpenseSummary':
            case 'App\Reports\IncomeExpenseSummary':
                $rows = $event->class->filters['de_accounts'];

                $this->setRowNamesAndValues($event, $rows);

                break;
        }
    }

    public function setRowNamesAndValuesForProfitLoss($event, $rows, $de_accounts)
    {
        $type_accounts = [
            'income' => [13, 14, 15],
            'expense' => [11, 12],
        ];

        foreach ($event->class->dates as $date) {
            foreach ($event->class->tables as $type_id => $type_name) {
                foreach ($rows as $id => $name) {
                    $de_account = $de_accounts->where('id', $id)->first();

                    if (!in_array($de_account->type_id, $type_accounts[$type_id])) {
                        continue;
                    }

                    $event->class->row_names[$type_name][$id] = $name;
                    $event->class->row_values[$type_name][$id][$date] = 0;
                }
            }
        }
    }

    protected function isEventApplicableToReports($event, $reports)
    {
        return empty($event->class) || !in_array(get_class($event->class), $reports);
    }
}

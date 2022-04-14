<?php

namespace Modules\DoubleEntry\Reports;

use App\Abstracts\Report;
use Modules\DoubleEntry\Models\DEClass;

class TrialBalance extends Report
{
    public $default_name = 'double-entry::general.trial_balance';

    public $category = 'general.accounting';

    public $icon = 'fa fa-balance-scale';

    public $indents = [
        'table_header' => '0px',
        'table_rows' => '48px',
    ];

    public function getGrandTotal()
    {
        if (!$this->loaded) {
            $this->load();
        }

        $total = (double) $this->footer_totals['debit'] - (double) $this->footer_totals['credit'];
        $total = money($total, setting('default.currency'), true)->format();

        return $total;
    }

    public function setViews()
    {
        parent::setViews();
        $this->views['content.header'] = 'double-entry::trial_balance.content.header';
        $this->views['content.footer'] = 'double-entry::trial_balance.content.footer';
        $this->views['table'] = 'double-entry::trial_balance.table';
        $this->views['table.header'] = 'double-entry::trial_balance.table.header';
        $this->views['table.footer'] = 'double-entry::trial_balance.table.footer';
        $this->views['table.rows'] = 'double-entry::trial_balance.table.rows';
    }

    public function setTables()
    {
        $model = $this->applyFilters(DEClass::query());

        $this->de_classes = $model->get()->transform(function ($class) {
            $class->name = trans($class->name);

            return $class;
        });

        $arr = $this->de_classes->pluck('name')->toArray();

        $this->tables = array_combine($arr, $arr);
    }

    public function setDates()
    {
        $this->footer_totals['debit'] = 0;
        $this->footer_totals['credit'] = 0;
    }

    public function setData()
    {
        $report_at = $this->getSearchStringValue('report_at');

        if (!empty($report_at) && is_array($report_at)) {
            $start_date = $report_at[0] . ' 00:00:00';
            $end_date = $report_at[1] . ' 23:59:59';
        }

        if (!empty($report_at) && !is_array($report_at)) {
            $start_date = $report_at . ' 00:00:00';
            $end_date = $report_at . ' 23:59:59';
        }

        $basis = $this->getSearchStringValue('basis');

        foreach ($this->de_classes as $class) {
            $this->row_values[$class->name] = [];

            foreach ($class->accounts as $account) {
                $this->row_names[$class->name][$account->id] = $account->name_linked_general_ledger;

                if (!empty($report_at)) {
                    $account->start_date = $start_date;
                    $account->end_date = $end_date;
                }

                if (!empty($basis)) {
                    $account->basis = $basis;
                }

                $total = $account->balance_without_subaccounts + $account->opening_balance;

                if (empty($total)) {
                    continue;
                }

                if ($total < 0) {
                    $debit_total = 0;
                    $credit_total = abs($total);
                } else {
                    $debit_total = abs($total);
                    $credit_total = 0;
                }

                $this->row_values[$class->name][$account->id]['debit'] = $debit_total;
                $this->row_values[$class->name][$account->id]['credit'] = $credit_total;

                $this->footer_totals['debit'] += $debit_total;
                $this->footer_totals['credit'] += $credit_total;
            }
        }
    }

    public function getFields()
    {
        return [];
    }
}

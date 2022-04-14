<?php

namespace Modules\DoubleEntry\Reports;

use App\Abstracts\Report;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\DEClass;
use Modules\DoubleEntry\Models\Journal;

class BalanceSheet extends Report
{
    public $default_name = 'double-entry::general.balance_sheet';

    public $category = 'general.accounting';

    public $icon = 'fa fa-balance-scale';

    public $total_liabilities_equity = 0;

    public function getGrandTotal()
    {
        return trans('general.na');
    }

    public function setViews()
    {
        parent::setViews();
        $this->views['content'] = 'double-entry::balance_sheet.content';
        $this->views['header'] = 'double-entry::balance_sheet.header';
    }

    public function setData()
    {
        $accounts = [];
        $liabilities = 0;

        $report_at = $this->getSearchStringValue('report_at');

        if (empty($report_at)) {
            $financial_year = $this->getFinancialYear();

            $start_date = $financial_year->getStartDate();
            $end_date = $financial_year->getEndDate();
        }

        if (!empty($report_at) && is_array($report_at)) {
            $start_date = $report_at[0] . ' 00:00:00';
            $end_date = $report_at[1] . ' 23:59:59';
        }

        if (!empty($report_at) && !is_array($report_at)) {
            $start_date = $report_at . ' 00:00:00';
            $end_date = $report_at . ' 23:59:59';
        }

        $basis = $this->getSearchStringValue('basis', 'accrual');

        $classes = DEClass::whereNotIn('name', ['double-entry::classes.income', 'double-entry::classes.expenses'])
            ->with(['types', 'types.accounts' => function ($query) use ($start_date, $end_date) {
                $query->whereHas('ledgers', function ($query) use ($start_date, $end_date) {
                    $query->whereBetween('issued_at', [$start_date, $end_date]);
                });
            }])->get();

        foreach ($classes as $class) {
            $class->total = 0;

            foreach ($class->types as $type) {
                $type->total = 0;

                if ($type->name == 'double-entry::types.equity') {
                    $account = $this->calculateCurrentYearEarnings($start_date, $end_date, $basis);
                    $accounts[$type->id][] = $account;
                    $type->total += $account->de_balance;
                    $class->total += $account->de_balance;
                }

                foreach ($type->accounts as $account) {
                    $opening_balance = $account->opening_balance;
                    $account->start_date = $start_date;
                    $account->end_date = $end_date;
                    $account->basis = $basis;
                    $balance = $opening_balance + $account->balance_without_subaccounts;

                    if (
                        $type->name == 'double-entry::types.equity' ||
                        $class->name == 'double-entry::classes.liabilities'
                    ) {
                        $balance = $balance * -1;
                    }

                    $account->de_balance = $balance;
                    $type->total += $balance;
                    $class->total += $balance;

                    $accounts[$type->id][] = $account;
                }
            }

            if ($class->name == 'double-entry::classes.liabilities') {
                $liabilities = $class->total;
            }

            if ($class->name == 'double-entry::classes.equity') {
                $this->total_liabilities_equity = $liabilities + $class->total;
            }
        }

        $this->de_classes = $classes;
        $this->de_accounts = $accounts;
    }

    public function getFields()
    {
        return [];
    }

    protected function calculateCurrentYearEarnings($start_date, $end_date, $basis)
    {
        $income = DEClass::where('name', 'double-entry::classes.income')
            ->first()
            ->accounts()
            ->whereHas('ledgers', function ($query) use ($start_date, $end_date, $basis) {
                $query->whereBetween('issued_at', [$start_date, $end_date]);

                if (isset($basis) && $basis == 'cash') {
                    $query->where(function ($query) use ($basis) {
                        $query->where('ledgerable_type', Transaction::class)
                            ->OrWhereHasMorph('ledgerable', [
                                Journal::class,
                            ], function ($query) use ($basis) {
                                $query->where('basis', $basis);
                            });
                    });
                }
            })
            ->get()
            ->sum(function ($account) {
                return $account->balance_without_subaccounts + $account->opening_balance;
            });

        $expense = DEClass::where('name', 'double-entry::classes.expenses')
            ->first()
            ->accounts()
            ->whereHas('ledgers', function ($query) use ($start_date, $end_date) {
                $query->whereBetween('issued_at', [$start_date, $end_date]);

                if (isset($basis) && $basis == 'cash') {
                    $query->where(function ($query) use ($basis) {
                        $query->where('ledgerable_type', Transaction::class)
                            ->OrWhereHasMorph('ledgerable', [
                                Journal::class,
                            ], function ($query) use ($basis) {
                                $query->where('basis', $basis);
                            });
                    });
                }
            })
            ->get()
            ->sum(function ($account) {
                return $account->balance_without_subaccounts + $account->opening_balance;
            });

        $earning = new Account();
        $earning->name = trans('double-entry::general.current_year_earnings');
        $earning->de_balance = abs($income) - $expense;

        return $earning;
    }
}

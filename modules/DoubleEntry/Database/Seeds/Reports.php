<?php

namespace Modules\DoubleEntry\Database\Seeds;

use App\Abstracts\Model;
use App\Models\Common\Report;
use Illuminate\Database\Seeder;

class Reports extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->create();

        Model::reguard();
    }

    /**
     * Creates pre-defined reports for the chart of accounts.
     *
     * @return void
     */
    private function create()
    {
        $company_id = $this->command->argument('company');

        $rows = [
            [
                [
                    'company_id' => $company_id,
                    'class' => 'Modules\DoubleEntry\Reports\GeneralLedger',
                    'name' => trans('double-entry::general.general_ledger'),
                    'description' => trans('double-entry::demo.reports.description.general_ledger'),
                ],
                [
                    'settings' => ['basis' => 'accrual'],
                ],
            ],
            [
                [
                    'company_id' => $company_id,
                    'class' => 'Modules\DoubleEntry\Reports\JournalReport',
                    'name' => trans_choice('double-entry::general.journals', 1) . ' ' . trans_choice('double-entry::general.entries', 2),
                    'description' => trans('double-entry::demo.reports.description.journal_report'),
                ],
                [
                    'settings' => ['basis' => 'accrual'],
                ],
            ],
            [
                [
                    'company_id' => $company_id,
                    'class' => 'Modules\DoubleEntry\Reports\BalanceSheet',
                    'name' => trans('double-entry::general.balance_sheet'),
                    'description' => trans('double-entry::demo.reports.description.balance_sheet'),
                ],
                [
                    'settings' => ['basis' => 'accrual'],
                ],
            ],
            [
                [
                    'company_id' => $company_id,
                    'class' => 'Modules\DoubleEntry\Reports\TrialBalance',
                    'name' => trans('double-entry::general.trial_balance'),
                    'description' => trans('double-entry::demo.reports.description.trial_balance'),
                ],
                [
                    'settings' => ['basis' => 'accrual'],
                ],
            ],
            [
                [
                    'company_id' => $company_id,
                    'class' => 'App\Reports\IncomeSummary',
                    'name' => trans('double-entry::demo.reports.name.income_summary'),
                    'description' => trans('double-entry::demo.reports.description.income_summary'),
                ],
                [
                    'settings' => ['group' => 'de_account', 'period' => 'monthly', 'basis' => 'accrual', 'chart' => 'line'],
                ],
            ],
            [
                [
                    'company_id' => $company_id,
                    'class' => 'App\Reports\ExpenseSummary',
                    'name' => trans('double-entry::demo.reports.name.expense_summary'),
                    'description' => trans('double-entry::demo.reports.description.expense_summary'),
                ],
                [
                    'settings' => ['group' => 'de_account', 'period' => 'monthly', 'basis' => 'accrual', 'chart' => 'line'],
                ],
            ],
            [
                [
                    'company_id' => $company_id,
                    'class' => 'App\Reports\IncomeExpenseSummary',
                    'name' => trans('double-entry::demo.reports.name.income_expense'),
                    'description' => trans('double-entry::demo.reports.description.income_expense'),
                ],
                [
                    'settings' => ['group' => 'de_account', 'period' => 'monthly', 'basis' => 'accrual', 'chart' => 'line'],
                ],
            ],
            [
                [
                    'company_id' => $company_id,
                    'class' => 'App\Reports\ProfitLoss',
                    'name' => trans('double-entry::demo.reports.name.profit_loss'),
                    'description' => trans('double-entry::demo.reports.description.profit_loss'),
                ],
                [
                    'settings' => ['group' => 'de_account', 'period' => 'quarterly', 'basis' => 'accrual'],
                ],
            ],
        ];

        foreach ($rows as $row) {
            $row[1]['created_from'] = 'double-entry::seed';

            Report::firstOrCreate($row[0], $row[1]);
        }
    }
}

<?php

namespace Modules\DoubleEntry\Traits;

use App\Traits\SearchString;
use App\Utilities\Date;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Type;

trait Accounts
{
    use SearchString;

    public function countSettings($account)
    {
        $counter = [];

        $settings = [
            'double-entry.accounts_receivable',
            'double-entry.accounts_payable',
            'double-entry.accounts_sales',
            'double-entry.accounts_expenses',
            'double-entry.accounts_sales_discount',
            'double-entry.accounts_purchase_discount',
            'double-entry.accounts_owners_contribution',
        ];

        foreach ($settings as $setting) {
            if ($account->code != setting($setting)) {
                continue;
            }

            $counter[] = strtolower(trans_choice('general.settings', 2));
        }

        return $counter;
    }

    public function updateSettings($old_code, $new_code)
    {
        $settings = [
            'double-entry.accounts_receivable',
            'double-entry.accounts_payable',
            'double-entry.accounts_sales',
            'double-entry.accounts_expenses',
            'double-entry.accounts_sales_discount',
            'double-entry.accounts_purchase_discount',
            'double-entry.accounts_owners_contribution',
        ];

        foreach ($settings as $setting) {
            if ($old_code == setting($setting)) {
                setting()->set($setting, $new_code);
            }
        }

        setting()->save();
    }

    /**
     *
     * Finding account id by searching on id, code and name
     *
     * @param mixed $field
     * @return integer|null $account_id
     */
    public function findImportedAccountId($field)
    {
        $account = Account::where('id', '=', $field)
            ->orWhere('code', '=', $field)
            ->orWhere('name', '=', $field)
            ->first();

        if (!is_null($account)) {
            return $account->id;
        }

        $account = Account::all()->first(function ($account) use ($field) {
            return $account->trans_name == $field;
        });

        if (!is_null($account)) {
            return $account->id;
        }

        return null;
    }

    /**
     *
     * Finding type id by searching on name
     *
     * @param mixed $field
     * @return integer|null $type_id
     */
    public function findImportedTypeId($field)
    {
        $type = Type::all()->first(function ($type) use ($field) {
            return trans($type->name) == $field;
        });

        if (!is_null($type)) {
            return $type->id;
        }

        return null;
    }

    /**
     * Marks first and last item of a class in a accounts collection.
     *
     * @param Collection $accounts
     * @return Collection
     */
    public function markStartingEndingFlags(Collection $accounts)
    {
        for ($i = 0; $i < $accounts->count(); $i++) {
            if ($i == 0) {
                $accounts[$i]->is_first_item_of_class = true;
                $accounts[$i]->is_last_item_of_class = false;

                continue;
            }

            if (($i + 1) == $accounts->count()) {
                $accounts[$i]->is_first_item_of_class = false;
                $accounts[$i]->is_last_item_of_class = true;

                continue;
            }

            if ($accounts[$i]->declass->name != $accounts[$i + 1]->declass->name) {
                $accounts[$i]->is_first_item_of_class = false;
                $accounts[$i]->is_last_item_of_class = true;
                $accounts[$i + 1]->is_first_item_of_class = true;
                $accounts[$i + 1]->is_last_item_of_class = false;
                $i++;

                continue;
            }

            $accounts[$i]->is_first_item_of_class = false;
            $accounts[$i]->is_last_item_of_class = false;
        }

        return $accounts;
    }

    /**
     * Finds existing maximum code and increase it
     *
     * @return mixed
     */
    public function getNextAccountCode()
    {
        return Account::isNotSubAccount()->get(['code'])->reject(function ($account) {
            return !preg_match('/^[0-9]*$/', $account->code);
        })->max('code') + 1;
    }

    /**
     * Gets the class of color considering given digit.
     *
     * @param double $digit
     * @param null|string $location
     * @return null|string
     */
    public function getDigitColor($digit, $location = null)
    {
        if ($digit == 0 && $location == null) {
            return 'text-primary';
        }

        if ($digit == 0 && $location == 'table') {
            return null;
        }

        if ($digit > 0) {
            return 'text-info';
        }

        if ($digit < 0) {
            return 'text-danger';
        }
    }

    /**
     * Calculates the balance of an account.
     *
     * @param bool $with_subaccounts
     * @return double
     */
    public function calculateBalance($with_subaccounts = true)
    {
        $total_debit = $total_credit = $balance = 0;

        $this->setDateInterval();

        $this->setBasis();

        $this->ledgers()
            ->{$this->basis}()
            ->whereBetween('issued_at', [$this->start_date, $this->end_date])
            ->with(['ledgerable'])
            ->each(function ($ledger) use (&$total_debit, &$total_credit) {
                $ledger->castDebit();
                $ledger->castCredit();

                $total_debit += $ledger->debit;
                $total_credit += $ledger->credit;
            });

        $balance = $total_debit - $total_credit;

        if ($with_subaccounts) {
            $this->sub_accounts()
                ->each(function ($sub_account) use (&$balance) {
                    $balance += $sub_account->balance;
                });
        }

        return $balance;
    }

    /**
     * Gets the balance of an account with colorized.
     *
     * @param bool $with_subaccounts
     * @return string|\Akaunting\Money\Money
     */
    public function getBalanceColorized($with_subaccounts = true)
    {
        $balance = $this->calculateBalance($with_subaccounts);

        $class = $this->getDigitColor($balance, 'table');

        if (is_null($class)) {
            return money($balance, setting('default.currency'), true);
        }

        return Str::of('<span class="' . $class . '">')
            ->append(money($balance, setting('default.currency'), true))
            ->append('</span>');
    }

    protected function setDateInterval()
    {
        if (isset($this->start_date) && isset($this->end_date)) {
            return;
        }

        $report_at = $this->getSearchStringValue('report_at');

        if (empty($report_at)) {
            $financial_year = $this->getFinancialYear();

            $this->start_date = $financial_year->getStartDate();
            $this->end_date = $financial_year->getEndDate();
        }

        if (!empty($report_at) && is_array($report_at)) {
            $this->start_date = $report_at[0] . ' 00:00:00';
            $this->end_date = $report_at[1] . ' 23:59:59';
        }

        if (!empty($report_at) && !is_array($report_at)) {
            $this->start_date = $report_at . ' 00:00:00';
            $this->end_date = $report_at . ' 23:59:59';
        }
    }

    public function setOpeningBalanceDates()
    {
        // 1970-01-01 00:00:00
        $this->start_date = Date::createFromTimestamp(0)->startOfDay()->toDateTimeString();

        $report_at = $this->getSearchStringValue('report_at');

        if (empty($report_at)) {
            $this->end_date = $this->getFinancialStart()->subDay()->endOfDay();

            return;
        }

        if (!empty($report_at) && is_array($report_at)) {
            $this->end_date = $report_at[0];
        }

        if (!empty($report_at) && !is_array($report_at)) {
            $this->end_date = $report_at;
        }

        $this->end_date = Date::createFromFormat('Y-m-d', $this->end_date);

        $this->end_date = $this->end_date->subDay()->endOfDay();
    }

    protected function setBasis()
    {
        if (isset($this->basis)) {
            return;
        }

        $this->basis = $this->getSearchStringValue('basis', 'accrual');
    }
}

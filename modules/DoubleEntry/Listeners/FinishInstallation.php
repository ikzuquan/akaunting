<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Module\Installed as Event;
use App\Models\Banking\Account;
use App\Models\Banking\Transaction;
use App\Models\Banking\Transfer;
use App\Models\Document\Document;
use App\Models\Setting\Tax;
use App\Traits\Documents;
use App\Traits\Jobs;
use Illuminate\Support\Facades\Artisan;
use Modules\DoubleEntry\Jobs\Journal\CreateJournalEntry;
use Modules\DoubleEntry\Models\Account as Coa;
use Modules\DoubleEntry\Models\AccountBank;
use Modules\DoubleEntry\Models\AccountTax;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\Ledger;
use Modules\DoubleEntry\Traits\Accounts;

class FinishInstallation
{
    use Accounts, Jobs, Documents;

    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if ($event->alias != 'double-entry') {
            return;
        }

        $this->callSeeds();

        $this->copyData();
    }

    protected function callSeeds()
    {
        Artisan::call('company:seed', [
            'company' => company_id(),
            '--class' => 'Modules\DoubleEntry\Database\Seeds\Install',
        ]);
    }

    protected function copyData()
    {
        $this->copyAccounts();
        $this->copyTransfers();
        $this->copyTaxes();
        $this->copyInvoices();
        $this->copyIncomeTransactions();
        $this->copyBills();
        $this->copyExpenseTransactions();
    }

    /**
     * Copy existing banking accounts to the chart of accounts.
     *
     * @return void
     */
    protected function copyAccounts()
    {
        foreach (Account::lazy() as $account) {
            $account_bank = AccountBank::where('bank_id', $account->id)->first();

            if (is_null($account_bank)) {
                $this->createBankAccount($account);

                continue;
            }

            $account_bank->account->update([
                'name' => $account->name,
                'enabled' => $account->enabled,
            ]);
        }
    }

    /**
     * Copy existing transfers to the journals.
     *
     * @return void
     */
    protected function copyTransfers()
    {
        Transfer::cursor()->each(function ($transfer) {
            $expense_transaction = $transfer->expense_transaction;
            $income_transaction = $transfer->income_transaction;

            $expense_transaction_account_id = AccountBank::where('bank_id', $expense_transaction->account_id)->pluck('account_id')->first();
            $income_transaction_account_id = AccountBank::where('bank_id', $income_transaction->account_id)->pluck('account_id')->first();

            if (empty($expense_transaction_account_id) || empty($income_transaction_account_id)) {
                return;
            }

            $journal = Journal::firstOrCreate([
                'company_id' => $transfer->company_id,
                'amount' => $expense_transaction->amount,
                'paid_at' => $expense_transaction->paid_at,
                'description' => $expense_transaction->description ?: '...',
                'reference' => 'transfer:' . $transfer->id,
            ], [
                'journal_number' => $this->getNextDocumentNumber('double-entry.journal'),
            ]);

            $l1 = $journal->ledger()->firstOrCreate([
                'company_id' => $transfer->company_id,
                'account_id' => $expense_transaction_account_id,
                'issued_at' => $journal->paid_at,
                'entry_type' => 'item',
                'credit' => $journal->amount,
            ]);

            $expense_transaction->reference = 'journal-entry-ledger:' . $l1->id;
            $expense_transaction->save();

            $l2 = $journal->ledger()->firstOrCreate([
                'company_id' => $transfer->company_id,
                'account_id' => $income_transaction_account_id,
                'issued_at' => $journal->paid_at,
                'entry_type' => 'item',
                'debit' => $journal->amount,
            ]);

            $income_transaction->reference = 'journal-entry-ledger:' . $l2->id;
            $income_transaction->save();
        });
    }

    /**
     * Copy existing taxes to the chart of accounts.
     *
     * @return void
     */
    protected function copyTaxes()
    {
        foreach (Tax::lazy() as $tax) {
            $account_tax = AccountTax::where('tax_id', $tax->id)->first();

            if (is_null($account_tax)) {
                $chart_of_account = Coa::create([
                    'company_id' => company_id(),
                    'type_id' => setting('double-entry.types_tax', 17),
                    'code' => $this->getNextAccountCode(),
                    'name' => $tax->name,
                    'enabled' => 1,
                ]);

                $chart_of_account->tax()->create([
                    'company_id' => company_id(),
                    'account_id' => $chart_of_account->id,
                    'tax_id' => $tax->id,
                ]);

                continue;
            }

            $account_tax->account->update([
                'name' => $tax->name,
                'enabled' => $tax->enabled,
            ]);
        }
    }

    /**
     * Copy existing invoices to the ledgers.
     *
     * @return void
     */
    protected function copyInvoices()
    {
        Document::invoice()->with(['items', 'item_taxes', 'transactions'])->cursor()->each(function ($invoice) {
            $accounts_receivable_id = Coa::code(setting('double-entry.accounts_receivable', 120))->pluck('id')->first();

            Ledger::firstOrCreate([
                'company_id' => company_id(),
                'account_id' => $accounts_receivable_id,
                'ledgerable_id' => $invoice->id,
                'ledgerable_type' => get_class($invoice),
                'issued_at' => $invoice->issued_at,
                'entry_type' => 'total',
                'debit' => $invoice->amount,
            ]);

            $invoice->items()->each(function ($item) use ($invoice) {
                $account_id = Coa::code(setting('double-entry.accounts_sales', 400))->pluck('id')->first();

                Ledger::firstOrCreate([
                    'company_id' => company_id(),
                    'account_id' => $account_id,
                    'ledgerable_id' => $item->id,
                    'ledgerable_type' => get_class($item),
                    'issued_at' => $invoice->issued_at,
                    'entry_type' => 'item',
                    'credit' => $item->total,
                ]);
            });

            $invoice->item_taxes()->each(function ($item_tax) use ($invoice) {
                $account_id = AccountTax::where('tax_id', $item_tax->tax_id)->pluck('account_id')->first();

                Ledger::firstOrCreate([
                    'company_id' => company_id(),
                    'account_id' => $account_id,
                    'ledgerable_id' => $item_tax->id,
                    'ledgerable_type' => get_class($item_tax),
                    'issued_at' => $invoice->issued_at,
                    'entry_type' => 'item',
                    'credit' => $item_tax->amount,
                ]);
            });

            $invoice->transactions()->each(function ($transaction) use ($accounts_receivable_id) {
                $account_id = AccountBank::where('bank_id', $transaction->account_id)->pluck('account_id')->first();

                if (is_null($account_id)) {
                    $account = $this->createBankAccount($transaction->account);

                    $account_id = $account->id;
                }

                Ledger::firstOrCreate([
                    'company_id' => company_id(),
                    'account_id' => $account_id,
                    'ledgerable_id' => $transaction->id,
                    'ledgerable_type' => get_class($transaction),
                    'issued_at' => $transaction->paid_at,
                    'entry_type' => 'total',
                    'debit' => $transaction->amount,
                ]);

                Ledger::firstOrCreate([
                    'company_id' => company_id(),
                    'account_id' => $accounts_receivable_id,
                    'ledgerable_id' => $transaction->id,
                    'ledgerable_type' => get_class($transaction),
                    'issued_at' => $transaction->paid_at,
                    'entry_type' => 'item',
                    'credit' => $transaction->amount,
                ]);
            });

        });
    }

    /**
     * Copy existing transactions that type's are income to the ledgers.
     *
     * @return void
     */
    protected function copyIncomeTransactions()
    {
        Transaction::type('income')->isNotDocument()->isNotTransfer()->cursor()->each(function ($transaction) {
            $account_id = AccountBank::where('bank_id', $transaction->account_id)->pluck('account_id')->first();

            if (is_null($account_id)) {
                $account = $this->createBankAccount($transaction->account);

                $account_id = $account->id;
            }

            Ledger::firstOrCreate([
                'company_id' => company_id(),
                'account_id' => $account_id,
                'ledgerable_id' => $transaction->id,
                'ledgerable_type' => get_class($transaction),
                'issued_at' => $transaction->paid_at,
                'entry_type' => 'total',
                'debit' => $transaction->amount,
            ]);

            $account_id = Coa::code(setting('double-entry.accounts_sales', 400))->pluck('id')->first();

            Ledger::firstOrCreate([
                'company_id' => company_id(),
                'account_id' => $account_id,
                'ledgerable_id' => $transaction->id,
                'ledgerable_type' => get_class($transaction),
                'issued_at' => $transaction->paid_at,
                'entry_type' => 'item',
                'credit' => $transaction->amount,
            ]);
        });
    }

    /**
     * Copy existing bills to the ledgers.
     *
     * @return void
     */
    protected function copyBills()
    {
        Document::bill()->with(['items', 'item_taxes', 'transactions'])->cursor()->each(function ($bill) {
            $accounts_payable_id = Coa::code(setting('double-entry.accounts_payable', 200))->pluck('id')->first();

            Ledger::firstOrCreate([
                'company_id' => company_id(),
                'account_id' => $accounts_payable_id,
                'ledgerable_id' => $bill->id,
                'ledgerable_type' => get_class($bill),
                'issued_at' => $bill->issued_at,
                'entry_type' => 'total',
                'credit' => $bill->amount,
            ]);

            $bill->items()->each(function ($item) use ($bill) {
                $account_id = Coa::code(setting('double-entry.accounts_expenses', 628))->pluck('id')->first();

                Ledger::firstOrCreate([
                    'company_id' => company_id(),
                    'account_id' => $account_id,
                    'ledgerable_id' => $item->id,
                    'ledgerable_type' => get_class($item),
                    'issued_at' => $bill->issued_at,
                    'entry_type' => 'item',
                    'debit' => $item->total,
                ]);
            });

            $bill->item_taxes()->each(function ($item_tax) use ($bill) {
                $account_id = AccountTax::where('tax_id', $item_tax->tax_id)->pluck('account_id')->first();

                Ledger::firstOrCreate([
                    'company_id' => company_id(),
                    'account_id' => $account_id,
                    'ledgerable_id' => $item_tax->id,
                    'ledgerable_type' => get_class($item_tax),
                    'issued_at' => $bill->issued_at,
                    'entry_type' => 'item',
                    'debit' => $item_tax->amount,
                ]);
            });

            $bill->transactions()->each(function ($transaction) use ($accounts_payable_id) {
                $account_id = AccountBank::where('bank_id', $transaction->account_id)->pluck('account_id')->first();

                if (is_null($account_id)) {
                    $account = $this->createBankAccount($transaction->account);

                    $account_id = $account->id;
                }

                Ledger::firstOrCreate([
                    'company_id' => company_id(),
                    'account_id' => $account_id,
                    'ledgerable_id' => $transaction->id,
                    'ledgerable_type' => get_class($transaction),
                    'issued_at' => $transaction->paid_at,
                    'entry_type' => 'total',
                    'credit' => $transaction->amount,
                ]);

                Ledger::firstOrCreate([
                    'company_id' => company_id(),
                    'account_id' => $accounts_payable_id,
                    'ledgerable_id' => $transaction->id,
                    'ledgerable_type' => get_class($transaction),
                    'issued_at' => $transaction->paid_at,
                    'entry_type' => 'item',
                    'debit' => $transaction->amount,
                ]);
            });
        });
    }

    /**
     * Copy existing transactions that type's are expense to the ledgers.
     *
     * @return void
     */
    protected function copyExpenseTransactions()
    {
        Transaction::type('expense')->isNotDocument()->isNotTransfer()->cursor()->each(function ($transaction) {
            $account_id = AccountBank::where('bank_id', $transaction->account_id)->pluck('account_id')->first();

            if (is_null($account_id)) {
                $account = $this->createBankAccount($transaction->account);

                $account_id = $account->id;
            }

            Ledger::firstOrCreate([
                'company_id' => company_id(),
                'account_id' => $account_id,
                'ledgerable_id' => $transaction->id,
                'ledgerable_type' => get_class($transaction),
                'issued_at' => $transaction->paid_at,
                'entry_type' => 'total',
                'credit' => $transaction->amount,
            ]);

            $account_id = Coa::code(setting('double-entry.accounts_expenses', 628))->pluck('id')->first();

            Ledger::firstOrCreate([
                'company_id' => company_id(),
                'account_id' => $account_id,
                'ledgerable_id' => $transaction->id,
                'ledgerable_type' => get_class($transaction),
                'issued_at' => $transaction->paid_at,
                'entry_type' => 'item',
                'debit' => $transaction->amount,
            ]);
        });
    }

    /**
     * Creates a chart of account
     *
     * @param Account $account
     * @return Coa
     */
    protected function createBankAccount(Account $account)
    {
        $chart_of_account = Coa::create([
            'company_id' => company_id(),
            'type_id' => setting('double-entry.types_bank', 6),
            'code' => $this->getNextAccountCode(),
            'name' => $account->name,
            'enabled' => 1,
        ]);

        $chart_of_account->bank()->create([
            'company_id' => company_id(),
            'account_id' => $chart_of_account->id,
            'bank_id' => $account->id,
        ]);

        if ($account->opening_balance <= 0) {
            return $chart_of_account;
        }

        $owner_contribution = Coa::code(setting('double-entry.accounts_owners_contribution'))->first();

        if (is_null($owner_contribution)) {
            return $chart_of_account;
        }

        $request = [
            'company_id' => $account->company_id,
            'paid_at' => $account->created_at,
            'description' => trans('accounts.opening_balance') . ';' . $account->name,
            'reference' => 'opening-balance:' . $chart_of_account->id,
            'items' => [
                ['account_id' => $chart_of_account->id, 'debit' => $account->opening_balance],
                ['account_id' => $owner_contribution->id, 'credit' => $account->opening_balance],
            ],
        ];

        $this->dispatch(new CreateJournalEntry($request));

        return $chart_of_account;
    }
}

<?php

namespace Modules\DoubleEntry\Observers\Document;

use App\Abstracts\Observer;
use App\Models\Document\Document;
use App\Models\Document\DocumentItem as Model;
use App\Traits\Jobs;
use App\Traits\Modules;
use Modules\CreditDebitNotes\Models\CreditNote;
use Modules\CreditDebitNotes\Models\DebitNote;
use Modules\DoubleEntry\Jobs\Ledger\CreateLedger;
use Modules\DoubleEntry\Jobs\Ledger\DeleteLedger;
use Modules\DoubleEntry\Models\Account as Coa;
use Modules\DoubleEntry\Models\Ledger;
use Modules\DoubleEntry\Traits\Accounts;
use Modules\DoubleEntry\Traits\Permissions;

class DocumentItem extends Observer
{
    use Accounts, Jobs, Permissions, Modules;

    /**
     * Listen to the created event.
     *
     * @param  Model  $document_item
     * @return void
     */
    public function created(Model $document_item)
    {
        if ($this->skipEvent($document_item)) {
            return;
        }

        $request = $this->getDocumentItemBaseRequest($document_item);

        $request = $this->appendDocumentItemSpecificFields($request, $document_item);

        $this->dispatch(new CreateLedger($request));
    }

    /**
     * Listen to the deleted event.
     *
     * @param  Model  $document_item
     * @return void
     */
    public function deleted(Model $document_item)
    {
        if ($this->skipEvent($document_item)) {
            return;
        }

        $ledger = Ledger::record($document_item->id, get_class($document_item))->first();

        if (is_null($ledger)) {
            return;
        }

        $this->dispatch(new DeleteLedger($ledger));
    }

    /**
     * Gets the basic parameters for the document item request.
     *
     * @param Model $document_item
     * @return array
     */
    private function getDocumentItemBaseRequest($document_item)
    {
        return [
            'company_id' => $document_item->company_id,
            'ledgerable_id' => $document_item->id,
            'ledgerable_type' => get_class($document_item),
            'issued_at' => $document_item->document->issued_at,
            'entry_type' => 'item',
        ];
    }

    /**
     * Appends the document item specific parameters.
     *
     * @param array $request
     * @param Model $document_item
     * @return array
     */
    private function appendDocumentItemSpecificFields($request, $document_item)
    {
        $account_id = null;

        if (isset($document_item->allAttributes['chart_of_account'])) {
            $document_item->allAttributes['de_account_id'] = $this->findImportedAccountId($document_item->allAttributes['chart_of_account']);
        }

        if (isset($document_item->allAttributes['de_account_id'])) {
            $account_id = $document_item->allAttributes['de_account_id'];
        }

        $request['account_id'] = $account_id;

        if ($document_item->document->type == Document::INVOICE_TYPE) {
            $request['credit'] = $document_item->total;

            if (empty($account_id)) {
                $request['account_id'] = Coa::code(setting('double-entry.accounts_sales', 400))->pluck('id')->first();
            }
        }

        if ($document_item->document->type == Document::BILL_TYPE) {
            $request['debit'] = $document_item->total;

            if (empty($account_id)) {
                $request['account_id'] = Coa::code(setting('double-entry.accounts_expenses', 628))->pluck('id')->first();
            }
        }

        if ($this->moduleIsEnabled('credit-debit-notes') && $document_item->document->type == CreditNote::TYPE) {
            $request['debit'] = $document_item->total;

            if (empty($account_id)) {
                $request['account_id'] = Coa::code(setting('double-entry.accounts_sales', 400))->pluck('id')->first();
            }
        }

        if ($this->moduleIsEnabled('credit-debit-notes') && $document_item->document->type == DebitNote::TYPE) {
            $request['credit'] = $document_item->total;

            if (empty($account_id)) {
                $request['account_id'] = Coa::code(setting('double-entry.accounts_expenses', 628))->pluck('id')->first();
            }
        }

        return $request;
    }

    /**
     * Determines event will be continued or not.
     *
     * @param Model $document_item
     * @return bool
     */
    private function skipEvent(Model $document_item)
    {
        if (!$this->moduleIsEnabled('double-entry') ||
            $this->isNotValidDocumentType($document_item->document->type)) {
            return true;
        }

        return false;
    }
}

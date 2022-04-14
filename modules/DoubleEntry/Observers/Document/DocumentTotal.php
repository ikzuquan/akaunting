<?php

namespace Modules\DoubleEntry\Observers\Document;

use App\Abstracts\Observer;
use App\Models\Document\Document;
use App\Models\Document\DocumentTotal as Model;
use App\Traits\Jobs;
use Modules\CreditDebitNotes\Models\CreditNote;
use Modules\CreditDebitNotes\Models\DebitNote;
use Modules\DoubleEntry\Jobs\Ledger\CreateLedger;
use Modules\DoubleEntry\Jobs\Ledger\DeleteLedger;
use Modules\DoubleEntry\Jobs\Ledger\UpdateLedger;
use Modules\DoubleEntry\Models\Account as Coa;
use Modules\DoubleEntry\Models\Ledger;
use Modules\DoubleEntry\Traits\Permissions;

class DocumentTotal extends Observer
{
    use Jobs, Permissions;

    /**
     * Listen to the created event.
     *
     * @param  Model  $document_total
     * @return void
     */
    public function created(Model $document_total)
    {
        if ($this->skipEvent($document_total)) {
            return;
        }

        if ($document_total->code != 'discount') {
            return;
        }

        $request = $this->getDocumentTotalBaseRequest($document_total);

        $request = $this->appendDocumentTotalSpecificFields($request, $document_total);

        $this->dispatch(new CreateLedger($request));
    }

    /**
     * Listen to the updated event.
     *
     * @param  Model  $document_total
     * @return void
     */
    public function updated(Model $document_total)
    {
        if ($this->skipEvent($document_total)) {
            return;
        }

        $ledger = Ledger::record($document_total->id, get_class($document_total))->first();

        if (is_null($ledger)) {
            return;
        }

        $request = $this->getDocumentTotalBaseRequest($document_total);

        $request = $this->appendDocumentTotalSpecificFields($request, $document_total);

        $this->dispatch(new UpdateLedger($ledger, $request));
    }

    /**
     * Listen to the deleted event.
     *
     * @param  Model  $document_total
     * @return void
     */
    public function deleted(Model $document_total)
    {
        if ($this->skipEvent($document_total)) {
            return;
        }

        $ledger = Ledger::record($document_total->id, get_class($document_total))->first();

        if (is_null($ledger)) {
            return;
        }

        $this->dispatch(new DeleteLedger($ledger));
    }

    /**
     * Gets the basic parameters for the document total request.
     *
     * @param Model $document_total
     * @return array
     */
    private function getDocumentTotalBaseRequest($document_total)
    {
        return [
            'company_id' => $document_total->company_id,
            'ledgerable_id' => $document_total->id,
            'ledgerable_type' => get_class($document_total),
            'issued_at' => $document_total->document->issued_at,
            'entry_type' => 'discount',
        ];
    }

    /**
     * Appends the document total specific parameters.
     *
     * @param array $request
     * @param Model $document_total
     * @return array
     */
    private function appendDocumentTotalSpecificFields($request, $document_total)
    {
        if ($document_total->document->type == Document::INVOICE_TYPE) {
            $request['account_id'] = Coa::code(setting('double-entry.accounts_sales_discount', 825))->pluck('id')->first();
            $request['debit'] = $document_total->amount;
        }

        if ($document_total->document->type == Document::BILL_TYPE) {
            $request['account_id'] = Coa::code(setting('double-entry.accounts_purchase_discount', 475))->pluck('id')->first();
            $request['credit'] = $document_total->amount;
        }

        if ($this->moduleIsEnabled('credit-debit-notes') && $document_total->document->type == CreditNote::TYPE) {
            $request['account_id'] = Coa::code(setting('double-entry.accounts_sales_discount', 825))->pluck('id')->first();
            $request['credit'] = $document_total->amount;
        }

        if ($this->moduleIsEnabled('credit-debit-notes') && $document_total->document->type == DebitNote::TYPE) {
            $request['account_id'] = Coa::code(setting('double-entry.accounts_purchase_discount', 475))->pluck('id')->first();
            $request['debit'] = $document_total->amount;
        }

        return $request;
    }

    /**
     * Determines event will be continued or not.
     *
     * @param Model $document_total
     * @return bool
     */
    private function skipEvent(Model $document_total)
    {
        if (!$this->moduleIsEnabled('double-entry') ||
            $this->isNotValidDocumentType($document_total->document->type)) {
            return true;
        }

        return false;
    }
}

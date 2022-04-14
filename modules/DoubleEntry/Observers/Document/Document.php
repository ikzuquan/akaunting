<?php

namespace Modules\DoubleEntry\Observers\Document;

use App\Abstracts\Observer;
use App\Models\Document\Document as Model;
use App\Traits\Jobs;
use App\Traits\Modules;
use Modules\CreditDebitNotes\Models\CreditNote;
use Modules\CreditDebitNotes\Models\DebitNote;
use Modules\DoubleEntry\Jobs\Ledger\CreateLedger;
use Modules\DoubleEntry\Jobs\Ledger\DeleteLedger;
use Modules\DoubleEntry\Jobs\Ledger\UpdateLedger;
use Modules\DoubleEntry\Models\Account as Coa;
use Modules\DoubleEntry\Models\Ledger;
use Modules\DoubleEntry\Traits\Permissions;

class Document extends Observer
{
    use Jobs, Permissions, Modules;

    /**
     * Listen to the created event.
     *
     * @param  Model  $document
     * @return void
     */
    public function created(Model $document)
    {
        if ($this->skipEvent($document)) {
            return;
        }

        $request = $this->getDocumentBaseRequest($document);

        $request = $this->appendDocumentSpecificFields($request, $document);

        $this->dispatch(new CreateLedger($request));
    }

    /**
     * Listen to the updated event.
     *
     * @param  Model  $document
     * @return void
     */
    public function updated(Model $document)
    {
        if ($this->skipEvent($document)) {
            return;
        }

        $ledger = Ledger::record($document->id, get_class($document))->first();

        if (is_null($ledger)) {
            return;
        }

        $request = $this->getDocumentBaseRequest($document);

        $request = $this->appendDocumentSpecificFields($request, $document);

        $this->dispatch(new UpdateLedger($ledger, $request));
    }

    /**
     * Listen to the deleted event.
     *
     * @param  Model  $document
     * @return void
     */
    public function deleted(Model $document)
    {
        if ($this->skipEvent($document)) {
            return;
        }

        $ledger = Ledger::record($document->id, get_class($document))->first();

        if (is_null($ledger)) {
            return;
        }

        $this->dispatch(new DeleteLedger($ledger));
    }

    /**
     * Gets the basic parameters for the document request.
     *
     * @param Model $document
     * @return array
     */
    private function getDocumentBaseRequest($document)
    {
        return [
            'company_id' => $document->company_id,
            'ledgerable_id' => $document->id,
            'ledgerable_type' => get_class($document),
            'issued_at' => $document->issued_at,
            'entry_type' => 'total',
        ];
    }

    /**
     * Appends the document specific parameters.
     *
     * @param array $request
     * @param Model $document
     * @return array
     */
    private function appendDocumentSpecificFields($request, $document)
    {
        if ($document->type == Model::INVOICE_TYPE) {
            $request['account_id'] = Coa::code(setting('double-entry.accounts_receivable', 120))->pluck('id')->first();
            $request['debit'] = $document->amount;
        }

        if ($document->type == Model::BILL_TYPE) {
            $request['account_id'] = Coa::code(setting('double-entry.accounts_payable', 200))->pluck('id')->first();
            $request['credit'] = $document->amount;
        }

        if ($this->moduleIsEnabled('credit-debit-notes') && $document->type == CreditNote::TYPE) {
            $request['account_id'] = Coa::code(setting('double-entry.accounts_receivable', 120))->pluck('id')->first();
            $request['credit'] = $document->amount;
        }

        if ($this->moduleIsEnabled('credit-debit-notes') && $document->type == DebitNote::TYPE) {
            $request['account_id'] = Coa::code(setting('double-entry.accounts_payable', 200))->pluck('id')->first();
            $request['debit'] = $document->amount;
        }

        return $request;
    }

    /**
     * Determines event will be continued or not.
     *
     * @param Model $document
     * @return bool
     */
    private function skipEvent(Model $document)
    {
        if (!$this->moduleIsEnabled('double-entry') ||
            $this->isNotValidDocumentType($document->type)) {
            return true;
        }

        return false;
    }
}

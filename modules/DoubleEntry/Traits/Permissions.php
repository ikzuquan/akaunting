<?php

namespace Modules\DoubleEntry\Traits;

use App\Models\Document\Document;
use App\Traits\Modules;
use Modules\CreditDebitNotes\Models\CreditNote;
use Modules\CreditDebitNotes\Models\DebitNote;

trait Permissions
{
    use Modules;

    protected function isNotValidDocumentType($type): bool
    {
        $valid_document_types = [
            Document::INVOICE_TYPE,
            Document::BILL_TYPE,
        ];

        if ($this->moduleIsEnabled('credit-debit-notes')) {
            $valid_document_types[] = CreditNote::TYPE;
            $valid_document_types[] = DebitNote::TYPE;
        }

        return !in_array($type, $valid_document_types);
    }

    protected function isNotValidTransactionType($transaction)
    {
        return !in_array($transaction->type, ['income', 'expense']);
    }
}

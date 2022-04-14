<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Document\DocumentUpdated as Event;
use App\Traits\Modules;

class DocumentUpdated
{
    use Modules;

    /**
     * Handle the event.
     *
     * @param Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if (!$this->moduleIsEnabled('double-entry')) {
            return;
        }

        $event->document->refresh();

        foreach ($event->document->items as $document_item) {
            if ($ledger = $document_item->de_ledger) {
                $ledger->issued_at = $event->document->issued_at;
                $ledger->save();
            }
        }

        foreach ($event->document->item_taxes as $document_item_tax) {
            if ($ledger = $document_item_tax->de_ledger) {
                $ledger->issued_at = $event->document->issued_at;
                $ledger->save();
            }
        }

        foreach ($event->document->totals as $document_total) {
            if ($ledger = $document_total->de_ledger) {
                $ledger->issued_at = $event->document->issued_at;
                $ledger->save();
            }
        }
    }
}

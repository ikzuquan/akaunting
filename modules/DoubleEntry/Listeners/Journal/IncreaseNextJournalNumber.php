<?php

namespace Modules\DoubleEntry\Listeners\Journal;

use App\Traits\Documents;
use Modules\DoubleEntry\Events\Journal\JournalCreated as Event;

class IncreaseNextJournalNumber
{
    use Documents;

    /**
     * Handle the event.
     *
     * @param \Modules\DoubleEntry\Events\Journal\JournalCreated $event
     * @return void
     */
    public function handle(Event $event)
    {
        $this->increaseNextDocumentNumber('double-entry.journal');
    }
}

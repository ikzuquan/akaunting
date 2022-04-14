<?php

namespace Modules\DoubleEntry\Listeners\Update\V30;

use App\Abstracts\Listeners\Update as Listener;
use App\Events\Install\UpdateFinished;
use App\Interfaces\Listener\ShouldUpdateAllCompanies;
use App\Traits\Documents;
use App\Traits\Jobs;
use Modules\DoubleEntry\Models\Journal;

class Version301 extends Listener implements ShouldUpdateAllCompanies
{
    use Jobs, Documents;

    const ALIAS = 'double-entry';

    const VERSION = '3.0.1';

    /**
     * Handle the event.
     *
     * @param  $event
     * @return void
     */
    public function handle(UpdateFinished $event)
    {
        if ($this->skipThisUpdate($event)) {
            return;
        }

        $this->updateJournals();
    }

    protected function updateJournals()
    {
        $journals = Journal::where('journal_number', 'MJE-00001')->get();

        foreach ($journals as $journal) {
            $journal->journal_number = $this->getNextDocumentNumber('double-entry.journal');
            $journal->save();

            $this->increaseNextDocumentNumber('double-entry.journal');
        }
    }
}

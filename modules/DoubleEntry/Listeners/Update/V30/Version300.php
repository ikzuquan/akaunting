<?php

namespace Modules\DoubleEntry\Listeners\Update\V30;

use App\Traits\Permissions;
use App\Models\Common\Report;
use App\Events\Install\UpdateFinished;
use Illuminate\Support\Facades\Artisan;
use Modules\DoubleEntry\Models\Journal;
use App\Abstracts\Listeners\Update as Listener;
use App\Interfaces\Listener\ShouldUpdateAllCompanies;
use App\Traits\Documents;
use App\Traits\Jobs;
use Modules\DoubleEntry\Jobs\Journal\UpdateJournalEntry;

class Version300 extends Listener implements ShouldUpdateAllCompanies
{
    use Permissions, Jobs, Documents;

    const ALIAS = 'double-entry';

    const VERSION = '3.0.0';

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

        $this->updateDatabase();
        $this->updateSettings();
        $this->updateReports();
        $this->updateJournals();
    }

    protected function updateDatabase()
    {
        Artisan::call('module:migrate', ['alias' => self::ALIAS, '--force' => true]);
    }

    protected function updateSettings()
    {
        setting()->set(['double-entry.journal.number_prefix' => 'MJE-']);
        setting()->set(['double-entry.journal.number_digit' => '5']);
        setting()->set(['double-entry.journal.number_next' => '1']);

        setting()->save();
    }

    protected function updateReports()
    {
        Report::firstOrCreate([
            'company_id' => company_id(),
            'class' => 'Modules\DoubleEntry\Reports\JournalReport',
            'name' => trans_choice('double-entry::general.journals', 1) . ' ' . trans_choice('double-entry::general.entries', 2),
            'description' => trans('double-entry::demo.reports.description.journal_report'),
        ], [
            'settings' => ['basis' => 'accrual'],
        ]);

        $this->createModuleReportPermission('double-entry', 'Modules\DoubleEntry\Reports\JournalReport');
    }

    protected function updateJournals()
    {
        $journals = Journal::where('journal_number', null)->get();

        foreach ($journals as $journal) {
            $journal->journal_number = $this->getNextDocumentNumber('double-entry.journal');
            $journal->save();
        }
    }
}

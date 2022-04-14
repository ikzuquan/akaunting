<?php

namespace Modules\DoubleEntry\BulkActions;

use App\Abstracts\BulkAction;
use Modules\DoubleEntry\Jobs\Journal\DeleteJournalEntry;
use Modules\DoubleEntry\Models\Journal;

class JournalEntry extends BulkAction
{
    public $model = Journal::class;

    public $actions = [
        'delete' => [
            'name' => 'general.delete',
            'message' => 'bulk_actions.message.delete',
            'permission' => 'delete-double-entry-journal-entry',
        ],
    ];

    /**
     * Remove the specified resource from storage.
     *
     * @param  $request
     * @return void
     */
    public function destroy($request)
    {
        $journal_entries = $this->getSelectedRecords($request);

        foreach ($journal_entries as $journal_entry) {
            try {
                $this->dispatch(new DeleteJournalEntry($journal_entry));
            } catch (\Exception $exception) {
                flash($exception->getMessage())->error()->important();
            }
        }
    }
}

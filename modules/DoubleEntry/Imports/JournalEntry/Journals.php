<?php

namespace Modules\DoubleEntry\Imports\JournalEntry;

use App\Abstracts\ImportMultipleSheets;
use Modules\DoubleEntry\Imports\JournalEntry\Sheets\JournalLedgers;
use Modules\DoubleEntry\Imports\JournalEntry\Sheets\Journals as Base;

class Journals extends ImportMultipleSheets
{
    public function sheets(): array
    {
        return [
            'journals' => new Base(),
            'journal_ledgers' => new JournalLedgers(),
        ];
    }
}

<?php

namespace Modules\DoubleEntry\Exports\JournalEntry;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\DoubleEntry\Exports\JournalEntry\Sheets\JournalLedgers;
use Modules\DoubleEntry\Exports\JournalEntry\Sheets\Journals as Base;

class Journals implements WithMultipleSheets
{
    use Exportable;

    public $ids;

    public function __construct($ids = null)
    {
        $this->ids = $ids;
    }

    public function sheets(): array
    {
        return [
            new Base($this->ids),
            new JournalLedgers($this->ids),
        ];
    }
}

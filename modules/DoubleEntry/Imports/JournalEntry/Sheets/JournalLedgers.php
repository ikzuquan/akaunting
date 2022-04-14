<?php

namespace Modules\DoubleEntry\Imports\JournalEntry\Sheets;

use App\Abstracts\Import;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\Ledger as Model;
use Modules\DoubleEntry\Traits\Accounts;

class JournalLedgers extends Import
{
    use Accounts;

    public function model(array $row)
    {
        return new Model($row);
    }

    public function map($row): array
    {
        if ($this->isEmpty($row, 'number')) {
            return [];
        }

        $row['account_id'] = $this->findImportedAccountId($row['account']);

        if (is_null($row['account_id'])) {
            return [];
        }

        $row = parent::map($row);

        $row['ledgerable_id'] = (int) Journal::where('journal_number', $row['number'])->pluck('id')->first();
        $row['ledgerable_type'] = 'Modules\\DoubleEntry\\Models\\Journal';
        $row['entry_type'] = 'item';

        if ($row['debit']) {
            $row['credit'] = 0;
        } else {
            $row['debit'] = 0;
        }

        return $row;
    }
}

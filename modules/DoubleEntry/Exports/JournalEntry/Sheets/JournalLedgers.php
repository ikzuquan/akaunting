<?php

namespace Modules\DoubleEntry\Exports\JournalEntry\Sheets;

use App\Abstracts\Export;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Modules\DoubleEntry\Models\Ledger as Model;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class JournalLedgers extends Export implements WithColumnFormatting
{
    public function collection()
    {
        return Model::with('ledgerable', 'account')
            ->where('ledgerable_type', 'Modules\\DoubleEntry\\Models\\Journal')
            ->collectForExport($this->ids, null, 'ledgerable_id');
    }

    public function map($model): array
    {
        $journal = $model->ledgerable;

        if (empty($journal)) {
            return [];
        }

        $model->number = $journal->journal_number;
        $model->account = $model->account->trans_name;

        return parent::map($model);
    }

    public function fields(): array
    {
        return [
            'number',
            'issued_at',
            'account',
            'debit',
            'credit',
            'reference',
            'notes',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_YYYYMMDD,
        ];
    }
}

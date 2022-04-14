<?php

namespace Modules\DoubleEntry\Exports\JournalEntry\Sheets;

use App\Abstracts\Export;
use Modules\DoubleEntry\Models\Journal as Model;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Journals extends Export implements WithColumnFormatting
{
    public function collection()
    {
        return Model::collectForExport($this->ids, ['paid_at' => 'desc']);
    }

    public function map($model): array
    {
        $model->number = $model->journal_number;
        $model->issued_at = $model->paid_at;

        return parent::map($model);
    }

    public function fields(): array
    {
        return [
            'number',
            'issued_at',
            'amount',
            'description',
            'reference',
            'basis',
            'currency_code',
            'currency_rate',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_YYYYMMDD,
        ];
    }
}

<?php

namespace Modules\DoubleEntry\Imports\JournalEntry\Sheets;

use App\Abstracts\Import;
use App\Models\Setting\Currency;
use Modules\DoubleEntry\Http\Requests\Journal as Request;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\Journal as Model;

class Journals extends Import
{
    public function model(array $row)
    {
        return new Model($row);
    }

    public function map($row): array
    {
        if ($this->isEmpty($row, 'number')) {
            return [];
        }

        if (!array_key_exists($row['basis'], Journal::BASIS)) {
            return [];
        }

        $row = parent::map($row);

        // if currency data is not specified, it will treat as default currency
        if (!isset($row['currency_code'])) {
            $row['currency_code'] = setting('default.currency');
            $row['currency_rate'] = 1;
        }

        $currency = Currency::code($row['currency_code'])->first();

        if (is_null($currency)) {
            $row['currency_code'] = setting('default.currency');
            $row['currency_rate'] = 1;
        }

        if (!isset($row['currency_rate'])) {
            $row['currency_rate'] = $currency->rate;
        }

        $row['journal_number'] = $row['number'];
        $row['paid_at'] = $row['issued_at'];

        return $row;
    }

    public function rules(): array
    {
        $rules = (new Request())->rules();

        unset($rules['items'], $rules['items.*.account_id'], $rules['items.*.debit'], $rules['items.*.credit']);

        return $rules;
    }
}

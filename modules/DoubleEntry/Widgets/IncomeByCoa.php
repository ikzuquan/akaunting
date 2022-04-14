<?php

namespace Modules\DoubleEntry\Widgets;

use App\Abstracts\Widget;
use App\Traits\Currencies;
use Modules\DoubleEntry\Models\Account as Coa;

class IncomeByCoa extends Widget
{
    use Currencies;

    public $default_name = 'double-entry::widgets.income_by_coa';

    public $default_settings = [
        'width' => 'col-md-6',
    ];

    public function show()
    {
        // income types
        $types = [13, 14, 15];

        Coa::with('ledgers')->inType($types)->enabled()->each(function ($coa) {
            $amount = 0;

            $model = $coa->ledgers()
                ->whereNotNull('credit')
                ->whereHasMorph('ledgerable', [
                    'App\Models\Banking\Transaction',
                    'Modules\DoubleEntry\Models\Journal',
                    'App\Models\Document\DocumentItem',
                    'App\Models\Document\DocumentTotal',
                ], function ($query, $type) {
                    if ($type == 'App\Models\Banking\Transaction') {
                        $query->whereNull('document_id');
                    }

                    if ($type == 'App\Models\Document\DocumentItem') {
                        $query->whereHas('document', function ($query) {
                            $query->invoice()->accrued();
                        });
                    }

                    if ($type == 'App\Models\Document\DocumentTotal') {
                        $query->whereHas('document', function ($query) {
                            $query->bill()->accrued();
                        });
                    }
                });

            $this->applyFilters($model, ['date_field' => 'issued_at'])->get()->each(function ($ledger) use (&$amount) {
                $ledgerable = $ledger->ledgerable;

                switch ($ledgerable->getTable()) {
                    case 'document_items':
                    case 'double_entry_journals':
                        $ledger->castCredit();

                        $amount += $ledger->credit;

                        break;
                    case 'transactions':
                        $amount += $ledgerable->getAmountConvertedToDefault();

                        break;
                    case 'document_totals':
                        $amount += $this->convertToDefault($ledgerable->amount, $ledgerable->document->currency_code, $ledgerable->document->currency_rate);

                        break;
                }

            });

            $random_color = '#' . dechex(rand(0x000000, 0xFFFFFF));

            $this->addMoneyToDonut($random_color, $amount, $coa->trans_name);
        });

        $chart = $this->getDonutChart(trans_choice('general.incomes', 1), 0, 160, 6);

        return $this->view('widgets.donut_chart', compact('chart'));
    }
}

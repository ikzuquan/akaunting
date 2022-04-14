<?php

namespace Modules\DoubleEntry\Http\ViewComposers;

use App\Traits\Modules;
use Illuminate\View\View;
use Modules\DoubleEntry\View\Components\Journals as Component;

class Journals
{
    use Modules;

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        if (!$this->moduleIsEnabled('double-entry')) {
            return;
        }

        $mapping = [
            'income' => 'transaction',
            'expense' => 'transaction',
            'journal' => 'transaction',
            'invoice' => 'document',
            'bill' => 'document',
            'credit-note' => 'document',
            'debit-note' => 'document',
        ];

        if (!array_key_exists($view->getData()['type'], $mapping)) {
            return;
        }

        $referenceDocument = $view->getData()[$mapping[$view->getData()['type']]];

        if (!$referenceDocument->de_ledger) {
            return;
        }

        $journals = new Component($referenceDocument);

        $section = 'row_footer_transactions_end';

        if ($mapping[$view->getData()['type']] == 'transaction') {
            $section = 'row_footer_histories_end';
        }

        $view->getFactory()->startPush($section, $journals->render()->with($journals->data()));
    }
}

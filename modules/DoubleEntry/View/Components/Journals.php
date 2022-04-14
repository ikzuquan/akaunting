<?php

namespace Modules\DoubleEntry\View\Components;

use App\Models\Banking\Transaction;
use App\Models\Document\Document;
use Illuminate\View\Component;

class Journals extends Component
{
    public $referenceDocument;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($referenceDocument)
    {
        $this->referenceDocument = $referenceDocument;

        if ($this->referenceDocument instanceof Document) {
            $this->referenceDocument->ledgers = collect();
            $this->referenceDocument->ledgers->push($this->referenceDocument->de_ledger);

            foreach ($this->referenceDocument->items as $item) {
                $item->load('de_ledger');

                if ($ledger = $item->de_ledger) {
                    $this->referenceDocument->ledgers->push($ledger);
                }
            }

            foreach ($this->referenceDocument->item_taxes as $item_tax) {
                $item_tax->load('de_ledger');

                if ($ledger = $item_tax->de_ledger) {
                    $this->referenceDocument->ledgers->push($ledger);
                }
            }

            foreach ($this->referenceDocument->totals as $total) {
                $total->load('de_ledger');

                if ($ledger = $total->de_ledger) {
                    $this->referenceDocument->ledgers->push($ledger);
                }
            }
        }

        if ($this->referenceDocument instanceof Transaction) {
            $this->referenceDocument->ledgers->load('account');
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('double-entry::components.journals');
    }
}

<?php

namespace Modules\DoubleEntry\Events\Ledger;

use Illuminate\Queue\SerializesModels;

class LedgerCreated
{
    use SerializesModels;

    /**
     * The ledger instance.
     *
     * @var \Modules\DoubleEntry\Models\Ledger
     */
    public $ledger;

    /**
     * Create a new event instance.
     *
     * @param \Modules\DoubleEntry\Models\Ledger $ledger
     * @return void
     */
    public function __construct($ledger)
    {
        $this->ledger = $ledger;
    }
}

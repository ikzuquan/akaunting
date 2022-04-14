<?php

namespace Modules\DoubleEntry\Events\Ledger;

use Illuminate\Queue\SerializesModels;

class LedgerUpdated
{
    use SerializesModels;

    /**
     * The ledger instance.
     *
     * @var \Modules\DoubleEntry\Models\Ledger
     */
    public $ledger;

    public $request;

    /**
     * Create a new event instance.
     *
     * @param \Modules\DoubleEntry\Models\Ledger $ledger
     * @param $request
     * @return void
     */
    public function __construct($ledger, $request)
    {
        $this->ledger = $ledger;
        $this->request = $request;
    }
}

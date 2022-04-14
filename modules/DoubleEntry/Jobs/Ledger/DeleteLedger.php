<?php

namespace Modules\DoubleEntry\Jobs\Ledger;

use App\Abstracts\Job;
use Modules\DoubleEntry\Events\Ledger\LedgerDeleted;

class DeleteLedger extends Job
{
    /**
     * The ledger instance.
     *
     * @var \Modules\DoubleEntry\Models\Ledger
     */
    protected $ledger;

    /**
     * Create a new job instance.
     *
     * @param \Modules\DoubleEntry\Models\Ledger $ledger
     * @return void
     */
    public function __construct($ledger)
    {
        $this->ledger = $ledger;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle()
    {
        \DB::transaction(function () {
            $this->ledger->delete();
        });

        event(new LedgerDeleted($this->ledger));

        return true;
    }
}

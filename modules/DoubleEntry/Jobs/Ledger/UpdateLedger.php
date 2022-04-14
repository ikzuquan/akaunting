<?php

namespace Modules\DoubleEntry\Jobs\Ledger;

use App\Abstracts\Job;
use Modules\DoubleEntry\Models\Ledger;
use Modules\DoubleEntry\Events\Ledger\LedgerUpdated;

class UpdateLedger extends Job
{
    protected $request;

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
     * @param $request
     * @return void
     */
    public function __construct($ledger, $request)
    {
        $this->ledger = $ledger;
        $this->request = $this->getRequestInstance($request);
    }

    /**
     * Execute the job.
     *
     * @return \Modules\DoubleEntry\Models\Ledger
     */
    public function handle()
    {
        \DB::transaction(function () {
            $this->ledger->update($this->request->all());
        });

        event(new LedgerUpdated($this->ledger, $this->request));

        return $this->ledger;
    }
}

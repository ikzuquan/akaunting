<?php

namespace Modules\DoubleEntry\Jobs\Account;

use App\Abstracts\Job;
use Modules\DoubleEntry\Events\Account\AccountCreated;
use Modules\DoubleEntry\Models\Account;

class CreateAccount extends Job
{
    protected $request;

    /**
     * The account instance.
     *
     * @var \Modules\DoubleEntry\Models\Account
     */
    protected $account;

    /**
     * Create a new job instance.
     *
     * @param $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $this->getRequestInstance($request);
    }

    /**
     * Execute the job.
     *
     * @return Account
     */
    public function handle()
    {
        \DB::transaction(function () {
            $this->account = Account::create($this->request->all());
        });

        event(new AccountCreated($this->account));

        return $this->account;
    }
}

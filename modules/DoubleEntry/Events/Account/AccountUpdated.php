<?php

namespace Modules\DoubleEntry\Events\Account;

use Illuminate\Queue\SerializesModels;

class AccountUpdated
{
    use SerializesModels;

    /**
     * The account instance.
     *
     * @var \Modules\DoubleEntry\Models\Account
     */
    public $account;

    public $request;

    /**
     * Create a new event instance.
     *
     * @param \Modules\DoubleEntry\Models\Account $account
     * @param $request
     * @return void
     */
    public function __construct($account, $request)
    {
        $this->account = $account;
        $this->request = $request;
    }
}

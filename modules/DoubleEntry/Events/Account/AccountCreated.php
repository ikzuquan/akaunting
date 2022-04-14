<?php

namespace Modules\DoubleEntry\Events\Account;

use Illuminate\Queue\SerializesModels;

class AccountCreated
{
    use SerializesModels;

    /**
     * The account instance.
     *
     * @var \Modules\DoubleEntry\Models\Account
     */
    public $account;

    /**
     * Create a new event instance.
     *
     * @param \Modules\DoubleEntry\Models\Account $account
     * @return void
     */
    public function __construct($account)
    {
        $this->account = $account;
    }
}

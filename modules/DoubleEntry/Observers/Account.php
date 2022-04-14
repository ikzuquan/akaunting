<?php

namespace Modules\DoubleEntry\Observers;

use App\Abstracts\Observer;
use App\Traits\Jobs;
use Modules\DoubleEntry\Jobs\Account\DeleteAccount;
use Modules\DoubleEntry\Models\Account as Model;

class Account extends Observer
{
    use Jobs;

    /**
     * Listen to the deleting event.
     *
     * @param  Model  $account
     * @return void
     */
    public function deleting(Model $account)
    {
        foreach ($account->sub_accounts as $sub_account) {
            $this->dispatch(new DeleteAccount($sub_account));
        }
    }
}

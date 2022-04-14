<?php

namespace Modules\DoubleEntry\Listeners\Account;

use App\Jobs\Banking\CreateAccount;
use App\Jobs\Banking\UpdateAccount;
use App\Traits\Jobs;
use Modules\DoubleEntry\Events\Account\AccountUpdated as Event;
use Modules\DoubleEntry\Models\AccountBank;

class UpdateAccountBank
{
    use Jobs;

    /**
     * Handle the event.
     *
     * @param \Modules\DoubleEntry\Events\Account\AccountUpdated $event
     * @return void
     */
    public function handle(Event $event)
    {
        $account = $event->account;

        if ($account->type_id != setting('double-entry.types_bank', 6)) {
            return;
        }

        if (isset($account->bank->bank)) {
            $core_account = $account->bank->bank;

            $request = [
                'company_id' => $account->company_id,
                'name' => trans($account->name),
                'number' => $core_account->number,
                'currency_code' => $core_account->currency_code,
                'opening_balance' => $core_account->opening_balance,
                'enabled' => $account->enabled,
            ];

            $this->dispatch(new UpdateAccount($core_account, $request));
        } else {
            $request = [
                'company_id' => $account->company_id,
                'name' => $account->name,
                'number' => $account->code,
                'currency_code' => setting('default.currency'),
                'opening_balance' => 0,
                'enabled' => $account->enabled,
                'bank_name' => 'chart-of-accounts',
            ];

            $banking_account = $this->dispatch(new CreateAccount($request));

            AccountBank::create([
                'company_id' => $account->company_id,
                'account_id' => $account->id,
                'bank_id' => $banking_account->id,
            ]);
        }
    }
}

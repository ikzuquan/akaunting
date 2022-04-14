<?php

namespace Modules\DoubleEntry\Jobs\Account;

use App\Abstracts\Job;
use Exception;
use Modules\DoubleEntry\Events\Account\AccountDeleted;
use Modules\DoubleEntry\Traits\Accounts;

class DeleteAccount extends Job
{
    use Accounts;

    /**
     * The account instance.
     *
     * @var \Modules\DoubleEntry\Models\Account
     */
    protected $account;

    /**
     * Create a new job instance.
     *
     * @param \Modules\DoubleEntry\Models\Account $account
     * @return void
     */
    public function __construct($account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle()
    {
        $this->authorize();

        \DB::transaction(function () {
            $this->account->delete();
        });

        event(new AccountDeleted($this->account));

        return true;
    }

    /**
     * Determine if this action is applicable.
     *
     * @return void
     * 
     * @throws \Exception
     */
    public function authorize()
    {
        $relationships = $this->countRelationships($this->account, [
            'bank' => 'bank_accounts',
            'tax' => 'tax_rates',
            'ledgers' => 'ledgers',
        ]);

        $settings = $this->countSettings($this->account);

        if (!empty($relationships) || !empty($settings)) {
            $message = trans('messages.warning.deleted', ['name' => $this->account->name, 'text' => implode(', ', $relationships)]);

            throw new \Exception($message);
        }
    }
}

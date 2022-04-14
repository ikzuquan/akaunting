<?php

namespace Modules\DoubleEntry\Jobs\Account;

use App\Abstracts\Job;
use App\Models\Banking\Account;
use Modules\DoubleEntry\Events\Account\AccountUpdated;
use Modules\DoubleEntry\Traits\Accounts;

class UpdateAccount extends Job
{
    use Accounts;

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
     * @param \Modules\DoubleEntry\Models\Account $account
     * @param $request
     * @return void
     */
    public function __construct($account, $request)
    {
        $this->account = $account;
        $this->request = $this->getRequestInstance($request);
    }

    /**
     * Execute the job.
     *
     * @return Account
     */
    public function handle()
    {
        $this->authorize();

        \DB::transaction(function () {
            if ($this->account->code != $this->request->code) {
                $this->updateSettings($this->account->code, $this->request->code);
            }

            $this->request['code'] = !empty($this->request['code']) ? $this->request['code'] : $this->account->code;
            $this->request['type_id'] = !empty($this->request['type_id']) ? $this->request['type_id'] : $this->account->type_id;

            $lang = array_flip(trans('double-entry::accounts'));

            if (!empty($lang[$this->request['name']])) {
                $this->request['name'] = 'double-entry::accounts.' . $lang[$this->request['name']];
            }

            if ($this->request['is_sub_account'] == 'false' && $this->account->account_id) {
                $this->request['account_id'] = null;
            }

            $this->account->update($this->request->all());
        });

        event(new AccountUpdated($this->account, $this->request));

        return $this->account;
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
        if ($this->account->type_id == setting('double-entry.types_bank', 6) && !isset($this->account->bank->bank)) {
            $message = trans('double-entry::messages.banking_account_not_found');

            throw new \Exception($message);
        }

        if ($this->account->type_id == setting('double-entry.types_bank', 6) && !$this->request->get('enabled') && ($this->account->bank->bank->id == setting('default.account'))) {
            $relationships = $this->getRelationships();

            $relationships[] = strtolower(trans_choice('general.companies', 1));

            $message = trans('messages.warning.disabled', ['name' => $this->account->bank->bank->name, 'text' => implode(', ', $relationships)]);

            throw new \Exception($message);
        }

        $settings = $this->countSettings($this->account);

        if (!$this->request->get('enabled') && !empty($settings)) {
            $message = trans('messages.warning.disabled', ['name' => trans($this->account->name), 'text' => strtolower(trans_choice('general.settings', 2))]);

            throw new \Exception($message);
        }
    }

    /**
     * Gets relationship with other models.
     *
     * @return array
     */
    public function getRelationships()
    {
        $rels = [
            'transactions' => 'transactions',
        ];

        return $this->countRelationships($this->account->bank->bank, $rels);
    }
}

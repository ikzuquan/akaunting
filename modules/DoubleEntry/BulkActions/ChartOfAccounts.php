<?php

namespace Modules\DoubleEntry\BulkActions;

use App\Abstracts\BulkAction;
use Modules\DoubleEntry\Jobs\Account\DeleteAccount;
use Modules\DoubleEntry\Jobs\Account\UpdateAccount;
use Modules\DoubleEntry\Models\Account;

class ChartOfAccounts extends BulkAction
{
    public $model = Account::class;

    public $actions = [
        'enable' => [
            'name' => 'general.enable',
            'message' => 'bulk_actions.message.enable',
            'permission' => 'update-double-entry-chart-of-accounts',
        ],
        'disable' => [
            'name' => 'general.disable',
            'message' => 'bulk_actions.message.disable',
            'permission' => 'update-double-entry-chart-of-accounts',
        ],
        'delete' => [
            'name' => 'general.delete',
            'message' => 'bulk_actions.message.delete',
            'permission' => 'delete-double-entry-chart-of-accounts',
        ],
    ];

    public function enable($request)
    {
        $accounts = $this->getSelectedRecords($request);

        foreach ($accounts as $account) {
            try {
                $this->dispatch(new UpdateAccount($account, ['enabled' => 1]));
            } catch (\Exception $e) {
                flash($e->getMessage())->error()->important();
            }
        }
    }

    public function disable($request)
    {
        $accounts = $this->getSelectedRecords($request);

        foreach ($accounts as $account) {
            try {
                $this->dispatch(new UpdateAccount($account, ['enabled' => 0]));
            } catch (\Exception $e) {
                flash($e->getMessage())->error()->important();
            }
        }
    }

    public function destroy($request)
    {
        $accounts = $this->getSelectedRecords($request);

        foreach ($accounts as $account) {
            try {
                $this->dispatch(new DeleteAccount($account));
            } catch (\Exception $e) {
                flash($e->getMessage())->error()->important();
            }
        }
    }
}

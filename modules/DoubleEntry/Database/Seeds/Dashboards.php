<?php

namespace Modules\DoubleEntry\Database\Seeds;

use App\Abstracts\Model;
use App\Jobs\Common\CreateDashboard;
use App\Traits\Jobs;
use Illuminate\Database\Seeder;

class Dashboards extends Seeder
{
    use Jobs;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->create();

        Model::reguard();
    }

    /**
     * Creates pre-defined dashboards for the app.
     * 
     * @return void
     */
    private function create()
    {
        $company_id = $this->command->argument('company');

        $this->dispatch(new CreateDashboard([
            'company_id' => $company_id,
            'name' => trans('double-entry::general.name'),
            'all_users' => true,
            'custom_widgets' => [
                'Modules\DoubleEntry\Widgets\TotalIncomeByCoa',
                'Modules\DoubleEntry\Widgets\TotalExpensesByCoa',
                'Modules\DoubleEntry\Widgets\TotalProfitByCoa',
                'App\Widgets\CashFlow',
                'Modules\DoubleEntry\Widgets\IncomeByCoa',
                'Modules\DoubleEntry\Widgets\ExpensesByCoa',
                'App\Widgets\AccountBalance',
                'Modules\DoubleEntry\Widgets\LatestIncomeByCoa',
                'Modules\DoubleEntry\Widgets\LatestExpensesByCoa',
            ],
        ]));
    }
}

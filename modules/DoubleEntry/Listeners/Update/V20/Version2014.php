<?php

namespace Modules\DoubleEntry\Listeners\Update\V20;

use App\Abstracts\Listeners\Update as Listener;
use App\Events\Install\UpdateFinished;
use App\Models\Module\Module;

class Version2014 extends Listener
{
    const ALIAS = 'double-entry';

    const VERSION = '2.0.14';

    /**
     * Handle the event.
     *
     * @param  $event
     * @return void
     */
    public function handle(UpdateFinished $event)
    {
        if ($this->skipThisUpdate($event)) {
            return;
        }

        $this->updateDatabase();
    }

    protected function updateDatabase()
    {
        $company_id = company_id();

        $modules = Module::where('company_id', '<>', '0')->alias('double-entry')->cursor();

        foreach ($modules as $module) {
            company($module->company_id)->makeCurrent();

            setting()->set(['double-entry.accounts_sales_discount' => 825]);
            setting()->set(['double-entry.accounts_purchase_discount' => 475]);

            setting()->save();
        }

        company($company_id)->makeCurrent();
    }
}

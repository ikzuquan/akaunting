<?php

namespace Modules\DoubleEntry\Listeners\Update\V21;

use App\Abstracts\Listeners\Update as Listener;
use App\Events\Install\UpdateFinished;
use App\Models\Module\Module;

class Version211 extends Listener
{
    const ALIAS = 'double-entry';

    const VERSION = '2.1.1';

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

        $modules = Module::allCompanies()->alias('double-entry')->cursor();

        foreach ($modules as $module) {
            company($module->company_id)->makeCurrent();

            setting()->set(['double-entry.accounts_owners_contribution' => 300]);

            setting()->save();
        }

        company($company_id)->makeCurrent();
    }
}

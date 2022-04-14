<?php

namespace Modules\DoubleEntry\Listeners\Update\V21;

use App\Abstracts\Listeners\Update as Listener;
use App\Events\Install\UpdateFinished;
use Illuminate\Support\Facades\File;

class Version217 extends Listener
{
    const ALIAS = 'double-entry';

    const VERSION = '2.1.7';

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

        $this->deleteOldFiles();
    }

    protected function deleteOldFiles()
    {
        $files = [
            'Listeners/InstallModule.php',
            'Listeners/ShowSetting.php',
            'Events/JournalCreated.php',
            'Events/JournalDeleted.php',
            'Events/JournalUpdated.php',
            'Jobs/CreateJournalEntry.php',
            'Jobs/DeleteJournalEntry.php',
            'Jobs/UpdateJournalEntry.php',
            'Jobs/DeleteAccount.php',
            'Resources/assets/chart-of-accounts.xlsx',
            'Scopes/Company.php',
            'Tests/Feature/AccountsTest.php',
            'Tests/Feature/DashboardsTest.php',
            'Tests/Feature/JournalsTest.php',
            'Tests/Feature/ReportsTest.php',
            'Tests/Feature/SettingsTest.php',
        ];

        $directories = [
            'Tests/Feature',
            'Tests',
        ];

        foreach ($files as $file) {
            File::delete(base_path('modules/DoubleEntry/' . $file));
        }

        foreach ($directories as $directory) {
            File::deleteDirectory(base_path('modules/DoubleEntry/' . $directory));
        }
    }
}

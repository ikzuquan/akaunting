<?php

namespace Modules\DoubleEntry\Listeners\Update\V21;

use App\Abstracts\Listeners\Update as Listener;
use App\Events\Install\UpdateFinished;
use Illuminate\Support\Facades\File;

class Version216 extends Listener
{
    const ALIAS = 'double-entry';

    const VERSION = '2.1.6';

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
            'Observers/Common/Company.php',
        ];

        foreach ($files as $file) {
            File::delete(base_path('modules/DoubleEntry/' . $file));
        }
    }
}

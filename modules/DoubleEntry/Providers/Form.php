<?php

namespace Modules\DoubleEntry\Providers;

use Form as Facade;
use Illuminate\Support\ServiceProvider as Provider;

class Form extends Provider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function boot()
    {
        Facade::component('doubleEntryBulkActionAllGroup', 'double-entry::partials.form.bulk_action_all_group', [
            'attributes' => []
        ]);

        Facade::component('doubleEntryBulkActionGroup', 'double-entry::partials.form.bulk_action_group', [
            'id', 'name', 'attributes' => []
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

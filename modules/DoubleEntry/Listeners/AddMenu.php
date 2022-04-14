<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Menu\AdminCreated as Event;
use App\Models\Module\Module;

class AddMenu
{
    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $module = Module::alias('double-entry')->enabled()->first();

        if (!$module) {
            return;
        }

        $user = user();

        if (!$user->can([
            'read-double-entry-chart-of-accounts',
            'read-double-entry-journal-entry',
        ])) {
            return;
        }

        $event->menu->dropdown(trans('double-entry::general.name'), function ($sub) use ($user) {
            if ($user->can('read-double-entry-chart-of-accounts')) {
                $sub->route('double-entry.chart-of-accounts.index', trans_choice('double-entry::general.chart_of_accounts', 2), [], 10, []);
            }

            if ($user->can('read-double-entry-journal-entry')) {
                $sub->route('double-entry.journal-entry.index', trans('double-entry::general.journal_entry'), [], 20, []);
            }
        }, 51, [
            'title' => trans('double-entry::general.name'),
            'icon' => 'fa fa-balance-scale',
        ]);
    }
}

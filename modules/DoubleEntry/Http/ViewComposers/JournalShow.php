<?php

namespace Modules\DoubleEntry\Http\ViewComposers;

use App\Traits\Modules;
use Illuminate\View\View;

class JournalShow
{
    use Modules;

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        if (!$this->moduleIsEnabled('double-entry')) {
            return;
        }

        $title = trans_choice('general.numbers', 1);
        $data = $view->getData()['transaction']->journal_number;

        $view->getFactory()->startPush('paid_at_input_start', view('double-entry::partials.journal_show_row', compact('title', 'data')));

        $title = trans('general.basis');
        $data = ucfirst($view->getData()['transaction']->basis);

        $view->getFactory()->startPush('paid_at_input_end', view('double-entry::partials.journal_show_row', compact('title', 'data')));
    }
}

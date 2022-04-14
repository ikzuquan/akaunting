<?php

namespace Modules\DoubleEntry\Listeners;

use App\Abstracts\Listeners\Report as Listener;
use App\Events\Report\FilterApplying;
use App\Events\Report\FilterShowing;
use App\Models\Common\Contact;
use App\Traits\DateTime;
use Modules\DoubleEntry\Models\Account as Coa;
use Modules\DoubleEntry\Traits\Journal as JournalTrait;

class AddCoaToJournalReport extends Listener
{
    use DateTime, JournalTrait;

    public $classes = [
        'Modules\DoubleEntry\Reports\JournalReport',
    ];

    /**
     * Handle filter showing event.
     *
     * @param \App\Events\Report\FilterShowing $event
     * @return void
     */
    public function handleFilterShowing(FilterShowing $event)
    {
        if ($this->skipThisClass($event)) {
            return;
        }

        $de_accounts = Coa::pluck('name', 'id')->transform(function ($name) {
            return trans($name);
        })->sort()->all();

        $event->class->filters['de_accounts'] = $de_accounts;
        $event->class->filters['names']['de_accounts'] = trans_choice('double-entry::general.chart_of_accounts', 1);

        $event->class->filters['report_at'] = '';
        $event->class->filters['keys']['report_at'] = 'report_at';
        $event->class->filters['names']['report_at'] = trans_choice('general.reports', 1) . ' ' . trans('general.date');
        $event->class->filters['types']['report_at'] = 'date';

        $event->class->filters['basis'] = $this->getBasis();
        $event->class->filters['keys']['basis'] = 'basis';
        $event->class->filters['names']['basis'] = trans('general.basis');
        $event->class->filters['defaults']['basis'] = $event->class->getSetting('basis', 'accrual');

        $event->class->filters['contact'] = Contact::pluck('name', 'id');
        $event->class->filters['keys']['contact'] = 'contact';
        $event->class->filters['names']['contact'] = trans_choice('general.contacts', 1);
    }

    /**
     * Handle filter applying event.
     *
     * @param \App\Events\Report\FilterApplying $event
     * @return void
     */
    public function handleFilterApplying(FilterApplying $event)
    {
        if ($this->skipThisClass($event)) {
            return;
        }

        if (!empty($event->args['de_account_id'])) {
            $event->model->where('account_id', $event->args['de_account_id']);
        }

        if (empty($event->args['report_at'])) {
            $this->scopeMonthsOfYear($event->model, 'issued_at');
        }

        if (!empty($event->args['report_at']) && is_array($event->args['report_at'])) {
            $start_end[] = $event->args['report_at'][0] . ' 00:00:00';
            $start_end[] = $event->args['report_at'][1] . ' 23:59:59';
        }

        if (!empty($event->args['report_at']) && !is_array($event->args['report_at'])) {
            $start_end[] = $event->args['report_at'] . ' 00:00:00';
            $start_end[] = $event->args['report_at'] . ' 23:59:59';
        }

        if (!empty($event->args['report_at']) && !empty($start_end)) {
            $event->model->whereBetween('issued_at', $start_end);
        }

        $event->model->
            whereNotIn('ledgerable_type', [
                'App\\Models\\Document\\DocumentItem',
                'App\\Models\\Document\\DocumentItemTax',
                'App\\Models\\Document\\DocumentTotal',
            ])
            ->{$event->args['basis']}()
            ->contact($event->args['contact'])
            ->orderBy('ledgerable_type', 'asc')
            ->orderBy('ledgerable_id', 'asc');
    }
}

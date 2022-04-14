<?php

namespace Modules\DoubleEntry\Listeners;

use App\Abstracts\Listeners\Report as Listener;
use App\Events\Report\FilterApplying;
use App\Events\Report\FilterShowing;
use App\Models\Common\Contact;
use App\Traits\DateTime;
use Modules\DoubleEntry\Models\Account as Coa;
use Modules\DoubleEntry\Traits\Journal as JournalTrait;

class AddCoaToGeneralLedger extends Listener
{
    use DateTime, JournalTrait;

    public $classes = [
        'Modules\DoubleEntry\Reports\GeneralLedger',
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

        $de_account_id = $this->getSearchStringValue('de_account_id');

        if (!empty($de_account_id)) {
            $event->model->where('id', $de_account_id);
        }

        $report_at = $this->getSearchStringValue('report_at');
        $basis = $this->getSearchStringValue('basis', 'accrual');
        $contact = $this->getSearchStringValue('contact', '');

        if (empty($report_at)) {
            $event->model->whereHas('ledgers', function ($query) use ($basis, $contact) {
                $this->scopeMonthsOfYear($query, 'issued_at')
                    ->$basis()
                    ->contact($contact)
                    ->orderBy('issued_at', 'asc');
            })->with('ledgers', function ($query) use ($basis, $contact) {
                $this->scopeMonthsOfYear($query, 'issued_at')
                    ->$basis()
                    ->contact($contact)
                    ->orderBy('issued_at', 'asc');

                $query->with('ledgerable');
            });

            return;
        }

        if (is_array($report_at)) {
            $start_end[] = $report_at[0] . ' 00:00:00';
            $start_end[] = $report_at[1] . ' 23:59:59';
        } else {
            $start_end[] = $report_at . ' 00:00:00';
            $start_end[] = $report_at . ' 23:59:59';
        }

        $event->model->whereHas('ledgers', function ($query) use ($start_end, $basis, $contact) {
            $query->whereBetween('issued_at', $start_end)
                ->$basis()
                ->contact($contact)
                ->orderBy('issued_at', 'asc');
        })->with('ledgers', function ($query) use ($start_end, $basis, $contact) {
            $query->whereBetween('issued_at', $start_end)
                ->$basis()
                ->contact($contact)
                ->orderBy('issued_at', 'asc');

            $query->with('ledgerable');
        });
    }
}

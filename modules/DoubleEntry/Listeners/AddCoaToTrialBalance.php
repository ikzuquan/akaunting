<?php

namespace Modules\DoubleEntry\Listeners;

use App\Abstracts\Listeners\Report as Listener;
use App\Events\Report\FilterApplying;
use App\Events\Report\FilterShowing;
use App\Traits\DateTime;
use Modules\DoubleEntry\Models\Account as Coa;
use Modules\DoubleEntry\Traits\Journal;

class AddCoaToTrialBalance extends Listener
{
    use DateTime, Journal;

    public $classes = [
        'Modules\DoubleEntry\Reports\TrialBalance',
    ];

    /**
     * Handle filter showing event.
     *
     * @param  $event
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
    }

    /**
     * Handle filter applying event.
     *
     * @param  $event
     * @return void
     */
    public function handleFilterApplying(FilterApplying $event)
    {
        if ($this->skipThisClass($event)) {
            return;
        }

        $de_account_id = $this->getSearchStringValue('de_account_id');

        $report_at = $this->getSearchStringValue('report_at');

        if (empty($report_at)) {
            $event->model->with(['accounts' => function ($query) use ($de_account_id) {
                if (!empty($de_account_id)) {
                    $query->where('double_entry_accounts.id', $de_account_id);
                }
                
                $query->whereHas('ledgers', function ($query) {
                    $this->scopeMonthsOfYear($query, 'issued_at');
                })->with('ledgers', function ($query) {
                    $this->scopeMonthsOfYear($query, 'issued_at');
                });
            }]);

            return;
        }

        if (is_array($report_at)) {
            $start_end[] = $report_at[0] . ' 00:00:00';
            $start_end[] = $report_at[1] . ' 23:59:59';
        } else {
            $start_end[] = $report_at . ' 00:00:00';
            $start_end[] = $report_at . ' 23:59:59';
        }

        $event->model->with(['accounts' => function ($query) use ($de_account_id, $start_end) {
            if (!empty($de_account_id)) {
                $query->where('double_entry_accounts.id', $de_account_id);
            }

            $query->whereHas('ledgers', function ($query) use ($start_end) {
                $query->whereBetween('issued_at', $start_end);
            })->with('ledgers', function ($query) use ($start_end) {
                $query->whereBetween('issued_at', $start_end);
            });
        }]);
    }
}

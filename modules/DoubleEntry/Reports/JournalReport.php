<?php

namespace Modules\DoubleEntry\Reports;

use App\Abstracts\Report;
use App\Models\Document\Document;
use Modules\DoubleEntry\Models\Ledger;

class JournalReport extends Report
{
    public $default_name = 'double-entry::general.journal_report';

    public $category = 'general.accounting';

    public $icon = 'fa fa-balance-scale';

    public function getGrandTotal()
    {
        return trans('general.na');
    }

    public function setViews()
    {
        parent::setViews();

        $this->views['show'] = 'double-entry::journal_report.show';
        $this->views['content'] = 'double-entry::journal_report.content';
        $this->views['header'] = 'double-entry::journal_report.header';
    }

    public function setData()
    {
        $report_at = $this->getSearchStringValue('report_at');
        $basis = $this->getSearchStringValue('basis', 'accrual');
        $contact = $this->getSearchStringValue('contact', '');
        $de_account_id = $this->getSearchStringValue('de_account_id');

        $filters = [
            'report_at' => $report_at,
            'basis' => $basis,
            'contact' => $contact,
            'de_account_id' => $de_account_id,
        ];

        $ledgers_builder = $this->applyFilters(Ledger::with(['ledgerable', 'account']), $filters);

        $this->reference_documents = $this->transformData($ledgers_builder);
    }

    public function getFields()
    {
        return [];
    }

    private function transformData($builder)
    {
        $ledgers = $builder->get();

        if ($ledgers->count() === 0) {
            return collect();
        }

        $reference_documents = $ledger_items = collect();
        $previous_ledgerable = null;

        foreach ($ledgers as $ledger) {
            if (is_null($previous_ledgerable)) {
                $previous_ledgerable = $ledger->ledgerable;
                $ledger_items = collect();
            }

            if (!$ledger->ledgerable->is($previous_ledgerable)) {
                $reference_documents->push($this->transformLedgersDocuments($ledger_items));

                $previous_ledgerable = $ledger->ledgerable;
                $ledger_items = collect();
            }

            $ledger->castDebit();
            $ledger->castCredit();
            $ledger_items->push($ledger);

            if ($ledger->ledgerable instanceof Document) {
                $ledger_items = $ledger_items->concat($this->getDocumentRelatedLedgers($ledger->ledgerable));
            }
        }

        $reference_documents->push($this->transformLedgersDocuments($ledger_items));

        return $reference_documents;
    }

    private function transformLedgersDocuments($ledger_items)
    {
        return (object) [
            'date' => company_date($ledger_items->first()->issued_at),
            'link' => $ledger_items->first()->ledgerable_link,
            'transaction' => $ledger_items->first()->transaction,
            'debit_total' => money($ledger_items->sum('debit'), setting('default.currency'), true),
            'credit_total' => money($ledger_items->sum('credit'), setting('default.currency'), true),
            'ledgers' => $ledger_items,
        ];
    }

    private function getDocumentRelatedLedgers($document)
    {
        $ledgers = collect();

        foreach ($document->items as $item) {
            if ($ledger = $item->de_ledger) {
                $ledgers->push($ledger);
            }
        }

        foreach ($document->item_taxes as $item_tax) {
            if ($ledger = $item_tax->de_ledger) {
                $ledgers->push($ledger);
            }
        }

        foreach ($document->totals as $total) {
            $total->load('de_ledger');

            if ($ledger = $total->de_ledger) {
                $ledgers->push($ledger);
            }
        }

        foreach ($ledgers as $ledger) {
            $ledger->castDebit();
            $ledger->castCredit();
        }

        return $ledgers;
    }
}

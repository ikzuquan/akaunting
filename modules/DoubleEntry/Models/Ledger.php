<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use App\Models\Banking\Transaction;
use App\Models\Document\Document;
use App\Models\Document\DocumentItem;
use App\Models\Document\DocumentItemTax;
use App\Models\Document\DocumentTotal;
use App\Traits\Modules;
use Modules\CreditDebitNotes\Models\CreditNote;
use Modules\CreditDebitNotes\Models\DebitNote;
use Modules\DoubleEntry\Casts\DefaultCurrency;
use Modules\DoubleEntry\Models\Journal;

class Ledger extends Model
{
    use Modules;

    protected $table = 'double_entry_ledger';

    protected $fillable = ['company_id', 'account_id', 'ledgerable_id', 'ledgerable_type', 'issued_at', 'entry_type', 'debit', 'credit', 'notes'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['ledgerable'];

    public function account()
    {
        return $this->belongsTo('Modules\DoubleEntry\Models\Account')->withDefault(['name' => trans('general.na')]);
    }

    public function ledgerable()
    {
        return $this->morphTo();
    }

    /**
     * Scope record.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $id
     * @param $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecord($query, $id, $type)
    {
        return $query->where('ledgerable_id', $id)->where('ledgerable_type', $type);
    }

    public function scopeCash($query)
    {
        $query->where('ledgerable_type', Transaction::class)
            ->OrWhereHasMorph('ledgerable', [
                Journal::class,
            ], function ($query) {
                $query->where('basis', 'cash');
            });
    }

    public function scopeAccrual($query)
    {
        $query->whereHasMorph('ledgerable', [
            Document::class,
            DocumentItem::class,
            DocumentItemTax::class,
            DocumentTotal::class,
            Transaction::class,
            Journal::class,
        ], function ($query, $type) {
            if ($type == 'App\Models\Document\Document') {
                $query->accrued();
            }

            if (in_array($type, ['App\Models\Document\DocumentItem', 'App\Models\Document\DocumentItemTax', 'App\Models\Document\DocumentTotal'])) {
                $query->whereHas('document', function ($query) {
                    $query->accrued();
                });
            }
        });
    }

    public function scopeContact($query, $contact)
    {
        if (empty($contact)) {
            return $query;
        }

        $query->whereHasMorph('ledgerable', [
            Document::class,
            DocumentItem::class,
            DocumentItemTax::class,
            DocumentTotal::class,
            Transaction::class,
        ], function ($query, $type) use ($contact) {
            if (in_array($type, [Document::class, Transaction::class])) {
                $query->contact($contact);
            }

            if (in_array($type, ['App\Models\Document\DocumentItem', 'App\Models\Document\DocumentItemTax', 'App\Models\Document\DocumentTotal'])) {
                $query->whereHas('document', function ($query) use ($contact) {
                    $query->contact($contact);
                });
            }
        });
    }

    public function getDescriptionAttribute()
    {
        $ledgerable = $this->ledgerable;

        if (!$ledgerable) {
            return '';
        }

        switch ($this->ledgerable_type) {
            case 'App\Models\Banking\Transaction':

                if ($ledgerable->type == 'income') {
                    $label = trans('revenues.revenue_received') . '. ' . trans('revenues.paid_by') . ' ' . $ledgerable->contact->name;
                } else {
                    $label = trans('payments.payment_made') . '. ' . trans('payments.paid_to') . ' ' . $ledgerable->contact->name;
                }

                break;

            case 'Modules\DoubleEntry\Models\Journal':

                $label = $ledgerable->description;

                if (empty($label)) {
                    $label = trans('double-entry::general.journal_entry') . '-' . $ledgerable->id;
                }

                break;

            case 'App\Models\Document\Document':

                if ($ledgerable->type == 'invoice') {
                    $label = trans('invoices.invoice_number') . ': ' . $ledgerable->document_number;
                } else {
                    $label = trans('bills.bill_number') . ': ' . $ledgerable->document_number;
                }

                break;

            case 'App\Models\Document\DocumentItem':
            case 'App\Models\Document\DocumentItemTax':

                if ($ledgerable->type == 'invoice') {
                    $label = trans('invoices.invoice_number') . ': ' . $ledgerable->document->document_number;
                } else {
                    $label = trans('bills.bill_number') . ': ' . $ledgerable->document->document_number;
                }

                break;

            default:
                $label = '';

                break;
        }

        return $label;
    }

    public function getTransactionAttribute()
    {
        $ledgerable = $this->ledgerable;

        if (!$ledgerable) {
            return trans('general.na');
        }

        switch ($this->ledgerable_type) {
            case 'App\Models\Banking\Transaction':

                $label = '#' . $ledgerable->id;

                break;

            case 'Modules\DoubleEntry\Models\Journal':

                $label = '#' . $ledgerable->id;

                if (!empty($ledgerable->journal_number)) {
                    $label = '#' . $ledgerable->journal_number;
                }

                break;

            case 'App\Models\Document\Document':

                $label = '#' . $ledgerable->document_number;

                break;

            case 'App\Models\Document\DocumentItem':
            case 'App\Models\Document\DocumentItemTax':

                $label = '#' . $ledgerable->document->document_number;

                break;

            default:
                $label = trans('general.na');

                break;
        }

        return $label;
    }

    public function getLedgerableLinkAttribute()
    {
        $ledgerable = $this->ledgerable;

        $document_types = [
            'App\Models\Document\Document',
            'App\Models\Document\DocumentItem',
            'App\Models\Document\DocumentItemTax',
        ];

        if ($this->ledgerable_type == 'Modules\DoubleEntry\Models\Journal' &&
            !empty($ledgerable->id)) {
            return route('double-entry.journal-entry.show', $ledgerable->id);
        }

        if (empty($ledgerable->type) || empty($ledgerable->id)) {
            return null;
        }

        if ($this->ledgerable_type == 'App\Models\Banking\Transaction' &&
            $ledgerable->type == 'income') {
            return route('revenues.show', $ledgerable->id);
        }

        if ($this->ledgerable_type == 'App\Models\Banking\Transaction' &&
            $ledgerable->type == 'expense') {
            return route('payments.show', $ledgerable->id);
        }

        if (in_array($this->ledgerable_type, $document_types) &&
            $ledgerable->type == Document::INVOICE_TYPE) {
            if ($this->ledgerable_type == 'App\Models\Document\Document') {
                return route('invoices.show', $ledgerable->id);
            }

            return route('invoices.show', $ledgerable->document_id);
        }

        if (in_array($this->ledgerable_type, $document_types) &&
            $ledgerable->type == Document::BILL_TYPE) {
            if ($this->ledgerable_type == 'App\Models\Document\Document') {
                return route('bills.show', $ledgerable->id);
            }

            return route('bills.show', $ledgerable->document_id);
        }

        if ($this->moduleIsEnabled('credit-debit-notes') &&
            in_array($this->ledgerable_type, $document_types) &&
            $ledgerable->type == CreditNote::TYPE) {
            if ($this->ledgerable_type == 'App\Models\Document\Document') {
                return route('credit-debit-notes.credit-notes.show', $ledgerable->id);
            }

            return route('credit-debit-notes.credit-notes.show', $ledgerable->document_id);
        }

        if ($this->moduleIsEnabled('credit-debit-notes') &&
            in_array($this->ledgerable_type, $document_types) &&
            $ledgerable->type == DebitNote::TYPE) {
            if ($this->ledgerable_type == 'App\Models\Document\Document') {
                return route('credit-debit-notes.debit-notes.show', $ledgerable->id);
            }

            return route('credit-debit-notes.debit-notes.show', $ledgerable->document_id);
        }

        return null;
    }

    public function castDebit($castable = DefaultCurrency::class)
    {
        $this->mergeCasts([
            'debit' => $castable,
        ]);
    }

    public function castCredit($castable = DefaultCurrency::class)
    {
        $this->mergeCasts([
            'credit' => $castable,
        ]);
    }
}

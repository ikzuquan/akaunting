<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use App\Traits\Currencies;
use App\Traits\Documents;
use App\Traits\Media;
use Bkwld\Cloner\Cloneable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DoubleEntry\Database\Factories\Journal as JournalFactory;

class Journal extends Model
{
    use Cloneable, Currencies, Documents, HasFactory, Media;

    public const BASIS = [
        'cash' => 'general.cash',
        'accrual' => 'general.accrual',
    ];

    protected $table = 'double_entry_journals';

    protected $fillable = ['company_id', 'paid_at', 'amount', 'description', 'reference', 'journal_number', 'basis', 'currency_code', 'currency_rate'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'double',
    ];

    /**
     * Sortable columns.
     *
     * @var array
     */
    public $sortable = ['paid_at', 'amount'];

    /**
     * @var array
     */
    public $cloneable_relations = ['ledgers'];

    public function ledger()
    {
        return $this->morphOne('Modules\DoubleEntry\Models\Ledger', 'ledgerable');
    }

    public function ledgers()
    {
        return $this->morphMany('Modules\DoubleEntry\Models\Ledger', 'ledgerable');
    }

    /**
     * Get the current balance.
     *
     * @return string
     */
    public function getAttachmentAttribute($value)
    {
        if (!empty($value) && !$this->hasMedia('attachment')) {
            return $value;
        } elseif (!$this->hasMedia('attachment')) {
            return false;
        }

        return $this->getMedia('attachment')->all();
    }

    /**
     * @inheritDoc
     *
     * @param  Journal $journal
     * @param  boolean $child
     */
    public function onCloning($journal, $child = null)
    {
        $this->journal_number = $this->getNextDocumentNumber('double-entry.journal');
    }

    /**
     * @inheritDoc
     *
     * @param  Journal $journal
     */
    public function onCloned($journal)
    {
        $this->increaseNextDocumentNumber('double-entry.journal');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return JournalFactory::new();
    }
}

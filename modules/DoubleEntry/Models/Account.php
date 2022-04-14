<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use App\Models\Common\Report;
use App\Traits\DateTime;
use App\Utilities\Date;
use Bkwld\Cloner\Cloneable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Modules\DoubleEntry\Database\Factories\Account as AccountFactory;
use Modules\DoubleEntry\Reports\GeneralLedger;
use Modules\DoubleEntry\Traits\Accounts;
use Znck\Eloquent\Traits\BelongsToThrough;

class Account extends Model
{
    use Accounts, Cloneable, DateTime, HasFactory, BelongsToThrough;

    protected $table = 'double_entry_accounts';

    protected $appends = ['debit_total', 'credit_total', 'trans_name'];

    protected $fillable = ['company_id', 'type_id', 'code', 'name', 'description', 'account_id', 'enabled'];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function sub_accounts()
    {
        return $this->hasMany(Account::class)->with('accounts');
    }

    public function type()
    {
        return $this->belongsTo('Modules\DoubleEntry\Models\Type');
    }

    public function declass()
    {
        return $this->belongsToThrough(
            'Modules\DoubleEntry\Models\DEClass',
            'Modules\DoubleEntry\Models\Type',
            null,
            '',
            [
                'Modules\DoubleEntry\Models\Type' => 'type_id',
                'Modules\DoubleEntry\Models\DEClass' => 'class_id',
            ]
        );
    }

    public function bank()
    {
        return $this->belongsTo('Modules\DoubleEntry\Models\AccountBank', 'id', 'account_id');
    }

    public function tax()
    {
        return $this->belongsTo('Modules\DoubleEntry\Models\AccountTax', 'id', 'account_id');
    }

    public function ledgers()
    {
        $ledgers = $this->hasMany('Modules\DoubleEntry\Models\Ledger');

        if (request()->has('start_date')) {
            $start_date = request('start_date') . ' 00:00:00';
            $end_date = request('end_date') . ' 23:59:59';

            $ledgers->whereBetween('issued_at', [$start_date, $end_date]);
        }

        return $ledgers;
    }

    /**
     * Scope to get all rows filtered, sorted.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $sort
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCollect($query, $sort = 'name')
    {
        $request = request();

        $search = $request->get('search');

        $query->usingSearchString($search)->sortable($sort);

        return $query->get();
    }

    /**
     * Scope to only include accounts of a given type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $types
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInType($query, $types)
    {
        if (empty($types)) {
            return $query;
        }

        return $query->whereIn('type_id', (array) $types);
    }

    /**
     * Scope code.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope gets only parent accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsNotSubAccount($query)
    {
        return $query->whereNull('account_id');
    }

    /**
     * Get the debit total of an account.
     *
     * @return double
     */
    public function getDebitTotalAttribute()
    {
        $total = 0;

        if (!isset($this->start_date) || !isset($this->end_date)) {
            $financial_year = $this->getFinancialYear();

            $this->start_date = $financial_year->getStartDate();
            $this->end_date = $financial_year->getEndDate();
        }

        if (!isset($this->basis)) {
            $this->basis = 'accrual';
        }

        $this->ledgers()
            ->{$this->basis}()
            ->whereBetween('issued_at', [$this->start_date, $this->end_date])
            ->with(['ledgerable'])
            ->each(function ($ledger) use (&$total) {
                $ledger->castDebit();

                $total += $ledger->debit;
            });

        return $total;
    }

    /**
     * Get the credit total of an account.
     *
     * @return double
     */
    public function getCreditTotalAttribute()
    {
        $total = 0;

        if (!isset($this->start_date) || !isset($this->end_date)) {
            $financial_year = $this->getFinancialYear();

            $this->start_date = $financial_year->getStartDate();
            $this->end_date = $financial_year->getEndDate();
        }

        if (!isset($this->basis)) {
            $this->basis = 'accrual';
        }

        $this->ledgers()
            ->{$this->basis}()
            ->whereBetween('issued_at', [$this->start_date, $this->end_date])
            ->with(['ledgerable'])
            ->each(function ($ledger) use (&$total) {
                $ledger->castCredit();

                $total += $ledger->credit;
            });

        return $total;
    }

    /**
     * Get the balance of an account.
     *
     * @return double
     */
    public function getBalanceAttribute()
    {
        return $this->calculateBalance();
    }

    /**
     * Get the balance of an account without considering sub accounts.
     *
     * @return double
     */
    public function getBalanceWithoutSubaccountsAttribute()
    {
        return $this->calculateBalance(false);
    }

    /**
     * Get the opening balance of an account.
     *
     * @return double
     */
    public function getOpeningBalanceAttribute()
    {
        $this->setOpeningBalanceDates();

        return $this->calculateBalance(false);
    }

    /**
     * Get the balance of an account with linked to general ledger.
     *
     * @return string
     */
    public function getBalanceLinkedGeneralLedgerAttribute()
    {
        $report = Report::where('class', GeneralLedger::class)->first();

        if (is_null($report)) {
            return Str::of('<span class="' . $this->getDigitColor($this->balance) . '">')
                ->append(money($this->balance, setting('default.currency'), true))
                ->append('</span>');
        }

        return Str::of('<a ')
            ->append('class="' . $this->getDigitColor($this->balance) . '" ')
            ->append('href="' . route('reports.show', [$report->id]) . '?search=de_account_id:' . $this->id)
            ->append('">')
            ->append(money($this->balance, setting('default.currency'), true))
            ->append('</a>');
    }

    /**
     * Get the name of an account with linked to general ledger.
     *
     * @return string
     */
    public function getNameLinkedGeneralLedgerAttribute()
    {
        $report = Report::where('class', GeneralLedger::class)->first();

        if (is_null($report) || !isset($this->id)) {
            return $this->trans_name;
        }

        return Str::of('<a ')
            ->append('href="' . route('reports.show', [$report->id]) . '?search=de_account_id:' . $this->id)
            ->append('">')
            ->append($this->trans_name)
            ->append('</a>');
    }

    /**
     * Get the balance of an account colorized.
     *
     * @return string
     */
    public function getBalanceColorizedAttribute()
    {
        return $this->getBalanceColorized();
    }

    /**
     * Get the balance of an account without considering sub accounts and colorized.
     *
     * @return string
     */
    public function getBalanceWithoutSubaccountsColorizedAttribute()
    {
        return $this->getBalanceColorized(false);
    }

    /**
     * Get the translated name of an account.
     *
     * @return string
     */
    public function getTransNameAttribute()
    {
        return is_array(trans($this->name)) ? $this->name : trans($this->name);
    }

    /**
     * Set the start date of an account.
     *
     * @return void
     */
    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $value;
    }

    /**
     * Set the end date of an account.
     *
     * @return void
     */
    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value;
    }

    /**
     * A no-op callback that gets fired when a model is cloning but before it gets
     * committed to the database
     *
     * @param  Illuminate\Database\Eloquent\Model $src
     * @param  boolean $child
     * @return void
     */
    public function onCloning($src, $child = null)
    {
        $this->code = $this->getNextAccountCode();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return AccountFactory::new();
    }
}

<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;

class Type extends Model
{
    public const TYPES = [
        1 => 'double-entry::types.current_asset',
        2 => 'double-entry::types.fixed_asset',
        3 => 'double-entry::types.inventory',
        4 => 'double-entry::types.non_current_asset',
        5 => 'double-entry::types.prepayment',
        6 => 'double-entry::types.bank_cash',
        7 => 'double-entry::types.current_liability',
        8 => 'double-entry::types.liability',
        9 => 'double-entry::types.non_current_liability',
        10 => 'double-entry::types.depreciation',
        11 => 'double-entry::types.direct_costs',
        12 => 'double-entry::types.expense',
        13 => 'double-entry::types.revenue',
        14 => 'double-entry::types.sales',
        15 => 'double-entry::types.other_income',
        16 => 'double-entry::types.equity',
        17 => 'double-entry::types.tax',
    ];

    protected $table = 'double_entry_types';

    protected $tenantable = false;

    protected $fillable = ['class_id', 'name'];

    public function accounts()
    {
        return $this->hasMany('Modules\DoubleEntry\Models\Account');
    }

    public function declass()
    {
        return $this->belongsTo('Modules\DoubleEntry\Models\DEClass', 'class_id', 'id');
    }
}

<?php

namespace Modules\DoubleEntry\Exports;

use App\Abstracts\Export;
use Modules\DoubleEntry\Models\Account as Model;

class COA extends Export
{
    public function title(): string
    {
        return 'Chart_Of_Accounts';
    }

    public function collection()
    {
        $model = Model::collectForExport($this->ids);

        return $model;
    }

    public function map($model): array
    {
        $model->type = trans($model->type->name);
        $model->name = $model->trans_name;

        if (!is_null($model->account_id)) {
            $parent_account = Model::find($model->account_id, ['name']);

            $model->parent = $parent_account->trans_name;
        }

        return parent::map($model);
    }

    public function fields(): array
    {
        return [
            'type',
            'code',
            'name',
            'description',
            'enabled',
            'parent',
        ];
    }
}

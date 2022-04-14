<?php

namespace Modules\DoubleEntry\Imports;

use App\Abstracts\Import;
use Modules\DoubleEntry\Http\Requests\Account as Request;
use Modules\DoubleEntry\Models\Account as Model;
use Modules\DoubleEntry\Traits\Accounts;

class COA extends Import
{
    use Accounts;

    public function model(array $row)
    {
        return new Model($row);
    }

    public function map($row): array
    {
        $row['type_id'] = $this->findImportedTypeId($row['type']);

        if (is_null($row['type_id'])) {
            return [];
        }

        $row['account_id'] = $this->findImportedAccountId($row['parent']);

        return parent::map($row);
    }

    public function rules(): array
    {
        return (new Request())->rules();
    }
}

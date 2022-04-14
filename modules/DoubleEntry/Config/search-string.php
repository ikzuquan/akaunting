<?php

use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\Type;

return [
    Journal::class => [
        'columns' => [
            'description' => ['searchable' => true],
            'reference' => ['searchable' => true],
            'journal_number' => ['searchable' => true],
            'currency_code' => ['searchable' => true],
            'basis' => [
                'key' => 'basis',
                'values' => Journal::BASIS,
            ],
            'paid_at' => [
                'key' => 'date',
                'date' => true,
            ],
        ],
    ],
    Account::class => [
        'columns' => [
            'code' => ['searchable' => true],
            'description' => ['searchable' => true],
            'enabled' => ['boolean' => true],
            'type_id' => [
                'values' => Type::TYPES,
            ],
        ],
    ],
];

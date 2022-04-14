<?php

return [

    'journal' => [
        'alias' => 'double-entry',
        'route' => [
            'prefix' => 'journal-entry',
            'parameter' => 'journal_entry',
        ],
        'permission' => [
            'prefix' => 'journal-entry',
        ],
        'translation' => [
            'prefix' => 'general',
            'header_account' => '#',
            'accounts' => '#',
        ],
    ],

];

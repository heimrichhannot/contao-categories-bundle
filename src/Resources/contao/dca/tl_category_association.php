<?php

$GLOBALS['TL_DCA']['tl_category_association'] = [
    'config' => [
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'fields' => [
        'id'       => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp'   => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'category' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'entity'   => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'field'    => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
    ]
];
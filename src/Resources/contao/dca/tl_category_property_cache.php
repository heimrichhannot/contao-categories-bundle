<?php

$GLOBALS['TL_DCA']['tl_category_property_cache'] = [
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
        // the category/categories field
        'field'    => [
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        // e.g. jumpTo
        'property' => [
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'context'  => [
            'sql' => "varchar(128) NOT NULL default ''"
        ],
        'value'    => [
            'sql' => "blob NULL"
        ],
    ]
];
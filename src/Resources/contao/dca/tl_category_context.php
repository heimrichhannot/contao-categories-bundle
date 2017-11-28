<?php

$GLOBALS['TL_DCA']['tl_category_context'] = [
    'config'   => [
        'dataContainer'     => 'Table',
        'enableVersioning'  => true,
        'onsubmit_callback' => [
            ['HeimrichHannot\Haste\Dca\General', 'setDateAdded'],
        ],
        'sql'               => [
            'keys' => [
                'id' => 'primary'
            ]
        ],
        'backlink'          => 'do=categories'
    ],
    'list'     => [
        'label'             => [
            'fields' => ['title'],
            'format' => '%s'
        ],
        'sorting'           => [
            'mode'         => 1,
            'fields'       => ['title'],
            'headerFields' => ['title'],
            'panelLayout'  => 'filter;sort,search,limit'
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();"'
            ],
        ],
        'operations'        => [
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_category_context']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif'
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_category_context']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.gif'
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_category_context']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_category_context']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif'
            ],
        ]
    ],
    'palettes' => [
        'default' => '{general_legend},title;'
    ],
    'fields'   => [
        'id'        => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp'    => [
            'label' => &$GLOBALS['TL_LANG']['tl_category_context']['tstamp'],
            'sql'   => "int(10) unsigned NOT NULL default '0'"
        ],
        'dateAdded' => [
            'label'   => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
            'sorting' => true,
            'flag'    => 6,
            'eval'    => ['rgxp' => 'datim', 'doNotCopy' => true],
            'sql'     => "int(10) unsigned NOT NULL default '0'"
        ],
        'title'     => [
            'label'     => &$GLOBALS['TL_LANG']['tl_category_context']['title'],
            'exclude'   => true,
            'search'    => true,
            'sorting'   => true,
            'flag'      => 1,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'tl_class' => 'w50', 'unique' => true],
            'sql'       => "varchar(255) NOT NULL default ''"
        ]
    ]
];
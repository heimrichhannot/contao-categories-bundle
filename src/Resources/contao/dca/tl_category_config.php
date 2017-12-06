<?php

$GLOBALS['TL_DCA']['tl_category_config'] = [
    'config'   => [
        'dataContainer'     => 'Table',
        'enableVersioning'  => true,
        'ptable'            => 'tl_category',
        'onsubmit_callback' => [
            ['HeimrichHannot\Haste\Dca\General', 'setDateAdded'],
        ],
        'sql'               => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'list'     => [
        'label'             => [
            'fields' => ['context'],
            'format' => '%s'
        ],
        'sorting'           => [
            'mode'         => 1,
            'flag'         => 1,
            'fields'       => ['context'],
            'headerFields' => ['context'],
            'panelLayout'  => 'filter;sort,search,limit'
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();"'
            ]
        ],
        'operations'        => [
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_category_config']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif'
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_category_config']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.gif'
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_category_config']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_category_config']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif'
            ],
        ]
    ],
    'palettes' => [
        'default' => '{general_legend},context;{redirect_legend},overrideJumpTo;'
    ],
    'fields'   => [
        'id'        => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid'       => [
            'foreignKey' => 'tl_category.title',
            'sql'        => "int(10) unsigned NOT NULL default '0'",
            'relation'   => ['type' => 'belongsTo', 'load' => 'eager']
        ],
        'tstamp'    => [
            'label' => &$GLOBALS['TL_LANG']['tl_category_config']['tstamp'],
            'sql'   => "int(10) unsigned NOT NULL default '0'"
        ],
        'dateAdded' => [
            'label'   => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
            'sorting' => true,
            'flag'    => 6,
            'eval'    => ['rgxp' => 'datim', 'doNotCopy' => true],
            'sql'     => "int(10) unsigned NOT NULL default '0'"
        ],
        'context'   => [
            'label'            => &$GLOBALS['TL_LANG']['tl_category_config']['context'],
            'exclude'          => true,
            'filter'           => true,
            'inputType'        => 'select',
            'options_callback' => ['HeimrichHannot\CategoriesBundle\Backend\CategoryConfig', 'getContextsAsOptions'],
            'save_callback'    => [['HeimrichHannot\CategoriesBundle\Backend\CategoryConfig', 'deleteCachedPropertyValuesByCategoryAndContext']],
            'eval'             => ['tl_class' => 'w50', 'mandatory' => true, 'includeBlankOption' => true],
            'sql'              => "int(10) unsigned NOT NULL default '0'"
        ]
    ]
];

\HeimrichHannot\Haste\Dca\General::addOverridableFields(['jumpTo'], 'tl_category', 'tl_category_config', [
    'save_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'deleteCachedPropertyValuesByCategoryAndPropertyBool']],
]);